<?php

namespace App\Http\Livewire;

use App\Models\Costumer;
use App\Models\CostumerContactSetting;
use App\Models\CustomerServiceCase;
use App\Models\CustomerServiceCaseActivity;
use App\Models\CustomerServiceCaseAttachment;
use App\Models\CustomerServiceSupportMilestone;
use App\Models\CustomerServiceSupportPlan;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CustomerServiceCases extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'statusFilter', 'typeFilter', 'priorityFilter', 'dateFrom', 'dateTo', 'dashboardFrom', 'dashboardTo', 'dashboardAssignee'];

    public $caseId;
    public $search = '';
    public $statusFilter = 'open';
    public $typeFilter = '';
    public $priorityFilter = '';
    public $assigneeFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $dashboardFrom = '';
    public $dashboardTo = '';
    public $dashboardAssignee = '';

    public $showCreateModal = false;
    public $showActivityForm = false;
    public $showResolveForm = false;
    public $showCloseForm = false;
    public $showAssignForm = false;
    public $showPriorityForm = false;

    public $customerSearch = '';
    public $selectedCustomerId = '';
    public $createCustomer = false;
    public $newCustomerName = '';
    public $newCustomerPhone = '';
    public $newCustomerEmail = '';
    public $newCustomerAddress = '';
    public $newCaseType = 'complaint';
    public $newProductId = '';
    public $newOrderId = '';
    public $newPurchaseDate = '';
    public $newDescription = '';
    public $newPriority = 'normal';
    public $newAssigneeId = '';
    public $newNextFollowUpAt = '';
    public $supportNeed = '';
    public $supportObjectives = '';
    public $supportStartDate = '';
    public $milestoneDays = [3, 7, 15, 30];
    public $uploads = [];

    public $activityType = 'internal_note';
    public $activityChannel = '';
    public $activityBody = '';
    public $newStatus = '';
    public $statusComment = '';
    public $resolution = '';
    public $resolutionResult = '';
    public $closureReason = '';
    public $nextFollowUpAt = '';
    public $assignedTo = '';
    public $milestoneObservation = '';
    public $completingMilestoneId = '';
    public $milestoneDates = [];
    public $satisfactionRating = '';
    public $satisfactionComment = '';

    public function mount(?int $caseId = null): void
    {
        $this->caseId = $caseId;
        $this->selectedCustomerId = (string) request()->query('customer', '');
        $this->newAssigneeId = Auth::id();
        $this->supportStartDate = now()->format('Y-m-d');
        $this->newNextFollowUpAt = now()->addDays(1)->format('Y-m-d\TH:i');
        if ($caseId) {
            $case = $this->authorizedCase($caseId);
            $this->nextFollowUpAt = $case->next_follow_up_at?->format('Y-m-d\TH:i') ?: now()->addDay()->format('Y-m-d\TH:i');
            foreach ($case->supportPlan?->milestones ?? [] as $milestone) {
                $this->milestoneDates[$milestone->id] = $milestone->due_at->format('Y-m-d\TH:i');
            }
        } elseif ($this->selectedCustomerId) {
            $customer = Costumer::find($this->selectedCustomerId);
            if ($customer) {
                $this->showCreateModal = true;
                $this->customerSearch = $customer->name.' — '.$customer->phone;
            } else {
                $this->selectedCustomerId = '';
            }
        }
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingPriorityFilter(): void { $this->resetPage(); }
    public function updatingAssigneeFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function showOpenCases(): void
    {
        $this->statusFilter = 'open';
        $this->priorityFilter = '';
        $this->resetPage();
    }

    public function showUrgentCases(): void
    {
        $this->statusFilter = 'open';
        $this->priorityFilter = 'urgent';
        $this->resetPage();
    }

    public function showOverdueCases(): void
    {
        $this->statusFilter = 'overdue';
        $this->priorityFilter = '';
        $this->resetPage();
    }

    public function showCompletedCases(): void
    {
        $this->statusFilter = 'completed';
        $this->priorityFilter = '';
        $this->resetPage();
    }

    public function updatedSelectedCustomerId($value): void
    {
        $customer = $value ? Costumer::find($value) : null;
        $this->customerSearch = $customer ? $customer->name.' — '.$customer->phone : '';
        $this->newOrderId = '';
        $this->newProductId = '';
        $this->newPurchaseDate = '';
    }

    public function updatedNewCaseType(): void
    {
        if ($this->newCaseType === 'personalized_support') {
            $this->supportStartDate = now()->format('Y-m-d');
        }
    }

    public function openCreateModal(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->closeAllModals();
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function openActivityModal(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if($case->status === 'closed', 403);
        $this->closeAllModals();
        $this->showActivityForm = true;
    }

    public function openResolveModal(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if(in_array($case->status, ['closed', 'resolved'], true), 403);
        $this->closeAllModals();
        $this->showResolveForm = true;
    }

    public function openCloseModal(): void
    {
        abort_unless($this->canManage(), 403);
        $case = $this->authorizedCase((int) $this->caseId);
        abort_unless($case->status === 'resolved', 422);
        $this->closeAllModals();
        $this->showCloseForm = true;
    }

    public function closeAllModals(): void
    {
        $this->showCreateModal = false;
        $this->showActivityForm = false;
        $this->showResolveForm = false;
        $this->showCloseForm = false;
        $this->showAssignForm = false;
        $this->showPriorityForm = false;
    }

    public function updatedCustomerSearch(): void
    {
        $this->selectedCustomerId = '';
        $this->createCustomer = false;
    }

    public function chooseCustomer(int $customerId): void
    {
        $customer = Costumer::findOrFail($customerId);
        $this->selectedCustomerId = (string) $customer->id;
        $this->customerSearch = $customer->name.' — '.$customer->phone;
        $this->createCustomer = false;
        $this->newOrderId = '';
        $this->newProductId = '';
        $this->newPurchaseDate = '';
    }

    public function startNewCustomer(): void
    {
        $this->selectedCustomerId = '';
        $this->createCustomer = true;
        $this->newCustomerName = trim($this->customerSearch);
        $this->newCustomerPhone = '';
        $this->newCustomerEmail = '';
        $this->newCustomerAddress = '';
    }

    public function updatedNewOrderId($value): void
    {
        $order = $value ? $this->selectedCustomerOrders()->firstWhere('id', (int) $value) : null;
        if ($order) {
            $this->newPurchaseDate = Carbon::parse($order->date_order ?: $order->created_at)->format('Y-m-d');
            $this->newProductId = (string) optional($order->orderItems->first())->product_id;
        }
    }

    public function createCase()
    {
        abort_unless($this->canCreate(), 403);

        $rules = [
            'newCaseType' => ['required', Rule::in(['complaint', 'dissatisfied', 'personalized_support', 'information'])],
            'newProductId' => ['nullable', 'exists:products,id'],
            'newOrderId' => ['nullable', 'exists:orders,id'],
            'newPurchaseDate' => ['nullable', 'date'],
            'newDescription' => ['required', 'string', 'min:8', 'max:10000'],
            'newPriority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'newAssigneeId' => ['nullable', 'exists:users,id'],
            'newNextFollowUpAt' => ['nullable', 'date'],
            'uploads' => ['array', 'max:5'],
            'uploads.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];

        if ($this->createCustomer) {
            $rules += [
                'newCustomerName' => ['required', 'string', 'max:255'],
                'newCustomerPhone' => ['required', 'string', 'max:60'],
                'newCustomerEmail' => ['nullable', 'email', 'max:255'],
                'newCustomerAddress' => ['nullable', 'string', 'max:255'],
            ];
        } else {
            $rules['selectedCustomerId'] = ['required', 'exists:costumers,id'];
        }

        if ($this->newCaseType === 'personalized_support') {
            $rules += [
                'supportNeed' => ['required', 'string', 'min:4', 'max:5000'],
                'supportObjectives' => ['nullable', 'string', 'max:5000'],
                'supportStartDate' => ['required', 'date'],
                'milestoneDays' => ['required', 'array', 'min:1'],
                'milestoneDays.*' => ['required', 'integer', 'min:1', 'max:365'],
            ];
        }

        $this->validate($rules);

        if ($this->canAssignCases() && $this->newAssigneeId && ! User::whereKey($this->newAssigneeId)->where('active', 1)->whereIn('user_type', ['CALLCENTER', 'MNG', 'SCR', 'ADMINUSER'])->exists()) {
            $this->addError('newAssigneeId', 'Choisissez un membre actif du service client.');
            return;
        }
        if ($this->newOrderId && $this->newProductId) {
            $orderContainsProduct = OrderItem::where('order_id', $this->newOrderId)->where('product_id', $this->newProductId)->exists();
            if (! $orderContainsProduct) {
                $this->addError('newProductId', 'Choisissez un produit de la commande sélectionnée.');
                return;
            }
        }

        $assigneeId = $this->canAssignCases() && $this->newAssigneeId ? (int) $this->newAssigneeId : Auth::id();
        $case = DB::transaction(function () use ($assigneeId) {
            $customer = $this->createCustomer
                ? $this->findOrCreateCustomer()
                : Costumer::findOrFail($this->selectedCustomerId);

            $order = null;
            if ($this->newOrderId) {
                $order = Order::where('id', $this->newOrderId)
                    ->where('costumer_id', (string) $customer->id)
                    ->firstOrFail();
            }

            $case = CustomerServiceCase::create([
                // The column is 32 characters; keep the unique placeholder within that limit.
                'case_number' => 'TMP-'.Str::random(24),
                'costumer_id' => $customer->id,
                'order_id' => $order?->id,
                'product_id' => $this->newProductId ?: optional($order?->orderItems()->first())->product_id,
                'case_type' => $this->newCaseType,
                'purchase_date' => $this->newPurchaseDate ?: ($order ? ($order->date_order ?: $order->created_at) : null),
                'description' => trim($this->newDescription),
                'priority' => $this->newPriority,
                'status' => 'new',
                'assigned_to' => $assigneeId,
                'created_by' => Auth::id(),
                'next_follow_up_at' => $this->newNextFollowUpAt ?: null,
            ]);
        $case->update(['case_number' => 'TEN-SC-'.now()->format('Y').'-'.str_pad((string) $case->id, 4, '0', STR_PAD_LEFT)]);

            $activity = $case->activities()->create([
                'user_id' => Auth::id(), 'activity_type' => 'created', 'body' => 'Dossier créé. '.trim($this->newDescription),
                'internal' => true, 'occurred_at' => now(),
            ]);

            if ((int) $case->assigned_to !== (int) Auth::id()) {
                $assigneeName = User::whereKey($case->assigned_to)->value('name') ?: 'un membre du service client';
                $case->activities()->create([
                    'user_id' => Auth::id(), 'activity_type' => 'assignment',
                    'body' => 'Dossier attribué à '.$assigneeName.' lors de sa création.',
                    'internal' => true, 'occurred_at' => now(),
                ]);
            }

            if ($case->case_type === 'personalized_support') {
                $plan = CustomerServiceSupportPlan::create([
                    'case_id' => $case->id,
                    'customer_need' => trim($this->supportNeed),
                    'objectives' => trim($this->supportObjectives) ?: null,
                    'started_at' => $this->supportStartDate,
                ]);
                $baseDate = Carbon::parse($this->supportStartDate)->startOfDay();
                foreach (collect($this->milestoneDays)->map(fn ($day) => (int) $day)->unique()->sort() as $day) {
                    $plan->milestones()->create(['day_offset' => $day, 'due_at' => $baseDate->copy()->addDays($day)->setTime(9, 0)]);
                }
            }

            $this->storeUploads($case, $activity);
            return $case;
        });

        $this->notifyCaseAudience($case, 'created', 'Un nouveau dossier '.$case->case_number.' a été créé pour '.$case->customer->name.'.', ['ADMINUSER', 'MNG', 'SCR']);
        if ((int) $case->assigned_to !== (int) Auth::id()) {
            $assignee = User::where('active', 1)->find($case->assigned_to);
            if ($assignee && $assignee->user_type === 'CALLCENTER') {
                $assignee->notify(new CustomerServiceCaseNotification($case, 'assigned', 'Le dossier '.$case->case_number.' vous a été attribué.'));
            }
        }

        $this->closeAllModals();
        $this->resetCreateForm();
        session()->flash('serviceCaseMessage', 'Le dossier '.$case->case_number.' a été créé.');
        return redirect()->route('service-cases.show', $case->id);
    }

    public function saveActivity(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if(in_array($case->status, ['closed'], true), 403);
        $this->validate([
            'activityType' => ['required', Rule::in(['internal_note', 'communication'])],
            'activityChannel' => ['required_if:activityType,communication', 'nullable', Rule::in(['whatsapp', 'call', 'sms', 'email', 'visit', 'other'])],
            'activityBody' => ['required', 'string', 'min:2', 'max:10000'],
            'uploads' => ['array', 'max:5'],
            'uploads.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $activity = $case->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => $this->activityType,
            'channel' => $this->activityType === 'communication' ? $this->activityChannel : null,
            'body' => trim($this->activityBody),
            'internal' => true,
            'occurred_at' => now(),
        ]);
        $this->storeUploads($case, $activity);
        $this->reset(['activityBody', 'uploads']);
        $this->activityType = 'internal_note';
        $this->activityChannel = '';
        $this->closeAllModals();
        session()->flash('serviceCaseMessage', 'L’intervention a été ajoutée à l’historique.');
    }

    public function changeStatus(string $status): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if(in_array($case->status, ['closed', 'resolved'], true), 403);
        $allowed = $this->allowedTransitions($case->status);
        $this->validate([
            'newStatus' => ['required', Rule::in($allowed)],
            'statusComment' => ['required', 'string', 'min:3', 'max:5000'],
            'resolution' => [$status === 'resolved' ? 'required' : 'nullable', 'string', 'min:4', 'max:10000'],
            'resolutionResult' => [$status === 'resolved' ? 'required' : 'nullable', 'string', 'min:4', 'max:10000'],
        ]);
        abort_unless($status === $this->newStatus, 422);
        abort_if($status === 'resolved' && ! $this->canManage(), 403, 'La validation de la résolution est réservée à un responsable.');

        $oldStatus = $case->status;
        $case->status = $status;
        if ($status === 'resolved') {
            $case->resolution = trim($this->resolution);
            $case->resolution_result = trim($this->resolutionResult);
            $case->resolved_at = now();
        }
        $case->save();
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'status_change', 'old_status' => $oldStatus,
            'new_status' => $status, 'body' => trim($this->statusComment), 'internal' => true, 'occurred_at' => now(),
        ]);
        if ($status === 'resolved') {
            $this->notifyCaseAudience($case, 'resolved', 'Le dossier '.$case->case_number.' a été marqué comme résolu et attend sa clôture.', ['ADMINUSER', 'MNG', 'SCR']);
        }
        $this->reset(['statusComment', 'resolution', 'resolutionResult']);
        $this->closeAllModals();
        session()->flash('serviceCaseMessage', 'Le statut du dossier a été mis à jour.');
    }

    public function saveFollowUp(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if(in_array($case->status, ['closed', 'resolved'], true), 403);
        $this->validate([
            'nextFollowUpAt' => ['required', 'date', 'after_or_equal:today'],
            'statusComment' => ['nullable', 'string', 'max:5000'],
        ]);
        $case->next_follow_up_at = $this->nextFollowUpAt;
        $case->save();
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'follow_up_scheduled',
            'body' => 'Prochain suivi fixé au '.Carbon::parse($this->nextFollowUpAt)->format('d/m/Y à H:i').($this->statusComment ? ' — '.trim($this->statusComment) : ''),
            'internal' => true, 'occurred_at' => now(),
        ]);
        $this->reset('statusComment');
        session()->flash('serviceCaseMessage', 'Le prochain suivi est programmé.');
    }

    public function openAssignForm(): void
    {
        abort_unless($this->canManage(), 403);
        $case = $this->authorizedCase((int) $this->caseId);
        $this->assignedTo = (string) ($case->assigned_to ?: '');
        $this->closeAllModals();
        $this->showAssignForm = true;
    }

    public function openPriorityForm(): void
    {
        abort_unless($this->canManage(), 403);
        $case = $this->authorizedCase((int) $this->caseId);
        $this->newPriority = $case->priority;
        $this->statusComment = '';
        $this->closeAllModals();
        $this->showPriorityForm = true;
    }

    public function startAnalysis(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_unless($case->status === 'new', 403);
        $this->newStatus = 'analysis';
        $this->statusComment = 'Prise en charge du dossier.';
        $this->closeAllModals();
        $this->showResolveForm = true;
    }

    public function updatePriority(): void
    {
        abort_unless($this->canManage(), 403);
        $case = $this->authorizedCase((int) $this->caseId);
        $this->validate([
            'newPriority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'statusComment' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $oldPriority = $case->priority;
        $case->priority = $this->newPriority;
        $case->save();
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'priority_change',
            'body' => 'Priorité modifiée : '.(['low'=>'Faible','normal'=>'Normale','high'=>'Élevée','urgent'=>'Urgente'][$oldPriority] ?? $oldPriority).' → '.(['low'=>'Faible','normal'=>'Normale','high'=>'Élevée','urgent'=>'Urgente'][$this->newPriority] ?? $this->newPriority).'. '.trim($this->statusComment),
            'internal' => true, 'occurred_at' => now(),
        ]);
        $this->closeAllModals();
        $this->reset('statusComment');
        session()->flash('serviceCaseMessage', 'La priorité a été modifiée et ajoutée à l’historique.');
    }

    public function assignCase(): void
    {
        abort_unless($this->canManage(), 403);
        $case = $this->authorizedCase((int) $this->caseId);
        $this->validate(['assignedTo' => ['required', 'exists:users,id']]);
        $user = User::whereIn('user_type', ['CALLCENTER', 'MNG', 'SCR', 'ADMINUSER'])->where('active', 1)->findOrFail($this->assignedTo);
        $previous = $case->assignee?->name ?: 'Non attribué';
        $case->assigned_to = $user->id;
        $case->save();
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'assignment', 'body' => 'Dossier attribué à '.$user->name.' (précédemment : '.$previous.').',
            'internal' => true, 'occurred_at' => now(),
        ]);
        if ($user->id !== Auth::id()) {
            $user->notify(new CustomerServiceCaseNotification($case, 'assigned', 'Le dossier '.$case->case_number.' vous a été attribué par '.Auth::user()->name.'.'));
        }
        $this->closeAllModals();
        session()->flash('serviceCaseMessage', 'Le dossier a été attribué à '.$user->name.'.');
    }

    public function closeCase(): void
    {
        abort_unless($this->canManage(), 403, 'La clôture est réservée à un responsable.');
        $case = $this->authorizedCase((int) $this->caseId);
        abort_unless($case->status === 'resolved', 422, 'Le dossier doit d’abord être marqué comme résolu.');
        $this->validate([
            'closureReason' => ['required', 'string', 'min:5', 'max:5000'],
            'resolutionResult' => ['required', 'string', 'min:4', 'max:10000'],
        ]);
        $case->update(['status' => 'closed', 'closure_reason' => trim($this->closureReason), 'resolution_result' => trim($this->resolutionResult), 'closed_at' => now()]);
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'status_change', 'old_status' => 'resolved', 'new_status' => 'closed',
            'body' => trim($this->closureReason), 'internal' => true, 'occurred_at' => now(),
        ]);
        $this->notifyCaseAudience($case, 'closed', 'Le dossier '.$case->case_number.' a été clôturé.', ['ADMINUSER', 'MNG', 'SCR']);
        $this->closeAllModals();
        session()->flash('serviceCaseMessage', 'Le dossier a été clôturé avec sa justification.');
    }

    public function completeMilestone(int $milestoneId): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if(in_array($case->status, ['closed'], true), 403);
        $milestone = CustomerServiceSupportMilestone::where('id', $milestoneId)
            ->whereHas('plan', fn ($query) => $query->where('case_id', $case->id))
            ->firstOrFail();
        $this->completingMilestoneId = (string) $milestoneId;
        $this->validate(['milestoneObservation' => ['required', 'string', 'min:2', 'max:5000']]);
        $milestone->update(['completed_at' => now(), 'completed_by' => Auth::id(), 'observation' => trim($this->milestoneObservation)]);
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'milestone',
            'body' => 'Étape du programme réalisée (échéance du '.$milestone->due_at->format('d/m/Y').') : '.trim($this->milestoneObservation),
            'internal' => true, 'occurred_at' => now(),
        ]);
        $this->reset(['milestoneObservation', 'completingMilestoneId']);
        session()->flash('serviceCaseMessage', 'L’étape d’accompagnement est enregistrée.');
    }

    public function updateMilestoneDate(int $milestoneId): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_if($case->status === 'closed', 403);
        $milestone = CustomerServiceSupportMilestone::where('id', $milestoneId)
            ->whereHas('plan', fn ($query) => $query->where('case_id', $case->id))
            ->whereNull('completed_at')->firstOrFail();
        $field = 'milestoneDates.'.$milestoneId;
        $this->validate([$field => ['required', 'date', 'after_or_equal:today']]);
        $newDueAt = Carbon::parse($this->milestoneDates[$milestoneId]);
        $oldDueAt = $milestone->due_at;
        $planStart = $case->supportPlan->started_at->startOfDay();
        $milestone->update([
            'due_at' => $newDueAt,
            'day_offset' => max(0, (int) $planStart->diffInDays($newDueAt->copy()->startOfDay(), false)),
        ]);
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'milestone_rescheduled',
            'body' => 'Échéance d’accompagnement déplacée du '.$oldDueAt->format('d/m/Y à H:i').' au '.$newDueAt->format('d/m/Y à H:i').'.',
            'internal' => true, 'occurred_at' => now(),
        ]);
        session()->flash('serviceCaseMessage', 'L’échéance a été modifiée et ajoutée à l’historique.');
    }

    public function saveSupportReview(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_unless($this->canManage(), 403);
        $this->validate(['supportObjectives' => ['required', 'string', 'min:4', 'max:10000']]);
        $plan = $case->supportPlan ?: abort(404);
        $plan->update(['final_review' => trim($this->supportObjectives), 'completed_at' => now()]);
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'support_review', 'body' => 'Bilan final : '.trim($this->supportObjectives),
            'internal' => true, 'occurred_at' => now(),
        ]);
        session()->flash('serviceCaseMessage', 'Le bilan de l’accompagnement est enregistré.');
    }

    public function saveSatisfaction(): void
    {
        $case = $this->authorizedCase((int) $this->caseId);
        abort_unless(in_array($case->status, ['resolved', 'closed'], true), 403);
        abort_if($case->customer_satisfaction !== null, 409, 'La satisfaction a déjà été enregistrée.');
        $this->validate([
            'satisfactionRating' => ['required', 'integer', 'between:1,5'],
            'satisfactionComment' => ['nullable', 'string', 'max:5000'],
        ]);
        $case->update([
            'customer_satisfaction' => (int) $this->satisfactionRating,
            'satisfaction_comment' => trim($this->satisfactionComment) ?: null,
        ]);
        $case->activities()->create([
            'user_id' => Auth::id(), 'activity_type' => 'satisfaction',
            'body' => 'Retour après traitement : '.$this->satisfactionRating.'/5'.($this->satisfactionComment ? ' — '.trim($this->satisfactionComment) : ''),
            'internal' => true, 'occurred_at' => now(),
        ]);
        $this->reset(['satisfactionRating', 'satisfactionComment']);
        session()->flash('serviceCaseMessage', 'Le retour de la cliente a été enregistré.');
    }

    protected function authorizedCase(int $id): CustomerServiceCase
    {
        $query = CustomerServiceCase::query()->with([
            'customer', 'product', 'order.orderItems.product', 'assignee', 'creator',
            'activities.user', 'activities.attachments.uploader', 'attachments.uploader',
            'supportPlan.milestones.completedBy',
        ])->whereKey($id);
        if (! $this->canManage()) {
            $query->where('assigned_to', Auth::id());
        }
        return $query->firstOrFail();
    }

    protected function caseContactLinks(?CustomerServiceCase $case): array
    {
        if (! $case || ! $case->customer) {
            return [];
        }

        $customer = $case->customer;
        $rawPhone = trim((string) $customer->phone);
        $digits = preg_replace('/\D+/', '', $rawPhone);
        if ($digits === '') {
            return [];
        }

        if (str_starts_with($rawPhone, '00')) {
            $phone = substr($digits, 2);
        } elseif (str_starts_with($rawPhone, '+')) {
            $phone = $digits;
        } else {
            $knownCallingCodes = [
                '1', '7', '20', '27', '33', '34', '39', '44', '49', '52', '55', '60', '61', '62', '63', '64', '65', '66',
                '81', '82', '84', '86', '90', '91', '92', '93', '94', '95', '98',
                '211', '212', '213', '216', '218', '220', '221', '222', '223', '224', '225', '226', '227', '228', '229',
                '230', '231', '232', '233', '234', '235', '236', '237', '238', '239', '240', '241', '242', '243', '244',
                '245', '246', '248', '249', '250', '251', '252', '253', '254', '255', '256', '257', '258', '260', '261',
                '262', '263', '264', '265', '266', '267', '268', '269',
            ];
            usort($knownCallingCodes, fn ($left, $right) => strlen($right) <=> strlen($left));
            $hasStoredInternationalCode = strlen($digits) >= 11 && collect($knownCallingCodes)
                ->contains(fn ($code) => str_starts_with($digits, $code));
            if ($hasStoredInternationalCode) {
                $phone = $digits;
            } else {
            $callingCode = $customer->contactPreference?->calling_code
                ?: CostumerContactSetting::query()->value('default_country_calling_code')
                ?: '+228';
            $callingCode = preg_replace('/\D+/', '', (string) $callingCode);
            $phone = $callingCode !== '' && ! str_starts_with($digits, $callingCode)
                ? $callingCode.$digits
                : $digits;
            }
        }

        $message = 'Bonjour '.$customer->name.', nous vous contactons au sujet de votre dossier '.$case->case_number;
        if ($case->product?->name) {
            $message .= ' concernant '.$case->product->name;
        }
        $message .= '. Nous sommes disponibles pour vous accompagner. TENACE COSMETIQUE';

        return [
            'whatsapp' => 'https://wa.me/'.$phone.'?text='.rawurlencode($message),
            'call' => 'tel:+'.$phone,
            'sms' => 'sms:+'.$phone.'?body='.rawurlencode($message),
            'email' => $customer->email ? 'mailto:'.$customer->email.'?body='.rawurlencode($message) : null,
        ];
    }

    protected function findOrCreateCustomer(): Costumer
    {
        $digits = preg_replace('/\D+/', '', $this->newCustomerPhone);
        $tail = substr($digits, -8);
        if ($tail !== '') {
            $matches = Costumer::where('phone', 'like', '%'.$tail)->limit(20)->get();
            foreach ($matches as $match) {
                if (preg_replace('/\D+/', '', $match->phone) === $digits) {
                    return $match;
                }
            }
        }
        if (trim($this->newCustomerEmail) !== '') {
            $existingByEmail = Costumer::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($this->newCustomerEmail))])->first();
            if ($existingByEmail) return $existingByEmail;
        }
        return Costumer::create([
            'name' => trim($this->newCustomerName), 'phone' => trim($this->newCustomerPhone),
            'email' => trim($this->newCustomerEmail) ?: null, 'adresse' => trim($this->newCustomerAddress) ?: 'Non renseignée',
            'user_id' => Auth::id(),
        ]);
    }

    protected function storeUploads(CustomerServiceCase $case, CustomerServiceCaseActivity $activity): void
    {
        foreach ($this->uploads ?: [] as $upload) {
            $path = $upload->store('customer-service/cases/'.$case->id, 'local');
            $case->attachments()->create([
                'activity_id' => $activity->id, 'uploaded_by' => Auth::id(), 'path' => $path,
                'original_name' => $upload->getClientOriginalName(), 'mime_type' => $upload->getMimeType(), 'size' => $upload->getSize(),
            ]);
        }
    }

    protected function notifyCaseAudience(CustomerServiceCase $case, string $event, string $message, array $roles): void
    {
        $recipients = User::whereIn('user_type', $roles)->where('active', 1)->get();
        if ($case->assigned_to) {
            $assignee = User::where('active', 1)->find($case->assigned_to);
            if ($assignee) $recipients->push($assignee);
        }
        foreach ($recipients->unique('id') as $recipient) {
            if ($recipient->id === Auth::id()) continue;
            $recipient->notify(new CustomerServiceCaseNotification($case, $event, $message));
        }
    }

    protected function resetCreateForm(): void
    {
        $this->reset([
            'customerSearch', 'selectedCustomerId', 'createCustomer', 'newCustomerName', 'newCustomerPhone', 'newCustomerEmail',
            'newCustomerAddress', 'newProductId', 'newOrderId', 'newPurchaseDate', 'newDescription', 'uploads', 'supportNeed', 'supportObjectives',
        ]);
        $this->newCaseType = 'complaint';
        $this->newPriority = 'normal';
        $this->newAssigneeId = Auth::id();
        $this->newNextFollowUpAt = now()->addDays(1)->format('Y-m-d\TH:i');
        $this->supportStartDate = now()->format('Y-m-d');
        $this->milestoneDays = [3, 7, 15, 30];
    }

    protected function canManage(): bool
    {
        return Auth::user()->hasRole(['ADMINUSER', 'MNG', 'SCR']);
    }

    protected function canAssignCases(): bool
    {
        return $this->canManage();
    }

    protected function canCreate(): bool
    {
        return Auth::user()->hasRole(['ADMINUSER', 'MNG', 'SCR', 'CALLCENTER']);
    }

    protected function visibleCases(): Builder
    {
        $query = $this->accessibleCases()->with(['customer', 'product', 'assignee']);
        if (trim($this->search) !== '') {
            $search = '%'.trim($this->search).'%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('case_number', 'like', $search)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }
        if ($this->statusFilter === 'open') {
            $query->whereNotIn('status', ['closed', 'resolved']);
        } elseif ($this->statusFilter === 'overdue') {
            $query->whereNotIn('status', ['closed', 'resolved'])->where(function (Builder $overdue) {
                $overdue->where(fn (Builder $scheduled) => $scheduled->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now()))
                    ->orWhereHas('supportPlan.milestones', fn (Builder $milestone) => $milestone->whereNull('completed_at')->where('due_at', '<', now()));
            });
        } elseif ($this->statusFilter === 'completed') {
            $query->whereIn('status', ['resolved', 'closed']);
        } elseif ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }
        if ($this->typeFilter !== '') $query->where('case_type', $this->typeFilter);
        if ($this->priorityFilter !== '') $query->where('priority', $this->priorityFilter);
        if ($this->canManage() && $this->assigneeFilter !== '') $query->where('assigned_to', $this->assigneeFilter);
        if ($this->dateFrom) $query->whereDate('created_at', '>=', $this->dateFrom);
        if ($this->dateTo) $query->whereDate('created_at', '<=', $this->dateTo);
        return $query;
    }

    protected function accessibleCases(): Builder
    {
        $query = CustomerServiceCase::query();
        if (! $this->canManage()) {
            $query->where('assigned_to', Auth::id());
        }
        return $query;
    }

    protected function selectedCustomerOrders()
    {
        if (! $this->selectedCustomerId) return collect();
        return Order::with('orderItems.product')
            ->where('costumer_id', (string) $this->selectedCustomerId)
            ->orderByDesc('date_order')->orderByDesc('created_at')->limit(20)->get();
    }

    protected function allowedTransitions(string $status): array
    {
        $transitions = [
            'new' => ['analysis'],
            'analysis' => ['waiting_customer', 'waiting_internal', 'support_in_progress', 'to_follow', 'resolved'],
            'waiting_customer' => ['analysis', 'waiting_internal', 'support_in_progress', 'to_follow', 'resolved'],
            'waiting_internal' => ['analysis', 'waiting_customer', 'support_in_progress', 'to_follow', 'resolved'],
            'support_in_progress' => ['analysis', 'waiting_customer', 'waiting_internal', 'to_follow', 'resolved'],
            'to_follow' => ['analysis', 'waiting_customer', 'waiting_internal', 'support_in_progress', 'resolved'],
        ];

        $allowed = $transitions[$status] ?? [];
        if (! $this->canManage()) {
            $allowed = array_values(array_diff($allowed, ['resolved']));
        }
        return $allowed;
    }

    public function render()
    {
        $case = $this->caseId ? $this->authorizedCase((int) $this->caseId) : null;
        if ($case && $case->supportPlan) {
            $this->supportObjectives = $case->supportPlan->final_review ?: $this->supportObjectives;
        }

        $customers = collect();
        if ($this->showCreateModal && strlen(trim($this->customerSearch)) >= 2 && ! $this->selectedCustomerId) {
            $term = trim($this->customerSearch).'%';
            $customers = Costumer::where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('email', 'like', $term)
                ->orderBy('name')->limit(8)->get(['id', 'name', 'phone', 'email']);
        }

        $caseRows = null;
        $counts = ['open' => 0, 'urgent' => 0, 'overdue' => 0, 'resolved' => 0];
        $dashboard = ['total' => 0, 'complaint' => 0, 'dissatisfied' => 0, 'support' => 0, 'information' => 0, 'closed' => 0, 'resolved_rate' => 0, 'average_resolution_hours' => null, 'average_satisfaction' => null, 'satisfied_rate' => null, 'ratings_count' => 0, 'by_assignee' => collect()];
        if (! $case) {
            $allVisible = $this->accessibleCases();
            $base = clone $allVisible;
            if ($this->dashboardFrom) $base->whereDate('created_at', '>=', $this->dashboardFrom);
            if ($this->dashboardTo) $base->whereDate('created_at', '<=', $this->dashboardTo);
            if ($this->canManage() && $this->dashboardAssignee !== '') $base->where('assigned_to', $this->dashboardAssignee);
            $dashboard['total'] = (clone $base)->count();
            $dashboard['complaint'] = (clone $base)->where('case_type', 'complaint')->count();
            $dashboard['dissatisfied'] = (clone $base)->where('case_type', 'dissatisfied')->count();
            $dashboard['support'] = (clone $base)->where('case_type', 'personalized_support')->count();
            $dashboard['information'] = (clone $base)->where('case_type', 'information')->count();
            $counts['open'] = (clone $allVisible)->whereNotIn('status', ['closed', 'resolved'])->count();
            $counts['urgent'] = (clone $allVisible)->where('priority', 'urgent')->whereNotIn('status', ['closed', 'resolved'])->count();
            $caseFollowUpsOverdue = (clone $allVisible)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())->whereNotIn('status', ['closed', 'resolved'])->count();
            $overdueMilestones = CustomerServiceSupportMilestone::whereNull('completed_at')->where('due_at', '<', now())
                ->whereHas('plan.customerServiceCase', function (Builder $query) {
                    $query->whereNotIn('status', ['closed', 'resolved']);
                    if (! $this->canManage()) {
                        $query->where('assigned_to', Auth::id());
                    }
                    if ($this->dashboardFrom) $query->whereDate('created_at', '>=', $this->dashboardFrom);
                    if ($this->dashboardTo) $query->whereDate('created_at', '<=', $this->dashboardTo);
                    if ($this->canManage() && $this->dashboardAssignee !== '') $query->where('assigned_to', $this->dashboardAssignee);
                })->count();
            $counts['overdue'] = $caseFollowUpsOverdue + $overdueMilestones;
            $counts['resolved'] = (clone $allVisible)->whereIn('status', ['resolved', 'closed'])->count();
            $dashboard['closed'] = (clone $base)->where('status', 'closed')->count();
            $dashboardResolved = (clone $base)->whereIn('status', ['resolved', 'closed'])->count();
            $dashboard['resolved_rate'] = $dashboard['total'] ? round($dashboardResolved / $dashboard['total'] * 100) : 0;
            $dashboard['average_resolution_hours'] = (clone $base)->whereNotNull('resolved_at')->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as average_hours')->value('average_hours');
            $rated = (clone $base)->whereNotNull('customer_satisfaction');
            $dashboard['ratings_count'] = (clone $rated)->count();
            $dashboard['average_satisfaction'] = (clone $rated)->avg('customer_satisfaction');
            $dashboard['satisfied_rate'] = (clone $rated)->count() ? round((clone $rated)->where('customer_satisfaction', '>=', 4)->count() / (clone $rated)->count() * 100) : null;
            $dashboard['by_assignee'] = (clone $base)->select('assigned_to')->selectRaw('COUNT(*) as case_count')
                ->with('assignee:id,name')->groupBy('assigned_to')->orderByDesc('case_count')->limit(5)->get();
            $caseRows = $this->visibleCases()->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
                ->orderByRaw("CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? AND status NOT IN ('closed','resolved') THEN 0 ELSE 1 END", [now()])
                ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
                ->orderByDesc('updated_at')->paginate(15);
        }

        return view('livewire.customer-service-cases', [
            'case' => $case,
            'caseContactLinks' => $this->caseContactLinks($case),
            'availableTransitions' => $case ? $this->allowedTransitions($case->status) : [],
            'caseRows' => $caseRows,
            'counts' => $counts,
            'dashboard' => $dashboard,
            'customers' => $customers,
            'customerOrders' => $this->selectedCustomerOrders(),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'assignees' => User::whereIn('user_type', ['CALLCENTER', 'MNG', 'SCR', 'ADMINUSER'])->where('active', 1)->orderBy('name')->get(['id', 'name', 'user_type']),
            'canManageCases' => $this->canManage(),
            'canAssignCases' => $this->canAssignCases(),
            'customerSelected' => $this->selectedCustomerId ? Costumer::find($this->selectedCustomerId) : null,
        ])->extends('layouts.admin')->section('content');
    }
}
