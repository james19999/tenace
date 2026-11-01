<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAuditEvent;
use App\Models\FinanceCashMovement;
use App\Models\FinanceTontine;
use App\Models\FinanceTontineMovement;
use App\Models\StockLocation;
use App\Models\User;
use App\Notifications\FinanceWorkflowNotification;
use App\Services\FinanceAuditService;
use App\Services\FinanceCashLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceTontineController extends Controller
{
    private const ASSIGNABLE_ROLES = [
        User::ROLE_MANAGER, User::ROLE_SECRETARY, User::ROLE_SALES, User::ROLE_CASHIER,
        User::ROLE_LIVREUR, User::ROLE_CALL_CENTER, User::ROLE_ACCOUNTANT,
        User::ROLE_COMMERCIAL_MANAGER, User::ROLE_WHOLESALER, User::ROLE_IT,
    ];
    private const ROLE_LABELS = [
        User::ROLE_MANAGER => 'Manager', User::ROLE_SECRETARY => 'Secrétaire',
        User::ROLE_SALES => 'Vendeur', User::ROLE_CASHIER => 'Caissier',
        User::ROLE_LIVREUR => 'Livreur', User::ROLE_CALL_CENTER => 'SAV',
        User::ROLE_ACCOUNTANT => 'Comptable', User::ROLE_COMMERCIAL_MANAGER => 'Responsable commercial',
        User::ROLE_WHOLESALER => 'Grossiste', User::ROLE_IT => 'Informaticien / ingénieur informatique',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $weekStart = today()->startOfWeek()->startOfDay();
        $nextWeekStart = $weekStart->copy()->addWeek();
        $query = FinanceTontine::query()
            ->when(! $user->isAdmin(), fn (Builder $scope) => $scope->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->id)))
            ->with(['assignees', 'location'])
            ->withExists(['movements as contribution_this_week' => fn (Builder $movements) => $movements->where('type', 'contribution')->where('occurred_at', '>=', $weekStart)->where('occurred_at', '<', $nextWeekStart)])
            ->withSum(['movements as contributed_amount' => fn (Builder $movements) => $movements->where('type', 'contribution')], 'amount')
            ->withSum(['movements as recovered_amount' => fn (Builder $movements) => $movements->where('type', 'recovery')], 'amount')
            ->latest('created_at');

        $statsQuery = FinanceTontine::query()
            ->when(! $user->isAdmin(), fn (Builder $scope) => $scope->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->id)));
        $tontineIds = (clone $statsQuery)->select('id');
        $totalContributed = (int) FinanceTontineMovement::query()->where('type', 'contribution')->whereIn('tontine_id', $tontineIds)->sum('amount');
        $totalRecovered = (int) FinanceTontineMovement::query()->where('type', 'recovery')->whereIn('tontine_id', $tontineIds)->sum('amount');
        $stats = [
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'contributed' => $totalContributed,
            'recovered' => $totalRecovered,
            'remaining' => max(0, $totalContributed - $totalRecovered),
        ];
        $tontines = $query->paginate(12)->withQueryString();

        return view('finance.tontines.index', [
            'tontines' => $tontines,
            'stats' => $stats,
            'canManage' => $user->isAdmin(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $oldAssigneeIds = old('assignee_ids', []);
        $oldAssigneeIds = is_array($oldAssigneeIds) ? $oldAssigneeIds : [];

        return view('finance.tontines.create', [
            'locations' => StockLocation::query()->where('type', 'shop')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedAssignees' => User::query()->where('active', true)->whereIn('user_type', self::ASSIGNABLE_ROLES)
                ->whereIn('id', $oldAssigneeIds)->orderBy('name')->get(['id', 'name', 'user_type']),
            'isEditing' => false,
            'tontine' => null,
        ]);
    }

    public function edit(Request $request, FinanceTontine $tontine): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $tontine->load('assignees');
        $oldAssigneeIds = old('assignee_ids', $tontine->assignees->pluck('id')->all());
        $oldAssigneeIds = is_array($oldAssigneeIds) ? $oldAssigneeIds : [];

        return view('finance.tontines.create', [
            'locations' => StockLocation::query()->where('type', 'shop')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedAssignees' => User::query()->whereIn('id', $oldAssigneeIds)->orderBy('name')->get(['id', 'name', 'user_type']),
            'isEditing' => true,
            'tontine' => $tontine,
        ]);
    }

    public function searchAssignees(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['q' => ['required', 'string', 'max:80']]);
        $term = trim($data['q']);
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }
        $users = User::query()->where('active', true)->whereIn('user_type', self::ASSIGNABLE_ROLES)
            ->where('name', 'like', '%'.$term.'%')->orderBy('name')->limit(15)->get(['id', 'name', 'user_type']);

        return response()->json($users->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => self::ROLE_LABELS[$user->user_type] ?? 'Employé',
        ]));
    }

    public function store(Request $request, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'cash_custody' => ['required', Rule::in(['general_cash', 'shop'])],
            'stock_location_id' => ['nullable', 'required_if:cash_custody,shop', 'integer', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('type', 'shop')->where('is_active', true))],
            'assignee_ids' => ['required', 'array', 'min:1'],
            'assignee_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where(fn ($query) => $query->where('active', true)->whereIn('user_type', self::ASSIGNABLE_ROLES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['cash_custody'] === 'general_cash') {
            $data['stock_location_id'] = null;
        }

        $tontine = DB::transaction(function () use ($data, $request, $audit): FinanceTontine {
            $tontine = FinanceTontine::query()->create([
                'reference' => 'TMP-'.Str::uuid(),
                'name' => $data['name'],
                'first_contribution_on' => null,
                'next_contribution_on' => null,
                'expected_recovery_on' => null,
                'cash_custody' => $data['cash_custody'],
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            $tontine->update(['reference' => sprintf('TON-%s-%06d', now()->format('Y'), $tontine->id)]);
            $tontine->assignees()->syncWithPivotValues($data['assignee_ids'], ['assigned_by' => $request->user()->id]);
            $audit->record($tontine, 'tontine_created', null, [
                'reference' => $tontine->reference,
                'name' => $tontine->name,
                'cash_custody' => $tontine->cash_custody === 'shop' ? 'Espèces · '.StockLocation::query()->whereKey($tontine->stock_location_id)->value('name') : 'Caisse générale',
                'assigned_users' => User::query()->whereIn('id', $data['assignee_ids'])->orderBy('name')->pluck('name')->all(),
                'notes' => $tontine->notes,
            ]);

            return $tontine->fresh();
        });

        $tontineUrl = route('finance.tontines.show', $tontine);
        $tontine->assignees->each(fn (User $assignee) => $assignee->notify(new FinanceWorkflowNotification(
            'Nouvelle tontine à suivre',
            'Tu as été désigné pour enregistrer les cotisations de '.$tontine->name.'.',
            $tontineUrl,
            'savings',
        )));

        return redirect()->route('finance.tontines.show', $tontine)->with('success', 'Tontine créée et personnes chargées notifiées.');
    }

    public function update(Request $request, FinanceTontine $tontine, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'cash_custody' => ['required', Rule::in(['general_cash', 'shop'])],
            'stock_location_id' => ['nullable', 'required_if:cash_custody,shop', 'integer', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('type', 'shop')->where('is_active', true))],
            'assignee_ids' => ['required', 'array', 'min:1'],
            'assignee_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where(fn ($query) => $query->where('active', true)->whereIn('user_type', self::ASSIGNABLE_ROLES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['cash_custody'] === 'general_cash') {
            $data['stock_location_id'] = null;
        }

        $addedAssigneeIds = DB::transaction(function () use ($data, $request, $tontine, $audit): array {
            $lockedTontine = FinanceTontine::query()->lockForUpdate()->findOrFail($tontine->id);
            $lockedTontine->load(['location', 'assignees']);
            $currentAssigneeIds = $lockedTontine->assignees->pluck('id')->all();
            $newAssigneeIds = array_map('intval', $data['assignee_ids']);
            $added = array_values(array_diff($newAssigneeIds, $currentAssigneeIds));
            $removed = array_values(array_diff($currentAssigneeIds, $newAssigneeIds));
            $cashLabel = fn (string $custody, ?int $locationId): string => $custody === 'shop'
                ? 'Espèces · '.StockLocation::query()->whereKey($locationId)->value('name')
                : 'Caisse générale';
            $before = [
                'name' => $lockedTontine->name,
                'cash_custody' => $cashLabel($lockedTontine->cash_custody, $lockedTontine->stock_location_id),
                'assigned_users' => $lockedTontine->assignees->pluck('name')->sort()->values()->all(),
                'notes' => $lockedTontine->notes,
            ];

            $lockedTontine->update([
                'name' => $data['name'],
                'cash_custody' => $data['cash_custody'],
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            if ($removed !== []) {
                $lockedTontine->assignees()->detach($removed);
            }
            foreach ($added as $assigneeId) {
                $lockedTontine->assignees()->attach($assigneeId, ['assigned_by' => $request->user()->id]);
            }

            $after = [
                'name' => $lockedTontine->name,
                'cash_custody' => $cashLabel($lockedTontine->cash_custody, $lockedTontine->stock_location_id),
                'assigned_users' => User::query()->whereIn('id', $newAssigneeIds)->orderBy('name')->pluck('name')->all(),
                'notes' => $lockedTontine->notes,
            ];
            $audit->record($lockedTontine, 'tontine_updated', $before, $after);

            return $added;
        });

        $tontine->refresh();
        $tontineUrl = route('finance.tontines.show', $tontine);
        User::query()->whereIn('id', $addedAssigneeIds)->get()->each(fn (User $assignee) => $assignee->notify(new FinanceWorkflowNotification(
            'Tontine à suivre',
            'Tu as été désigné pour enregistrer les cotisations de '.$tontine->name.'.',
            $tontineUrl,
            'savings',
        )));

        return redirect()->route('finance.tontines.show', $tontine)->with('success', 'Tontine modifiée.');
    }

    public function show(Request $request, FinanceTontine $tontine): View
    {
        $this->authorizeTontine($request->user(), $tontine);
        $canManage = $request->user()->isAdmin();
        $tontine->load(['creator', 'location']);
        if ($canManage) {
            $tontine->load(['assignees', 'movements.creator']);
            $displayMovements = $tontine->movements->sortByDesc('occurred_at');
            $contributed = (int) $tontine->movements->where('type', 'contribution')->sum('amount');
            $recovered = (int) $tontine->movements->where('type', 'recovery')->sum('amount');
        } else {
            $displayMovements = $tontine->movements()->where('type', 'contribution')->where('created_by', $request->user()->id)
                ->with('creator')->orderByDesc('occurred_at')->get();
            $contributed = 0;
            $recovered = 0;
        }

        return view('finance.tontines.show', [
            'tontine' => $tontine,
            'contributed' => $contributed,
            'recovered' => $recovered,
            'recoveryRemaining' => $canManage ? max(0, $contributed - $recovered) : 0,
            'canManage' => $canManage,
            'displayMovements' => $displayMovements,
            'contributionThisWeek' => $this->hasContributionForWeek($tontine, today()),
            'canContribute' => $tontine->status === 'active' && ! $this->hasContributionForWeek($tontine, today()),
            'contributionSubmissionKey' => (string) Str::uuid(),
            'recoverySubmissionKey' => (string) Str::uuid(),
        ]);
    }

    public function storeContribution(Request $request, FinanceTontine $tontine, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        $this->authorizeTontine($request->user(), $tontine);
        $this->recordMovement($request, $tontine, 'contribution', $ledger, $audit);

        return back()->with('success', 'Cotisation enregistrée et caisse mise à jour.');
    }

    public function storeRecovery(Request $request, FinanceTontine $tontine, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->recordMovement($request, $tontine, 'recovery', $ledger, $audit);

        return back()->with('success', 'Somme récupérée enregistrée et caisse créditée.');
    }

    public function attachment(Request $request, FinanceTontineMovement $movement)
    {
        $this->authorizeTontine($request->user(), $movement->tontine);
        abort_unless($request->user()->isAdmin() || ($movement->type === 'contribution' && (int) $movement->created_by === (int) $request->user()->id), 403);
        abort_unless($movement->proof_path && Storage::disk('local')->exists($movement->proof_path), 404);

        return Storage::disk('local')->download($movement->proof_path);
    }

    private function recordMovement(Request $request, FinanceTontine $tontine, string $type, FinanceCashLedgerService $ledger, FinanceAuditService $audit): void
    {
        abort_unless($tontine->status === 'active', 409, 'Cette tontine est déjà clôturée.');
        $isContribution = $type === 'contribution';
        $availableForRecovery = $isContribution ? null : $this->availableForRecovery($tontine);
        if (! $isContribution && $availableForRecovery <= 0) {
            throw ValidationException::withMessages(['amount' => 'Aucune somme n’est disponible à récupérer.']);
        }
        $rules = [
            'submission_key' => ['required', 'uuid'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000', ...(! $isContribution ? ['max:'.$availableForRecovery] : [])],
            'occurred_at' => ['required', 'date', 'before_or_equal:today', ...($isContribution ? ['after_or_equal:'.today()->startOfWeek()->toDateString()] : [])],
            'proof' => [$isContribution ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
        $data = $request->validate($rules);
        if (FinanceTontineMovement::query()->where('submission_key', $data['submission_key'])->exists()) {
            return;
        }
        $occurredOn = Carbon::parse($data['occurred_at'])->toDateString();
        if ($isContribution) {
            if ($this->hasContributionForWeek($tontine, Carbon::parse($occurredOn))) {
                throw ValidationException::withMessages(['amount' => 'Une cotisation est déjà enregistrée pour cette semaine.']);
            }
        }

        $proofPath = $request->file('proof')?->store('finance/tontines/proofs', 'local');
        try {
            $created = DB::transaction(function () use ($request, $tontine, $type, $data, $proofPath, $ledger, $audit, $isContribution): bool {
                $existing = FinanceTontineMovement::query()->where('submission_key', $data['submission_key'])->first();
                if ($existing) {
                    return false;
                }
                $lockedTontine = FinanceTontine::query()->lockForUpdate()->findOrFail($tontine->id);
                abort_unless($lockedTontine->status === 'active', 409, 'Cette tontine est déjà clôturée.');
                if ($isContribution) {
                    if ($this->hasContributionForWeek($lockedTontine, Carbon::parse($data['occurred_at']))) {
                        throw ValidationException::withMessages(['amount' => 'Une cotisation est déjà enregistrée pour cette semaine.']);
                    }
                } else {
                    $lockedAvailableForRecovery = $this->availableForRecovery($lockedTontine);
                    if ($lockedAvailableForRecovery <= 0 || (int) $data['amount'] > $lockedAvailableForRecovery) {
                        throw ValidationException::withMessages(['amount' => 'Le montant dépasse la somme disponible à récupérer ('.number_format($lockedAvailableForRecovery, 0, ',', ' ').' F).']);
                    }
                }
                $movement = FinanceTontineMovement::query()->create([
                    'reference' => 'TMP-'.Str::uuid(),
                    'submission_key' => $data['submission_key'],
                    'tontine_id' => $lockedTontine->id,
                    'type' => $type,
                    'due_on' => null,
                    'amount' => $data['amount'],
                    'occurred_at' => Carbon::parse($data['occurred_at'])->startOfDay(),
                    'proof_path' => $proofPath,
                    'note' => $data['note'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
                $movement->update(['reference' => sprintf('TON-MVT-%s-%07d', now()->format('Y'), $movement->id)]);

                $locationId = $lockedTontine->cash_custody === 'shop' ? $lockedTontine->stock_location_id : null;
                $cashMovement = $ledger->record([
                    'movement_type' => $isContribution ? 'tontine_contribution' : 'tontine_recovery',
                    'from_custody' => $isContribution ? $lockedTontine->cash_custody : 'external',
                    'to_custody' => $isContribution ? 'external' : $lockedTontine->cash_custody,
                    'amount' => $data['amount'],
                    'stock_location_id' => $locationId,
                    'source_type' => 'finance_tontine_movement',
                    'source_id' => $movement->id,
                    'source_key' => 'tontine:'.$movement->id,
                    'source_reference' => $lockedTontine->reference,
                    'occurred_at' => Carbon::parse($data['occurred_at'])->startOfDay(),
                    'description' => ($isContribution ? 'Cotisation tontine ' : 'Récupération tontine ').$lockedTontine->name,
                    'metadata' => ['tontine_reference' => $lockedTontine->reference, 'tontine_movement_reference' => $movement->reference],
                    'created_by' => $request->user()->id,
                ]);
                $movement->update(['cash_movement_id' => $cashMovement->id]);
                if ($isContribution) {
                    $lockedTontine->first_contribution_on ??= Carbon::parse($data['occurred_at'])->toDateString();
                    $lockedTontine->save();
                }
                $audit->record($movement, $isContribution ? 'tontine_contribution_paid' : 'tontine_recovery_received', null, [
                    'reference' => $movement->reference,
                    'type' => $isContribution ? 'Cotisation payée' : 'Somme récupérée',
                    'amount' => $movement->amount,
                    'occurred_at' => Carbon::parse($data['occurred_at'])->format('d/m/Y'),
                    'due_on' => $movement->due_on?->format('d/m/Y'),
                    'proof_attached' => (bool) $movement->proof_path,
                    'note' => $movement->note,
                    'recorded_by' => $request->user()->name,
                ]);
                return true;
            });
            if (! $created && $proofPath) {
                Storage::disk('local')->delete($proofPath);
            }
        } catch (\Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }
            throw $exception;
        }
    }

    private function hasContributionForWeek(FinanceTontine $tontine, Carbon $date): bool
    {
        $weekStart = $date->copy()->startOfWeek()->startOfDay();
        $nextWeekStart = $weekStart->copy()->addWeek();

        return $tontine->movements()->where('type', 'contribution')
            ->where('occurred_at', '>=', $weekStart)
            ->where('occurred_at', '<', $nextWeekStart)
            ->exists();
    }

    private function authorizeTontine(User $user, FinanceTontine $tontine): void
    {
        abort_unless($user->isAdmin() || $tontine->assignees()->whereKey($user->id)->exists(), 403);
    }

    private function availableForRecovery(FinanceTontine $tontine): int
    {
        $contributed = (int) $tontine->movements()->where('type', 'contribution')->sum('amount');
        $recovered = (int) $tontine->movements()->where('type', 'recovery')->sum('amount');

        return max(0, $contributed - $recovered);
    }
}
