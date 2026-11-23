<?php

namespace App\Http\Controllers;

use App\Models\Orders\Order;
use App\Models\User;
use App\Services\CourierWeeklyBonusCalculator;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourierWeeklyBonusController extends Controller
{
    public function index(Request $request, CourierWeeklyBonusCalculator $calculator)
    {
        $weekStart = $request->query('week');
        $week = $weekStart ? DB::table('courier_bonus_weeks')->where('week_start', $weekStart)->first() : DB::table('courier_bonus_weeks')->latest('week_start')->first();
        $weeks = DB::table('courier_bonus_weeks')->orderByDesc('week_start')->limit(30)->get();
        $courierSearch = trim((string) $request->query('courier_search', ''));
        $courierTotal = User::query()->where('user_type', User::ROLE_LIVREUR)->where('active', true)->count();
        $couriers = User::query()->where('user_type', User::ROLE_LIVREUR)->where('active', true)
            ->when($courierSearch !== '', fn ($query) => $query->where(fn ($search) => $search->where('name', 'like', '%'.$courierSearch.'%')->orWhere('phone', 'like', '%'.$courierSearch.'%')))
            ->orderBy('name')->paginate(12, ['*'], 'courier_page')->withQueryString();
        $attendance = $week ? DB::table('courier_bonus_attendances')->where('week_id', $week->id)->get()->groupBy('courier_id') : collect();
        $ratings = $week ? DB::table('courier_bonus_ratings')->where('week_id', $week->id)->get()->keyBy('courier_id') : collect();
        $exceptions = $week ? DB::table('courier_bonus_late_exceptions')->where('week_id', $week->id)->get()->keyBy('order_id') : collect();
        $result = $week ? $calculator->calculate($week) : null;
        $deliveredOrders = $week ? $calculator->deliveredOrders($week) : collect();
        $autoPresence = $deliveredOrders->filter(fn (Order $order) => $order->delivered_at)
            ->groupBy(fn (Order $order) => $order->user_id.'|'.$order->delivered_at->toDateString())
            ->map(fn ($orders) => $orders->pluck('code')->filter()->values()->all());
        $eligibleIds = $result ? $result['orders']->pluck('id')->flip() : collect();
        $ineligibleOrders = $deliveredOrders->reject(fn (Order $order) => $eligibleIds->has($order->id))->map(function (Order $order): Order {
            $due = max(0, (int) ($order->montant ?? ((int) $order->total + (int) $order->tax)));
            $confirmed = (int) $order->payments->whereIn('status', ['collected', 'confirmed'])->sum('amount');
            $issues = [];
            if ($order->delivery_mode === 'expedition' && $order->deliveryPhotos->isEmpty()) $issues[] = 'preuve de l’expédition absente';
            if ((int) $order->paid_amount < $due || $confirmed < $due) $issues[] = 'encaissement incomplet';
            $order->setAttribute('bonus_exclusion_reason', implode(' · ', $issues));
            return $order;
        })->values();
        $ontimeCount = $result ? collect($result['points'])->sum('speed') : 0;
        $savedAwards = $week ? DB::table('courier_bonus_awards')->where('week_id', $week->id)->get()->keyBy('courier_id') : collect();
        $isLocked = $week && in_array($week->status, ['manager_validated', 'approved'], true);
        $periodSnapshot = $week && $week->result_snapshot ? (json_decode($week->result_snapshot, true) ?: []) : [];
        $lockedTotal = $isLocked ? (int) $savedAwards->sum('total_amount') : ($result['distributed'] ?? 0);
        $lockedDeliveryIds = $isLocked ? $savedAwards->flatMap(fn ($award) => (json_decode($award->snapshot, true)['points']['orders'] ?? []))->unique() : collect();
        $displayRows = $isLocked
            ? $savedAwards->map(function ($award): array {
                $snapshot = json_decode($award->snapshot, true) ?: [];
                return ['name' => $award->courier_name, 'points' => $snapshot['points'] ?? [], 'amounts' => ['productivity' => $award->productivity_amount, 'speed' => $award->speed_amount, 'presence' => $award->presence_amount, 'professionalism' => $award->professionalism_amount], 'total' => $award->total_amount];
            })
            : collect($result['points'] ?? [])->map(fn ($point, $id): array => ['name' => $point['courier']->name, 'points' => $point, 'amounts' => $result['awards'][$id], 'total' => array_sum($result['awards'][$id])])->values();
        $filteredResultRows = collect($displayRows)->filter(fn ($row) => $courierSearch === '' || str_contains(mb_strtolower($row['name']), mb_strtolower($courierSearch)))->values();
        $resultPageNumber = LengthAwarePaginator::resolveCurrentPage('result_page');
        $resultPage = new LengthAwarePaginator(
            $filteredResultRows->forPage($resultPageNumber, 12)->values(),
            $filteredResultRows->count(),
            12,
            $resultPageNumber,
            ['path' => $request->url(), 'pageName' => 'result_page', 'query' => $request->query()]
        );
        $ontimeCount = collect($displayRows)->sum(fn ($row) => (int) ($row['points']['speed'] ?? 0));
        if ($isLocked && isset($periodSnapshot['ineligible_orders'])) $ineligibleOrders = collect($periodSnapshot['ineligible_orders']);
        else $ineligibleOrders = $ineligibleOrders->map(fn (Order $order) => ['code' => $order->code, 'courier' => $order->user?->name ?? 'Livreur inconnu', 'reason' => $order->bonus_exclusion_reason]);
        $deliveredCount = $isLocked ? (int) ($periodSnapshot['delivered_count'] ?? $lockedDeliveryIds->count()) : $deliveredOrders->count();
        $lateOrders = $result ? $result['orders']->filter(fn (Order $order) => ! $order->courier_departed_at || $order->courier_departed_at->diffInSeconds($order->delivered_at) > 45 * 60) : collect();
        $audits = $week ? DB::table('courier_bonus_audits')->leftJoin('users', 'users.id', '=', 'courier_bonus_audits.actor_id')->where('week_id', $week->id)->orderByDesc('courier_bonus_audits.id')->limit(30)->get(['courier_bonus_audits.*', 'users.name as actor_name']) : collect();

        return view('courier-bonuses.index', compact('week', 'weeks', 'couriers', 'courierTotal', 'courierSearch', 'attendance', 'ratings', 'exceptions', 'result', 'lateOrders', 'audits', 'savedAwards', 'isLocked', 'lockedTotal', 'lockedDeliveryIds', 'resultPage', 'ineligibleOrders', 'ontimeCount', 'deliveredCount', 'periodSnapshot', 'autoPresence'));
    }

    public function storeWeek(Request $request)
    {
        $data = $request->validate(['week_start' => ['required', 'date'], 'budget' => ['required', 'integer', 'min:0', 'max:15000']]);
        $start = Carbon::parse($data['week_start'])->startOfDay();
        abort_unless($start->isMonday(), 422, 'La semaine de prime doit commencer un lundi.');
        $end = $start->copy()->addDays(5);
        $existing = DB::table('courier_bonus_weeks')->where('week_start', $start->toDateString())->first();
        if ($existing) return redirect()->route('courier-bonuses.index', ['week' => $start->toDateString()])->with('success', 'Cette semaine existe déjà.');
        $id = DB::table('courier_bonus_weeks')->insertGetId(['week_start' => $start->toDateString(), 'week_end' => $end->toDateString(), 'budget' => $data['budget'], 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit($id, 'week_created', 'Création de la semaine de primes.', null, ['budget' => $data['budget']]);
        return redirect()->route('courier-bonuses.index', ['week' => $start->toDateString()])->with('success', 'Semaine créée. Renseigne les présences et les notes avant validation.');
    }

    public function saveInputs(Request $request, int $week)
    {
        $period = $this->editableWeek($week);
        $data = $request->validate([
            'correction_reason' => ['required', 'string', 'min:3', 'max:500'],
            'attendance' => ['required', 'array', 'min:1', 'max:1'], 'attendance.*' => ['required', 'array', 'min:6', 'max:6'],
            'attendance.*.*.status' => ['required', 'in:present,absent,authorized_leave'],
            'attendance.*.*.reason' => ['nullable', 'string', 'max:500'],
            'ratings' => ['required', 'array', 'min:1', 'max:1'], 'ratings.*' => ['required', 'array'],
            'ratings.*.politeness' => ['required', 'integer', 'between:0,2'], 'ratings.*.presentation' => ['required', 'integer', 'between:0,2'],
            'ratings.*.instructions' => ['required', 'integer', 'between:0,2'], 'ratings.*.money_handling' => ['required', 'integer', 'between:0,2'],
            'ratings.*.discipline' => ['required', 'integer', 'between:0,2'],
        ]);
        $attendanceCourierIds = array_keys($data['attendance']);
        $ratingCourierIds = array_keys($data['ratings']);
        abort_unless($attendanceCourierIds === $ratingCourierIds, 422, 'Enregistre les présences et l’évaluation du même livreur dans cette fiche.');
        DB::transaction(function () use ($request, $period, $data): void {
            foreach (($data['attendance'] ?? []) as $courierId => $days) {
                abort_unless(User::query()->whereKey($courierId)->where('user_type', User::ROLE_LIVREUR)->where('active', true)->exists(), 422);
                foreach ($days as $date => $record) {
                    abort_unless($date >= $period->week_start && $date <= $period->week_end, 422);
                    if ($record['status'] !== 'present' && blank($record['reason'] ?? null)) {
                        $courierName = User::query()->whereKey($courierId)->value('name') ?: 'ce livreur';
                        $dayLabel = Carbon::parse($date)->locale('fr')->isoFormat('dddd D MMMM');
                        throw ValidationException::withMessages([
                            'attendance' => 'Indique le motif de '.($record['status'] === 'absent' ? 'l’absence' : 'du congé autorisé').' de '.$courierName.' pour le '.$dayLabel.'.',
                        ]);
                    }
                    $old = DB::table('courier_bonus_attendances')->where('week_id', $period->id)->where('courier_id', $courierId)->where('attendance_date', $date)->first();
                    DB::table('courier_bonus_attendances')->updateOrInsert(
                        ['week_id' => $period->id, 'courier_id' => $courierId, 'attendance_date' => $date],
                        ['status' => $record['status'], 'reason' => $record['reason'] ?? null, 'recorded_by' => $request->user()->id, 'updated_at' => now(), 'created_at' => $old->created_at ?? now()]
                    );
                    if (! $old || $old->status !== $record['status'] || $old->reason !== ($record['reason'] ?? null)) {
                        $this->audit($period->id, 'attendance_corrected', 'Correction de présence pour le livreur #'.$courierId.' le '.$date.'. Motif : '.$data['correction_reason'], (array) $old, $record);
                    }
                }
            }
            foreach (($data['ratings'] ?? []) as $courierId => $rating) {
                abort_unless(User::query()->whereKey($courierId)->where('user_type', User::ROLE_LIVREUR)->where('active', true)->exists(), 422);
                $old = DB::table('courier_bonus_ratings')->where('week_id', $period->id)->where('courier_id', $courierId)->first();
                DB::table('courier_bonus_ratings')->updateOrInsert(['week_id' => $period->id, 'courier_id' => $courierId], $rating + ['recorded_by' => $request->user()->id, 'updated_at' => now(), 'created_at' => $old->created_at ?? now()]);
                if (! $old || collect($rating)->diffAssoc((array) $old)->isNotEmpty()) $this->audit($period->id, 'rating_corrected', 'Note professionnelle corrigée pour le livreur #'.$courierId.'. Motif : '.$data['correction_reason'], (array) $old, $rating);
            }
        });
        return back()->with('success', 'Présences et évaluations enregistrées.');
    }

    public function saveLateException(Request $request, int $week, Order $order, CourierWeeklyBonusCalculator $calculator)
    {
        $period = $this->editableWeek($week);
        $data = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        $eligibleOrder = $calculator->eligibleOrders($period)->firstWhere('id', $order->id);
        abort_unless($eligibleOrder, 404);
        abort_if(! $eligibleOrder->courier_departed_at || ! $eligibleOrder->delivered_at
            || $eligibleOrder->courier_departed_at->greaterThanOrEqualTo($eligibleOrder->delivered_at)
            || $eligibleOrder->courier_departed_at->diffInSeconds($eligibleOrder->delivered_at) <= 45 * 60,
            422, 'Cette commande ne présente pas de retard mesurable supérieur à 45 minutes.');
        DB::table('courier_bonus_late_exceptions')->updateOrInsert(
            ['week_id' => $period->id, 'order_id' => $order->id],
            ['reason' => $data['reason'], 'validated_by' => $request->user()->id, 'validated_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
        $this->audit($period->id, 'late_exception_validated', 'Retard exceptionnel validé pour '.$order->code, null, ['order_id' => $order->id, 'reason' => $data['reason']]);
        return back()->with('success', 'Exception de retard validée.');
    }

    public function validateWeek(Request $request, int $week, CourierWeeklyBonusCalculator $calculator)
    {
        $period = $this->editableWeek($week);
        abort_if(Carbon::parse($period->week_end)->endOfDay()->isFuture(), 422, 'La semaine doit être terminée avant sa validation.');
        $couriers = User::query()->where('user_type', User::ROLE_LIVREUR)->where('active', true)->get();
        foreach ($couriers as $courier) {
            $days = DB::table('courier_bonus_attendances')->where('week_id', $period->id)->where('courier_id', $courier->id)->whereBetween('attendance_date', [$period->week_start, $period->week_end])->count();
            abort_unless($days === 6, 422, 'Il manque une ou plusieurs présences du lundi au samedi pour '.$courier->name.'.');
            abort_unless(DB::table('courier_bonus_ratings')->where('week_id', $period->id)->where('courier_id', $courier->id)->exists(), 422, 'Il manque l’évaluation de professionnalisme de '.$courier->name.'.');
        }
        $result = $calculator->calculate($period);
        $allDelivered = $calculator->deliveredOrders($period);
        $eligibleIds = $result['orders']->pluck('id')->flip();
        $excluded = $allDelivered->reject(fn (Order $order) => $eligibleIds->has($order->id))->map(function (Order $order): array {
            $due = max(0, (int) ($order->montant ?? ((int) $order->total + (int) $order->tax)));
            $confirmed = (int) $order->payments->whereIn('status', ['collected', 'confirmed'])->sum('amount');
            $issues = [];
            if ($order->delivery_mode === 'expedition' && $order->deliveryPhotos->isEmpty()) $issues[] = 'preuve de l’expédition absente';
            if ((int) $order->paid_amount < $due || $confirmed < $due) $issues[] = 'encaissement incomplet';
            return ['code' => $order->code, 'courier' => $order->user?->name ?? 'Livreur inconnu', 'reason' => implode(' · ', $issues)];
        })->values()->all();
        $snapshot = ['delivered_count' => $allDelivered->count(), 'eligible_count' => $result['orders']->count(), 'ineligible_orders' => $excluded];
        $previousAwards = DB::table('courier_bonus_awards')->where('week_id', $period->id)->get()->map(fn ($award) => ['courier' => $award->courier_name, 'productivity' => $award->productivity_amount, 'speed' => $award->speed_amount, 'presence' => $award->presence_amount, 'professionalism' => $award->professionalism_amount, 'total' => $award->total_amount])->all();
        $newAwards = collect($result['awards'])->map(function ($amounts, $courierId) use ($result): array {
            return ['courier' => $result['points'][$courierId]['courier']->name, ...$amounts, 'total' => array_sum($amounts)];
        })->values()->all();
        DB::transaction(function () use ($period, $request, $result, $snapshot, $previousAwards, $newAwards): void {
            DB::table('courier_bonus_awards')->where('week_id', $period->id)->delete();
            foreach ($result['awards'] as $courierId => $amounts) {
                $point = $result['points'][$courierId];
                DB::table('courier_bonus_awards')->insert([
                    'week_id' => $period->id, 'courier_id' => $courierId, 'courier_name' => $point['courier']->name,
                    'productivity_amount' => $amounts['productivity'], 'speed_amount' => $amounts['speed'], 'presence_amount' => $amounts['presence'],
                    'professionalism_amount' => $amounts['professionalism'], 'total_amount' => array_sum($amounts),
                    'snapshot' => json_encode(['points' => array_intersect_key($point, array_flip(['productivity', 'speed', 'presence', 'professionalism', 'orders'])), 'distributed' => $result['distributed']], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('courier_bonus_weeks')->where('id', $period->id)->update(['status' => 'manager_validated', 'manager_validated_by' => $request->user()->id, 'manager_validated_at' => now(), 'result_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            $this->audit($period->id, $previousAwards ? 'calculation_revalidated' : 'manager_validated', 'Le responsable a validé les données et le calcul.', $previousAwards ?: null, ['distributed' => $result['distributed'], 'eligible_deliveries' => $result['orders']->count(), 'awards' => $newAwards]);
        });
        return back()->with('success', 'Calcul verrouillé pour décision de la Direction Générale.');
    }

    public function decide(Request $request, int $week)
    {
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'decision_note' => ['nullable', 'string', 'max:1000']]);
        if ($data['decision'] === 'rejected') $request->validate(['decision_note' => ['required', 'string', 'min:5', 'max:1000']]);
        $period = DB::table('courier_bonus_weeks')->where('id', $week)->first();
        abort_unless($period, 404);
        abort_unless($period->status === 'manager_validated', 409, 'Seules les semaines vérifiées par le responsable peuvent être décidées.');
        DB::transaction(function () use ($period, $request, $data): void {
            DB::table('courier_bonus_weeks')->where('id', $period->id)->update(['status' => $data['decision'], 'decided_by' => $request->user()->id, 'decided_at' => now(), 'decision_note' => $data['decision_note'] ?? null, 'updated_at' => now()]);
            $this->audit($period->id, $data['decision'], $data['decision'] === 'approved' ? 'Prime approuvée par la Direction Générale.' : $data['decision_note'], ['status' => $period->status], ['status' => $data['decision']]);
        });
        return back()->with('success', $data['decision'] === 'approved' ? 'Primes approuvées. La semaine est désormais verrouillée.' : 'Calcul rejeté. Le responsable peut corriger puis soumettre à nouveau.');
    }

    public function export(int $week): StreamedResponse
    {
        $period = DB::table('courier_bonus_weeks')->where('id', $week)->first();
        abort_unless($period, 404);
        $rows = DB::table('courier_bonus_awards')->join('users', 'users.id', '=', 'courier_bonus_awards.courier_id')->where('week_id', $week)->orderBy('users.name')->get(['users.name', 'productivity_amount', 'speed_amount', 'presence_amount', 'professionalism_amount', 'total_amount']);
        return response()->streamDownload(function () use ($rows): void {
            $file = fopen('php://output', 'w');
            fprintf($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Livreur', 'Productivité (F CFA)', 'Rapidité (F CFA)', 'Présence (F CFA)', 'Professionnalisme (F CFA)', 'Total (F CFA)'], ';');
            foreach ($rows as $row) fputcsv($file, array_values((array) $row), ';');
            fclose($file);
        }, 'primes-livreurs-'.$period->week_start.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function editableWeek(int $id): object
    {
        $week = DB::table('courier_bonus_weeks')->where('id', $id)->first();
        abort_unless($week, 404);
        abort_if(in_array($week->status, ['approved', 'manager_validated'], true), 409, 'Cette période est verrouillée jusqu’à sa décision par la Direction Générale.');
        return $week;
    }

    private function audit(int $weekId, string $event, ?string $reason, ?array $before, ?array $after): void
    {
        DB::table('courier_bonus_audits')->insert(['week_id' => $weekId, 'actor_id' => auth()->id(), 'event' => $event, 'reason' => $reason, 'before_data' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null, 'after_data' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null, 'created_at' => now(), 'updated_at' => now()]);
    }
}
