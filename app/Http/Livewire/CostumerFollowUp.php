<?php

namespace App\Http\Livewire;

use App\Models\Costumer;
use App\Models\CostumerContactHistory;
use App\Models\CostumerContactMessageTemplate;
use App\Models\CostumerContactPreference;
use App\Models\CostumerContactSetting;
use App\Models\CustomerServiceCase;
use App\Models\CustomerServiceSupportPlan;
use App\Models\Orders\Order;
use App\Models\User;
use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class CostumerFollowUp extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = 'to_follow_up';
    public $showContactModal = false;
    public $showResponseModal = false;
    public $showHistoryModal = false;
    public $showFeedbackModal = false;
    public $feedbackSentiment = '';
    public $showSettings = false;
    public $showDoNotContactModal = false;
    public $showDeleteTemplateModal = false;
    public $showScheduledDateModal = false;
    public $showFollowUpDecisionModal = false;
    public $pendingFollowUpDecision = '';
    public $selectedCostumerId;
    public $selectedHistoryId;
    public $scheduledDateCostumerId;
    public $scheduledFollowUpDate = '';
    public $deletingTemplateId;
    public $deletingTemplateName = '';
    public $contactType = 'initial';
    public $channel = 'whatsapp';
    public $channelDetail = '';
    public $contactedAt;
    public $followUpAt;
    public $notes = '';
    public $responseReceivedNow = false;
    public $immediateResponse = '';
    public $immediateSentiment = 'neutral';
    public $createServiceCase = false;
    public $quickServiceCaseType = 'dissatisfied';
    public $quickServiceCaseDescription = '';
    public $quickServiceCaseOrderId = '';
    public $quickServiceCaseProductId = '';
    public $quickServiceCaseProductIds = [];
    public $response = '';
    public $sentiment = 'neutral';
    public $individualCallingCode = '+228';
    public $doNotContactReason = '';
    public $newTemplateChannel = 'whatsapp';
    public $newTemplateName = '';
    public $newTemplateBody = '';
    public $selectedTemplateId;
    public $editingTemplateId;
    public $defaultFollowUpDays = 14;
    public $maxFollowUps = 3;
    public $followUpStartDate;
    public $defaultCountryCallingCode = '+228';
    public $selectedDate;
    public $followUpScope = 'today';

    protected $queryString = ['search', 'statusFilter', 'selectedDate', 'followUpScope'];

    protected $listeners = ['showFeedbackDetails' => 'openFeedbackDetails'];

    public function mount(): void
    {
        if (!$this->selectedDate) {
            $this->selectedDate = now()->format('Y-m-d');
        }
        $this->followUpStartDate = now()->format('Y-m-d');
        $this->contactedAt = now()->format('Y-m-d\TH:i');
        $this->loadSettings();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedDate(): void
    {
        $this->resetPage();
    }

    public function updatedFollowUpScope(): void
    {
        $this->resetPage();
    }

    public function setFollowUpScope(string $scope): void
    {
        $this->followUpScope = $scope;
        $this->resetPage();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->resetPage();
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate ?: now())->subDay()->format('Y-m-d');
        $this->resetPage();
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate ?: now())->addDay()->format('Y-m-d');
        $this->resetPage();
    }

    public function goToToday(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function updatedChannel(): void
    {
        $this->selectedTemplateId = CostumerContactMessageTemplate::where('channel', $this->channel)
            ->where('active', true)->value('id');
    }

    public function updatedResponseReceivedNow($value = null): void
    {
        $this->resetValidation($this->responseReceivedNow ? 'followUpAt' : 'immediateResponse');
        if (! $this->responseReceivedNow) {
            $this->createServiceCase = false;
        }
    }

    public function openContactModal(int $costumerId, string $type = 'initial'): void
    {
        $costumer = Costumer::with('contactPreference')->findOrFail($costumerId);
        abort_if($costumer->contactPreference && $costumer->contactPreference->do_not_contact_at, 403);
        $this->selectedCostumerId = $costumer->id;
        $this->contactType = $type === 'follow_up' ? 'follow_up' : 'initial';
        $this->channel = 'whatsapp';
        $this->channelDetail = '';
        $this->selectedTemplateId = CostumerContactMessageTemplate::where('channel', 'whatsapp')->where('active', true)->value('id');
        $this->individualCallingCode = $this->detectCallingCode($costumer->phone)
            ?? $costumer->contactPreference->calling_code
            ?? $this->defaultCountryCallingCode;
        $this->contactedAt = now()->format('Y-m-d\TH:i');
        $this->followUpAt = now()->addDays((int) $this->defaultFollowUpDays)->format('Y-m-d\TH:i');
        $this->notes = '';
        $this->responseReceivedNow = false;
        $this->immediateResponse = '';
        $this->immediateSentiment = 'neutral';
        $this->createServiceCase = false;
        $this->quickServiceCaseType = 'dissatisfied';
        $this->quickServiceCaseDescription = '';
        $latestOrder = Order::with('orderItems')
            ->where('costumer_id', (string) $costumer->id)
            ->orderByRaw('COALESCE(date_order, created_at) DESC')
            ->orderByDesc('id')
            ->first();
        $this->quickServiceCaseOrderId = (string) ($latestOrder?->id ?? '');
        $this->quickServiceCaseProductIds = $latestOrder?->orderItems
            ->pluck('product_id')->filter()->unique()->map(fn ($id) => (string) $id)->values()->all() ?? [];
        $this->quickServiceCaseProductId = $this->quickServiceCaseProductIds[0] ?? '';
        $this->showContactModal = true;
    }

    public function updatedQuickServiceCaseOrderId($orderId): void
    {
        $order = $orderId
            ? Order::with('orderItems')->where('costumer_id', (string) $this->selectedCostumerId)->find($orderId)
            : null;
        $this->quickServiceCaseProductIds = $order?->orderItems
            ->pluck('product_id')->filter()->unique()->map(fn ($id) => (string) $id)->values()->all() ?? [];
        $this->quickServiceCaseProductId = $this->quickServiceCaseProductIds[0] ?? '';
        $this->resetValidation('quickServiceCaseProductIds');
    }

    public function saveContact()
    {
        $rules = [
            'selectedCostumerId' => 'required|exists:costumers,id',
            'contactType' => 'required|in:initial,follow_up',
            'channel' => 'required|in:whatsapp,sms,call,other',
            'channelDetail' => $this->channel === 'other' ? 'required|string|max:120' : 'nullable|string|max:120',
            'contactedAt' => 'required|date',
            'responseReceivedNow' => 'boolean',
            'immediateResponse' => 'nullable|string|max:10000',
            'immediateSentiment' => 'required|in:positive,neutral,negative',
            'individualCallingCode' => ['required', 'regex:/^\\+?[0-9]{1,4}$/'],
            'selectedTemplateId' => 'nullable|exists:costumer_contact_message_templates,id',
            'notes' => 'nullable|string|max:5000',
        ];
        if ($this->createServiceCase) {
            abort_unless($this->canCreateQuickServiceCase(), 403);
            abort_unless($this->responseReceivedNow, 422);
            $rules['quickServiceCaseType'] = 'required|in:complaint,dissatisfied,personalized_support,information';
            $rules['quickServiceCaseOrderId'] = 'nullable|integer|exists:orders,id';
            $rules['quickServiceCaseProductIds'] = 'array';
            $rules['quickServiceCaseProductIds.*'] = 'integer|distinct|exists:products,id';
        }
        if ($this->responseReceivedNow) {
            $rules['immediateResponse'] = 'required|string|max:10000';
        } else {
            $rules['followUpAt'] = 'nullable|date|after:contactedAt';
        }
        $this->validate($rules);

        $serviceCaseOrder = null;
        if ($this->createServiceCase) {
            $serviceCaseOrder = $this->quickServiceCaseOrderId
                ? Order::with('orderItems')->where('costumer_id', (string) $this->selectedCostumerId)->find($this->quickServiceCaseOrderId)
                : null;
            if ($this->quickServiceCaseOrderId && ! $serviceCaseOrder) {
                $this->addError('quickServiceCaseOrderId', 'Choisis une commande appartenant à cette cliente.');
                return;
            }
            $selectedProductIds = collect($this->quickServiceCaseProductIds)->map(fn ($id) => (int) $id)->unique();
            $orderProductIds = $serviceCaseOrder?->orderItems->pluck('product_id')->map(fn ($id) => (int) $id)->unique() ?? collect();
            if ($selectedProductIds->diff($orderProductIds)->isNotEmpty()) {
                $this->addError('quickServiceCaseProductIds', 'Choisis uniquement des produits de la commande sélectionnée.');
                return;
            }
        }

        abort_if(CostumerContactPreference::where('costumer_id', $this->selectedCostumerId)->whereNotNull('do_not_contact_at')->exists(), 403);

        $callingCode = str_starts_with($this->individualCallingCode, '+')
            ? $this->individualCallingCode
            : '+'.$this->individualCallingCode;

        $followUpAt = $this->responseReceivedNow ? null : ($this->followUpAt ?: null);
        DB::transaction(function () use ($callingCode, $followUpAt) {
            CostumerContactPreference::updateOrCreate(
                ['costumer_id' => $this->selectedCostumerId],
                [
                    'calling_code' => $callingCode,
                    'next_follow_up_at' => $followUpAt,
                    'updated_by' => Auth::id(),
                ]
            );

            CostumerContactHistory::create([
                'costumer_id' => $this->selectedCostumerId,
                'user_id' => Auth::id(),
                'contact_type' => $this->contactType,
                'channel' => $this->channel,
                'channel_detail' => $this->channel === 'other' ? trim($this->channelDetail) : null,
                'contacted_at' => $this->contactedAt,
                'follow_up_at' => $followUpAt,
                'response' => $this->responseReceivedNow ? $this->immediateResponse : null,
                'sentiment' => $this->responseReceivedNow ? $this->immediateSentiment : null,
                'responded_at' => $this->responseReceivedNow ? now() : null,
                'message_template_id' => $this->selectedTemplateId,
                'notes' => $this->notes ?: null,
            ]);

        });

        $this->forgetFollowUpData();

        if ($this->createServiceCase) {
            $selectedProductIds = collect($this->quickServiceCaseProductIds)->map(fn ($id) => (int) $id)->unique()->values();
            session()->put('customer_service_case_prefill', [
                'type' => $this->quickServiceCaseType,
                'description' => trim((string) $this->immediateResponse),
                'order_id' => $serviceCaseOrder?->id,
                'product_ids' => $selectedProductIds->all(),
                'product_id' => $selectedProductIds->first(),
                'purchase_date' => $serviceCaseOrder ? Carbon::parse($serviceCaseOrder->date_order ?: $serviceCaseOrder->created_at)->format('Y-m-d') : null,
            ]);
            session()->flash('messages', 'Contact et réponse enregistrés. Complète maintenant le dossier SAV.');
            return redirect()->route('service-cases.index', ['customer' => $this->selectedCostumerId]);
        }

        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-contact-modal', 'type' => 'contact']);
        session()->flash('messages', $this->responseReceivedNow
            ? 'Contact et réponse enregistrés. Le suivi de ce client est terminé.'
            : 'Contact enregistré dans l’historique.');
    }

    private function createQuickServiceCase(
        Costumer $customer,
        string $response,
        string $channel,
        ?string $channelDetail = null,
        array $productIds = []
    ): CustomerServiceCase {
        $latestOrder = Order::with('orderItems')
            ->where('costumer_id', (string) $customer->id)
            ->orderByDesc('id')
            ->first();

        $case = CustomerServiceCase::create([
            'case_number' => 'TMP-'.\Illuminate\Support\Str::random(24),
            'costumer_id' => $customer->id,
            'order_id' => $latestOrder?->id,
            'product_id' => $productIds[0] ?? null,
            'case_type' => $this->quickServiceCaseType,
            'purchase_date' => $latestOrder ? ($latestOrder->date_order ?: $latestOrder->created_at) : null,
            'description' => trim($this->quickServiceCaseDescription),
            'priority' => 'normal',
            'status' => 'new',
            'assigned_to' => Auth::id(),
            'created_by' => Auth::id(),
        ]);
        $case->update(['case_number' => 'TEN-SC-'.now()->format('Y').'-'.str_pad((string) $case->id, 4, '0', STR_PAD_LEFT)]);
        $case->products()->sync($productIds);
        $case->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'created',
            'body' => 'Dossier créé depuis le suivi des contacts.',
            'internal' => true,
            'occurred_at' => now(),
        ]);
        $case->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'communication',
            'channel' => $channel,
            'body' => 'Réponse enregistrée dans le suivi des contacts'
                .($channel === 'other' && $channelDetail ? ' — '.$channelDetail : '')
                .' : '.$response,
            'internal' => true,
            'occurred_at' => now(),
        ]);

        if ($this->quickServiceCaseType === 'personalized_support') {
            $plan = CustomerServiceSupportPlan::create([
                'case_id' => $case->id,
                'customer_need' => trim($this->quickServiceCaseDescription),
                'started_at' => now()->toDateString(),
            ]);
            foreach ([3, 7, 15, 30] as $day) {
                $plan->milestones()->create([
                    'day_offset' => $day,
                    'due_at' => now()->startOfDay()->addDays($day)->setTime(9, 0),
                ]);
            }
        }

        return $case;
    }

    public function openResponseModal(int $historyId): void
    {
        $history = CostumerContactHistory::with('costumer')->findOrFail($historyId);
        $this->selectedHistoryId = $history->id;
        $this->selectedCostumerId = $history->costumer_id;
        $this->response = $history->response ?? '';
        $this->sentiment = $history->sentiment ?? 'neutral';
        $this->createServiceCase = false;
        $this->quickServiceCaseType = 'dissatisfied';
        $this->quickServiceCaseDescription = '';
        $latestOrder = Order::with('orderItems')->where('costumer_id', (string) $history->costumer_id)
            ->orderByRaw('COALESCE(date_order, created_at) DESC')->orderByDesc('id')->first();
        $this->quickServiceCaseProductIds = $latestOrder?->orderItems
            ->pluck('product_id')->filter()->unique()->map(fn ($id) => (string) $id)->values()->all() ?? [];
        $this->resetValidation();
        $this->showResponseModal = true;
    }

    public function saveResponse(): void
    {
        abort_unless(! $this->createServiceCase || $this->canCreateQuickServiceCase(), 403);

        if ($this->createServiceCase && trim($this->quickServiceCaseDescription) === '') {
            $this->quickServiceCaseDescription = trim($this->response);
        }

        $rules = [
            'selectedHistoryId' => 'required|exists:costumer_contact_histories,id',
            'response' => 'required|string|'.($this->createServiceCase ? 'min:8|' : '').'max:10000',
            'sentiment' => 'required|in:positive,neutral,negative',
            'createServiceCase' => 'boolean',
        ];
        if ($this->createServiceCase) {
            $rules['quickServiceCaseType'] = 'required|in:complaint,dissatisfied,personalized_support,information';
            $rules['quickServiceCaseDescription'] = 'required|string|min:8|max:10000';
            $rules['quickServiceCaseProductIds'] = 'array';
            $rules['quickServiceCaseProductIds.*'] = 'integer|distinct|exists:products,id';
        }
        $this->validate($rules);

        $history = CostumerContactHistory::with('costumer')->findOrFail($this->selectedHistoryId);
        abort_unless((int) $history->costumer_id === (int) $this->selectedCostumerId, 404);
        $selectedProductIds = collect($this->quickServiceCaseProductIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($this->createServiceCase && $selectedProductIds->isNotEmpty()) {
            $latestOrder = Order::with('orderItems')->where('costumer_id', (string) $history->costumer_id)
                ->orderByRaw('COALESCE(date_order, created_at) DESC')->orderByDesc('id')->first();
            $orderProductIds = $latestOrder?->orderItems->pluck('product_id')->map(fn ($id) => (int) $id)->unique() ?? collect();
            if ($selectedProductIds->diff($orderProductIds)->isNotEmpty()) {
                $this->addError('quickServiceCaseProductIds', 'Choisis uniquement des produits de la dernière commande.');
                return;
            }
        }

        $serviceCase = DB::transaction(function () use ($history, $selectedProductIds) {
            $history->update([
                'response' => $this->response,
                'sentiment' => $this->sentiment,
                'responded_at' => now(),
                'follow_up_at' => null,
            ]);
            CostumerContactPreference::where('costumer_id', $history->costumer_id)
                ->update(['next_follow_up_at' => null]);

            if (! $this->createServiceCase) {
                return null;
            }

            return $this->createQuickServiceCase(
                $history->costumer,
                trim($this->response),
                $history->channel,
                $history->channel_detail,
                $selectedProductIds->all()
            );
        });

        if ($serviceCase) {
            $recipients = User::whereIn('user_type', ['ADMINUSER', 'MNG', 'SCR'])->where('active', 1)->get();
            foreach ($recipients->unique('id') as $recipient) {
                if ((int) $recipient->id !== (int) Auth::id()) {
                    $recipient->notify(new CustomerServiceCaseNotification(
                        $serviceCase,
                        'created',
                        'Un nouveau dossier '.$serviceCase->case_number.' a été créé pour '.$serviceCase->customer->name.'.'
                    ));
                }
            }
        }

        $this->forgetFollowUpData();
        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-response-modal', 'type' => 'response']);
        session()->flash('messages', $serviceCase
            ? 'Réponse enregistrée et dossier SAV '.$serviceCase->case_number.' créé.'
            : 'Réponse enregistrée. Le suivi de ce client est terminé.');
    }

    public function openHistory(int $costumerId): void
    {
        $this->selectedCostumerId = $costumerId;
        $this->showFeedbackModal = false;
        $this->showHistoryModal = true;
    }

    public function openFeedbackDetails(string $sentiment): void
    {
        abort_unless(in_array($sentiment, ['positive', 'neutral', 'negative'], true), 404);

        $this->feedbackSentiment = $sentiment;
        $this->showFeedbackModal = true;
        $this->resetPage('feedbackPage');
    }

    public function openScheduledDateModal(int $costumerId): void
    {
        $costumer = Costumer::with('contactPreference')->findOrFail($costumerId);
        abort_if($costumer->contactPreference && $costumer->contactPreference->do_not_contact_at, 403);
        abort_unless(Order::where('costumer_id', (string) $costumer->id)->exists(), 403);

        $this->scheduledDateCostumerId = $costumer->id;
        $this->scheduledFollowUpDate = now()->format('Y-m-d\\TH:i');
        $this->showScheduledDateModal = true;
    }

    public function saveScheduledDate(): void
    {
        $this->validate([
            'scheduledDateCostumerId' => 'required|exists:costumers,id',
            'scheduledFollowUpDate' => 'required|date',
        ]);

        $costumer = Costumer::with(['latestContactHistory', 'contactPreference'])
            ->findOrFail($this->scheduledDateCostumerId);
        abort_if($costumer->contactPreference && $costumer->contactPreference->do_not_contact_at, 403);
        abort_unless(Order::where('costumer_id', (string) $costumer->id)->exists(), 403);

        $previousPreferenceDate = $costumer->contactPreference?->next_follow_up_at;
        if (!$previousPreferenceDate && $costumer->latestContactHistory?->follow_up_at) {
            $costumer->latestContactHistory->update(['follow_up_at' => $this->scheduledFollowUpDate]);
        }

        CostumerContactPreference::updateOrCreate(
            ['costumer_id' => $costumer->id],
            ['next_follow_up_at' => $this->scheduledFollowUpDate, 'updated_by' => Auth::id()]
        );
        $this->forgetFollowUpData();
        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'scheduled-follow-up-date-modal', 'type' => 'scheduled-date']);
        session()->flash('messages', 'La prochaine date de contact a été modifiée.');
    }

    public function openDoNotContactModal(int $costumerId): void
    {
        $this->selectedCostumerId = $costumerId;
        $this->doNotContactReason = '';
        $this->showDoNotContactModal = true;
    }

    public function markDoNotContact(): void
    {
        $this->validate([
            'selectedCostumerId' => 'required|exists:costumers,id',
            'doNotContactReason' => 'required|string|max:2000',
        ]);

        CostumerContactPreference::updateOrCreate(
            ['costumer_id' => $this->selectedCostumerId],
            [
                'do_not_contact_at' => now(),
                'do_not_contact_reason' => $this->doNotContactReason,
                'next_follow_up_at' => null,
                'updated_by' => Auth::id(),
            ]
        );
        $this->forgetFollowUpData();
        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-do-not-contact-modal', 'type' => 'do-not-contact']);
        session()->flash('messages', 'Le client est marqué « ne plus solliciter ».');
    }

    public function beginClosingModal(string $modal): void
    {
        $modalTypes = [
            'follow-up-contact-modal' => 'contact',
            'follow-up-response-modal' => 'response',
            'follow-up-history-modal' => 'history',
            'follow-up-do-not-contact-modal' => 'do-not-contact',
            'delete-template-modal' => 'delete-template',
            'scheduled-follow-up-date-modal' => 'scheduled-date',
            'follow-up-decision-confirm-modal' => 'follow-up-decision',
            'follow-up-feedback-modal' => 'feedback',
        ];

        if (isset($modalTypes[$modal])) {
            $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => $modal, 'type' => $modalTypes[$modal]]);
            $this->skipRender();
        }
    }

    public function finishClosingModal(string $modal): void
    {
        if ($modal === 'contact') {
            $this->showContactModal = false;
            $this->reset(['selectedCostumerId', 'notes', 'selectedTemplateId', 'responseReceivedNow', 'immediateResponse', 'immediateSentiment', 'createServiceCase', 'quickServiceCaseType', 'quickServiceCaseDescription', 'quickServiceCaseOrderId', 'quickServiceCaseProductId', 'quickServiceCaseProductIds']);
        } elseif ($modal === 'response') {
            $this->showResponseModal = false;
            $this->reset(['selectedHistoryId', 'selectedCostumerId', 'response', 'sentiment', 'createServiceCase', 'quickServiceCaseType', 'quickServiceCaseDescription', 'quickServiceCaseProductIds']);
        } elseif ($modal === 'history') {
            $this->showHistoryModal = false;
            $this->reset(['selectedCostumerId']);
        } elseif ($modal === 'do-not-contact') {
            $this->showDoNotContactModal = false;
            $this->reset(['selectedCostumerId', 'doNotContactReason']);
        } elseif ($modal === 'delete-template') {
            $this->showDeleteTemplateModal = false;
            $this->reset(['deletingTemplateId', 'deletingTemplateName']);
        } elseif ($modal === 'scheduled-date') {
            $this->showScheduledDateModal = false;
            $this->reset(['scheduledDateCostumerId', 'scheduledFollowUpDate']);
        } elseif ($modal === 'follow-up-decision') {
            $this->showFollowUpDecisionModal = false;
            $this->reset(['selectedCostumerId', 'pendingFollowUpDecision']);
        } elseif ($modal === 'feedback') {
            $this->showFeedbackModal = false;
            $this->reset(['feedbackSentiment']);
        }
    }

    public function allowContactAgain(int $costumerId): void
    {
        $preference = CostumerContactPreference::where('costumer_id', $costumerId)->firstOrFail();
        $preference->update([
            'do_not_contact_at' => null,
            'do_not_contact_reason' => null,
            'updated_by' => Auth::id(),
        ]);
        $this->forgetFollowUpData();
        session()->flash('messages', 'Le suivi du client est réactivé.');
    }

    public function editMessageTemplate(int $templateId): void
    {
        $template = CostumerContactMessageTemplate::findOrFail($templateId);
        $this->editingTemplateId = $template->id;
        $this->newTemplateChannel = $template->channel;
        $this->newTemplateName = $template->name;
        $this->newTemplateBody = $template->body;
    }

    public function cancelMessageTemplateEdit(): void
    {
        $this->reset(['editingTemplateId', 'newTemplateName', 'newTemplateBody']);
        $this->newTemplateChannel = 'whatsapp';
    }

    public function saveMessageTemplate(): void
    {
        $this->validate([
            'newTemplateChannel' => 'required|in:whatsapp,sms,call',
            'newTemplateName' => 'required|string|max:120',
            'newTemplateBody' => 'required|string|max:2000',
        ]);

        $data = [
            'channel' => $this->newTemplateChannel,
            'name' => $this->newTemplateName,
            'body' => $this->newTemplateBody,
            'active' => true,
        ];

        if ($this->editingTemplateId) {
            CostumerContactMessageTemplate::whereKey($this->editingTemplateId)->update($data);
        } else {
            CostumerContactMessageTemplate::create($data);
        }

        $this->cancelMessageTemplateEdit();
        session()->flash('messages', 'Le modèle de message a été enregistré.');
    }

    public function toggleMessageTemplate(int $templateId): void
    {
        $template = CostumerContactMessageTemplate::findOrFail($templateId);
        $template->update(['active' => !$template->active]);
        if ((int) $this->selectedTemplateId === $templateId) {
            $this->selectedTemplateId = null;
        }
        session()->flash('messages', $template->active ? 'Le modèle a été activé.' : 'Le modèle a été désactivé.');
    }

    public function openDeleteTemplateModal(int $templateId): void
    {
        $template = CostumerContactMessageTemplate::findOrFail($templateId);
        $this->deletingTemplateId = $template->id;
        $this->deletingTemplateName = $template->name;
        $this->showDeleteTemplateModal = true;
    }

    public function confirmDeleteMessageTemplate(): void
    {
        $template = CostumerContactMessageTemplate::findOrFail($this->deletingTemplateId);
        $templateId = $template->id;
        $template->delete();

        if ((int) $this->selectedTemplateId === $templateId) {
            $this->selectedTemplateId = null;
        }
        if ((int) $this->editingTemplateId === $templateId) {
            $this->cancelMessageTemplateEdit();
        }

        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'delete-template-modal', 'type' => 'delete-template']);
        session()->flash('messages', 'Le modèle a été supprimé.');
    }

    public function decideFollowUp(int $costumerId, string $decision): void
    {
        if (!in_array($decision, ['continue', 'stop'], true)) {
            return;
        }

        $customer = Costumer::with(['latestContactHistory', 'contactPreference'])->withCount([
            'contactHistories as contact_histories_count' => fn ($query) => $query->whereNotNull('responded_at'),
            'contactHistories as follow_up_count' => fn ($query) => $query->where('contact_type', 'follow_up'),
        ])->findOrFail($costumerId);
        $latest = $customer->latestContactHistory;
        abort_unless($latest && $this->statusFor($customer) === 'review_required', 403);

        $latest->update(['follow_up_decision' => $decision]);
        if ($decision === 'stop') {
            CostumerContactPreference::where('costumer_id', $costumerId)
                ->update(['next_follow_up_at' => null]);
        }
        $this->forgetFollowUpData();
        session()->flash('messages', $decision === 'continue'
            ? 'Une nouvelle série de relances est autorisée.'
            : 'Le suivi est clôturé après les relances prévues.');
    }

    public function openFollowUpDecisionModal(int $costumerId, string $decision): void
    {
        abort_unless(in_array($decision, ['continue', 'stop'], true), 403);
        $customer = Costumer::with(['latestContactHistory', 'contactPreference'])->withCount([
            'contactHistories as contact_histories_count' => fn ($query) => $query->whereNotNull('responded_at'),
            'contactHistories as follow_up_count' => fn ($query) => $query->where('contact_type', 'follow_up'),
        ])->findOrFail($costumerId);
        abort_unless($this->statusFor($customer) === 'review_required', 403);

        $this->selectedCostumerId = $customer->id;
        $this->pendingFollowUpDecision = $decision;
        $this->showFollowUpDecisionModal = true;
    }

    public function confirmFollowUpDecision(): void
    {
        $this->validate([
            'selectedCostumerId' => 'required|exists:costumers,id',
            'pendingFollowUpDecision' => 'required|in:continue,stop',
        ]);
        $this->decideFollowUp((int) $this->selectedCostumerId, $this->pendingFollowUpDecision);
        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-decision-confirm-modal', 'type' => 'follow-up-decision']);
    }

    public function saveSettings(): void
    {
        $this->validate([
            'defaultFollowUpDays' => 'required|integer|min:1|max:365',
            'maxFollowUps' => 'required|integer|min:1|max:20',
            'followUpStartDate' => 'required|date_format:Y-m-d',
            'defaultCountryCallingCode' => ['required', 'regex:/^\+?[0-9]{1,4}$/'],
        ]);

        CostumerContactSetting::query()->updateOrCreate(['id' => 1], [
            'default_follow_up_days' => $this->defaultFollowUpDays,
            'follow_up_start_date' => $this->followUpStartDate,
            'max_follow_ups' => $this->maxFollowUps,
            'default_country_calling_code' => str_starts_with($this->defaultCountryCallingCode, '+')
                ? $this->defaultCountryCallingCode
                : '+'.$this->defaultCountryCallingCode,
        ]);

        $this->defaultCountryCallingCode = str_starts_with($this->defaultCountryCallingCode, '+')
            ? $this->defaultCountryCallingCode
            : '+'.$this->defaultCountryCallingCode;

        $this->forgetFollowUpData();

        session()->flash('messages', 'Les paramètres de relance ont été enregistrés.');
    }

    protected function loadSettings(): void
    {
        $settings = CostumerContactSetting::first();
        if ($settings) {
            $this->defaultFollowUpDays = $settings->default_follow_up_days;
            $this->defaultCountryCallingCode = $settings->default_country_calling_code;
            $this->maxFollowUps = $settings->max_follow_ups;
            $this->followUpStartDate = $settings->follow_up_start_date ?: now()->format('Y-m-d');
        }
    }

    protected function statusFor(Costumer $costumer): string
    {
        if ($costumer->contactPreference && $costumer->contactPreference->do_not_contact_at) {
            return 'do_not_contact';
        }

        $hasNewScheduledFollowUp = $costumer->contactPreference && $costumer->contactPreference->next_follow_up_at;
        if ($costumer->contact_histories_count > 0 && !$hasNewScheduledFollowUp) {
            return 'responded';
        }

        $latest = $costumer->latestContactHistory;
        $nextFollowUpAt = $costumer->contactPreference?->next_follow_up_at;
        if (!$nextFollowUpAt && $latest) {
            $nextFollowUpAt = $latest->follow_up_at;
        } elseif (!$nextFollowUpAt && $costumer->latestOrder) {
            $orderDate = $costumer->latestOrder->date_order ?: $costumer->latestOrder->created_at;
            if (Carbon::parse($orderDate)->toDateString() >= ($this->followUpStartDate ?: now()->format('Y-m-d'))) {
                $nextFollowUpAt = Carbon::parse($orderDate)->addDays((int) $this->defaultFollowUpDays);
            }
        }
        $isDue = $nextFollowUpAt && ($nextFollowUpAt->isPast() || $nextFollowUpAt->isToday());
        if (!$latest) {
            return $isDue ? 'to_follow_up' : 'not_contacted';
        }

        if ($latest->follow_up_decision === 'stop' && !$hasNewScheduledFollowUp) {
            return 'closed';
        }

        if ($latest->follow_up_decision === 'stop' && $hasNewScheduledFollowUp) {
            return $isDue ? 'to_follow_up' : 'contacted';
        }

        if ($costumer->follow_up_count >= $this->maxFollowUps
            && $latest->follow_up_decision !== 'continue'
            && $isDue) {
            return 'review_required';
        }

        return $isDue ? 'to_follow_up' : 'contacted';
    }

    protected function dueOrdersCutoff(): string
    {
        return now()->subDays((int) $this->defaultFollowUpDays)->format('Y-m-d H:i:s');
    }

    protected function dueCustomerIdsFromOrders(): array
    {
        $cutoff = $this->dueOrdersCutoff();
        $startDate = $this->followUpStartDate ?: now()->format('Y-m-d');
        $cacheKey = 'customer-follow-up-due-orders:'.$startDate.':'.$cutoff;

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($cutoff, $startDate) {
            return Order::query()
                ->select('costumer_id')
                ->whereNotNull('costumer_id')
                ->where('costumer_id', '!=', '')
                ->whereNotIn('costumer_id', CostumerContactPreference::query()
                    ->selectRaw('CAST(costumer_id AS CHAR)')->whereNotNull('do_not_contact_at'))
                ->groupBy('costumer_id')
                ->havingRaw('MAX(COALESCE(date_order, created_at)) >= ?', [$startDate.' 00:00:00'])
                ->havingRaw('MAX(COALESCE(date_order, created_at)) <= ?', [$cutoff])
                ->pluck('costumer_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();
        });
    }

    protected function statusQuery(string $status, ?Builder $query = null): Builder
    {
        $query = $query ?: Costumer::query();

        if ($status === 'not_contacted') {
            $query->whereDoesntHave('contactHistories')
                ->whereDoesntHave('contactPreference', fn ($preference) => $preference
                    ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay()))
                ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('do_not_contact_at'));

            $dueOrderCustomerIds = $this->dueCustomerIdsFromOrders();
            if (!empty($dueOrderCustomerIds)) {
                $query->whereNotIn('costumers.id', $dueOrderCustomerIds);
            }

            return $query;
        }

        if ($status === 'responded') {
            return $query->whereHas('contactHistories', fn ($history) => $history->whereNotNull('responded_at'))
                ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('do_not_contact_at'));
        }

        if ($status === 'do_not_contact') {
            return $query->whereHas('contactPreference', fn ($preference) => $preference->whereNotNull('do_not_contact_at'));
        }

        $query->where(function ($followUpCycle) {
            $followUpCycle->whereDoesntHave('contactHistories', fn ($history) => $history->whereNotNull('responded_at'))
                ->orWhereHas('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'));
        })
            ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('do_not_contact_at'));

        if ($status === 'closed') {
            return $this->whereLatestContactHistory($query, fn ($history) => $history->where('follow_up_decision', 'stop'));
        }

        if ($status === 'review_required') {
            return $query->whereHas('contactHistories', fn ($history) => $history->where('contact_type', 'follow_up'), '>=', $this->maxFollowUps)
                ->where(function ($due) {
                    $due->whereHas('contactPreference', fn ($preference) => $preference
                        ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay()))
                        ->orWhere(function ($historyDue) {
                            $historyDue->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                                ->where(function ($latestHistory) {
                                    $this->whereLatestContactHistory($latestHistory, fn ($history) => $history
                                        ->whereNull('follow_up_decision')->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now()->endOfDay()));
                                });
                        });
                })
                ->where(function ($latestHistory) {
                    $this->whereLatestContactHistory($latestHistory, fn ($history) => $history->whereNull('follow_up_decision'));
                });
        }

        if ($status === 'to_follow_up') {
            $dueOrderCustomerIds = $this->dueCustomerIdsFromOrders();

            return $query->where(function ($due) use ($dueOrderCustomerIds) {
                $due->where(function ($scheduledDue) {
                    $scheduledDue->whereHas('contactPreference', fn ($preference) => $preference
                        ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay()))
                        ->where(function ($attempts) {
                            $attempts->whereHas('contactHistories', fn ($history) => $history->where('contact_type', 'follow_up'), '<', $this->maxFollowUps)
                                ->orWhere(function ($latestHistory) {
                                    $this->whereLatestContactHistory($latestHistory, fn ($history) => $history->whereIn('follow_up_decision', ['continue', 'stop']));
                                });
                        });
                })
                    ->orWhere(function ($historyDue) {
                        $historyDue->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                            ->where(function ($attempts) {
                                $attempts->whereHas('contactHistories', fn ($history) => $history->where('contact_type', 'follow_up'), '<', $this->maxFollowUps)
                                    ->orWhere(function ($latestHistory) {
                                        $this->whereLatestContactHistory($latestHistory, fn ($history) => $history->where('follow_up_decision', 'continue'));
                                    });
                            })
                            ->where(function ($latestHistory) {
                                $this->whereLatestContactHistory($latestHistory, fn ($history) => $history
                                    ->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now()->endOfDay())
                                    ->where(fn ($decision) => $decision->whereNull('follow_up_decision')->orWhere('follow_up_decision', 'continue')));
                            });
                    })
                    ->orWhere(function ($orderDue) use ($dueOrderCustomerIds) {
                        if (empty($dueOrderCustomerIds)) {
                            $orderDue->whereRaw('0 = 1');
                        } else {
                            $orderDue->whereIn('costumers.id', $dueOrderCustomerIds)
                                ->whereDoesntHave('contactHistories')
                                ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'));
                        }
                    });
            });
        }

        if ($status === 'contacted') {
            return $query->whereHas('contactHistories')->where(function ($pending) {
                $pending->whereHas('contactPreference', fn ($preference) => $preference
                    ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '>', now()->endOfDay()))
                    ->orWhere(function ($historyPending) {
                        $historyPending->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                            ->where(function ($latestHistory) {
                                $this->whereLatestContactHistory($latestHistory, fn ($history) => $history
                                    ->where(function ($followUp) {
                                        $followUp->whereNull('follow_up_at')->orWhere('follow_up_at', '>', now()->endOfDay());
                                    })->where(function ($decision) {
                                        $decision->whereNull('follow_up_decision')->orWhere('follow_up_decision', '!=', 'stop');
                                    }));
                            });
                    });
            });
        }

        return $query;
    }

    protected function whereLatestContactHistory(Builder $query, callable $constraints): Builder
    {
        return $query->whereExists(function (\Illuminate\Database\Query\Builder $latest) use ($constraints) {
            $latest->selectRaw('1')
                ->from('costumer_contact_histories as latest_customer_contact')
                ->whereColumn('latest_customer_contact.costumer_id', 'costumers.id')
                ->whereRaw('latest_customer_contact.id = (SELECT latest_contact.id FROM costumer_contact_histories AS latest_contact WHERE latest_contact.costumer_id = costumers.id ORDER BY latest_contact.contacted_at DESC, latest_contact.id DESC LIMIT 1)');

            $constraints($latest);
        });
    }

    protected function searchQuery(Builder $query): Builder
    {
        if ($this->search) {
            $search = '%'.$this->search.'%';
            $query->where(function ($customers) use ($search) {
                $customers->where('name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        return $query;
    }

    protected function selectedMessage(Costumer $costumer): ?string
    {
        $template = $this->selectedTemplateId
            ? CostumerContactMessageTemplate::whereKey($this->selectedTemplateId)->where('channel', $this->channel)->where('active', true)->first()
            : null;

        if (!$template) {
            return null;
        }

        $order = $costumer->latestOrder;
        if ($order && !$order->relationLoaded('orderItems')) {
            $order->load('orderItems.product');
        }
        $products = $order
            ? $order->orderItems->pluck('product.name')->filter()->unique()->implode(', ')
            : '';

        $message = str_replace(['{{name}}', '{{product}}'], [$costumer->name, $products], $template->body);

        $message = preg_replace('/(?:merci[, ]*)?tenace(?:\s+cosmetique)?[.!]?\s*$/iu', '', $message);

        if ($products && !str_contains($template->body, '{{product}}')) {
            $message = rtrim($message)."\n\nVotre commande : ".$products.'.';
        }

        return rtrim($message)."\n\nTENACE COSMETIQUE";
    }

    protected function detectCallingCode(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        $hasInternationalPrefix = str_starts_with($phone, '+') || str_starts_with($phone, '00');
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $callingCodes = [
            '1', '7', '20', '27', '33', '34', '39', '44', '49', '52', '55', '60', '61', '62', '63', '64', '65', '66',
            '81', '82', '84', '86', '90', '91', '92', '93', '94', '95', '98',
            '211', '212', '213', '216', '218', '220', '221', '222', '223', '224', '225', '226', '227', '228', '229',
            '230', '231', '232', '233', '234', '235', '236', '237', '238', '239', '240', '241', '242', '243', '244',
            '245', '246', '248', '249', '250', '251', '252', '253', '254', '255', '256', '257', '258', '260', '261',
            '262', '263', '264', '265', '266', '267', '268', '269',
        ];
        usort($callingCodes, fn ($left, $right) => strlen($right) <=> strlen($left));

        foreach ($callingCodes as $callingCode) {
            if (str_starts_with($digits, $callingCode)
                && ($hasInternationalPrefix || strlen($digits) >= 11)) {
                return '+'.$callingCode;
            }
        }

        return null;
    }

    protected function contactUrl(Costumer $costumer, string $channel): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $costumer->phone);
        if (!$phone) {
            return null;
        }

        if (str_starts_with(trim((string) $costumer->phone), '00')) {
            $phone = substr($phone, 2);
        } elseif (!str_starts_with(trim((string) $costumer->phone), '+')) {
            $callingCode = ltrim($this->individualCallingCode ?: $this->defaultCountryCallingCode, '+');
            $phone = str_starts_with($phone, $callingCode) ? $phone : $callingCode.$phone;
        }

        if ($channel === 'whatsapp') {
            $message = $this->selectedMessage($costumer) ?? ('Bonjour '.$costumer->name.', nous aimerions recueillir votre avis sur votre expérience avec nous. Merci de nous répondre.');
            return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
        }

        if ($channel === 'sms') {
            $message = $this->selectedMessage($costumer);
            return 'sms:+'.$phone.($message ? '?body='.rawurlencode($message) : '');
        }

        if ($channel === 'call') {
            return 'tel:+'.$phone;
        }

        return null;
    }

    protected function canCreateQuickServiceCase(): bool
    {
        return Auth::user()?->hasRole(['ADMINUSER', 'MNG', 'SCR', 'CALLCENTER']) ?? false;
    }

    protected function followUpCountsCacheKey(): string
    {
        return 'customer-follow-up-counts:'.$this->defaultFollowUpDays.':'.$this->maxFollowUps.':'.($this->followUpStartDate ?: now()->format('Y-m-d'));
    }

    protected function forgetFollowUpData(): void
    {
        Cache::forget($this->followUpCountsCacheKey());
        Cache::forget('customer-follow-up-due-orders:'.($this->followUpStartDate ?: now()->format('Y-m-d')).':'.$this->dueOrdersCutoff());
        if ($this->selectedDate) {
            Cache::forget('customer-follow-up-daily:'.$this->selectedDate.':'.$this->defaultFollowUpDays.':'.$this->maxFollowUps.':'.$this->followUpStartDate);
        }
        Cache::forget('customer-follow-up-daily:'.now()->format('Y-m-d').':'.$this->defaultFollowUpDays.':'.$this->maxFollowUps.':'.$this->followUpStartDate);
        $versionKey = 'customer-follow-up-list-version';
        Cache::add($versionKey, 1);
        Cache::increment($versionKey);
    }

    public function loadDailyActivityStats(): array
    {
        $date = $this->selectedDate ?: now()->format('Y-m-d');
        $startDate = $this->followUpStartDate ?: now()->format('Y-m-d');
        $cacheKey = 'customer-follow-up-daily:'.$date.':'.$this->defaultFollowUpDays.':'.$this->maxFollowUps.':'.$startDate;

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($date, $startDate) {
            $parsedDate = Carbon::parse($date);
            $startOfDay = $parsedDate->copy()->startOfDay();
            $endOfDay = $parsedDate->copy()->endOfDay();

            // 1. Contacts effectués à la date sélectionnée
            $doneCustomerIds = CostumerContactHistory::whereBetween('contacted_at', [$startOfDay, $endOfDay])
                ->pluck('costumer_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $doneCount = CostumerContactHistory::whereBetween('contacted_at', [$startOfDay, $endOfDay])->count();

            // 2. Clients prévus / restant à relancer à la date sélectionnée
            $prefIds = CostumerContactPreference::whereDate('next_follow_up_at', $date)
                ->whereNull('do_not_contact_at')
                ->pluck('costumer_id');

            $histIds = CostumerContactHistory::whereDate('follow_up_at', $date)
                ->whereNotIn('costumer_id', CostumerContactPreference::query()
                    ->select('costumer_id')->whereNotNull('do_not_contact_at'))
                ->pluck('costumer_id');

            $orderTargetDate = $parsedDate->copy()->subDays((int) $this->defaultFollowUpDays)->format('Y-m-d');
            $orderIds = collect();
            if ($orderTargetDate >= $startDate) {
                $orderIds = Order::whereDate(DB::raw('COALESCE(date_order, created_at)'), $orderTargetDate)
                    ->whereNotNull('costumer_id')
                    ->where('costumer_id', '!=', '')
                    ->whereNotIn('costumer_id', CostumerContactPreference::query()
                        ->select('costumer_id')->whereNotNull('do_not_contact_at'))
                    ->pluck('costumer_id');
            }

            $todayCustomerIds = $prefIds->merge($histIds)->merge($orderIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->filter()
                ->diff($doneCustomerIds)
                ->values()
                ->all();

            $scheduledCount = count($todayCustomerIds);

            // Volume total du jour = contacts déjà effectués + contacts encore à relancer
            $totalDayScheduled = $doneCustomerIds->merge($todayCustomerIds)->unique()->count();

            // 3. Contacts en retard (prévus avant le jour sélectionné)
            $overduePrefIds = CostumerContactPreference::where('next_follow_up_at', '<', $startOfDay)
                ->whereNull('do_not_contact_at')
                ->pluck('costumer_id');

            $overdueHistIds = CostumerContactHistory::where('follow_up_at', '<', $startOfDay)
                ->whereNotIn('costumer_id', CostumerContactPreference::query()
                    ->select('costumer_id')->whereNotNull('do_not_contact_at'))
                ->pluck('costumer_id');

            $dueOrderIds = $this->dueCustomerIdsFromOrders();

            $overdueCustomerIds = $overduePrefIds->merge($overdueHistIds)->merge($dueOrderIds)
                ->map(fn ($id) => (int) $id)
                ->diff($todayCustomerIds)
                ->diff($doneCustomerIds)
                ->unique()
                ->filter()
                ->values();

            if ($overdueCustomerIds->isNotEmpty()) {
                // Ignore stale history/order dates when a newer follow-up is scheduled.
                $futureScheduledIds = CostumerContactPreference::whereIn('costumer_id', $overdueCustomerIds)
                    ->where('next_follow_up_at', '>=', $startOfDay)
                    ->pluck('costumer_id')
                    ->map(fn ($id) => (int) $id);

                // A reply or a stopped follow-up closes the old overdue task.
                $nonActionableIds = Costumer::query()
                    ->whereIn('costumers.id', $overdueCustomerIds)
                    ->where(function ($query) {
                        $query->whereHas('contactPreference', fn ($preference) => $preference->whereNotNull('do_not_contact_at'))
                            ->orWhere(function ($resolved) {
                                $resolved->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                                    ->where(function ($latestHistory) {
                                        $this->whereLatestContactHistory($latestHistory, fn ($history) => $history->where(function ($terminal) {
                                            $terminal->whereNotNull('responded_at')->orWhere('follow_up_decision', 'stop');
                                        }));
                                    });
                            });
                    })
                    ->pluck('costumers.id')
                    ->map(fn ($id) => (int) $id);

                $overdueCustomerIds = $overdueCustomerIds
                    ->diff($futureScheduledIds)
                    ->diff($nonActionableIds)
                    ->values();
            }

            $overdueCustomerIds = $overdueCustomerIds->all();

            $overdueCount = count($overdueCustomerIds);

            // 4. Pourcentage d'avancement réel du jour : réalisés / total du jour
            $progressRate = $totalDayScheduled > 0
                ? min(100, (int) round(($doneCount / $totalDayScheduled) * 100))
                : ($doneCount > 0 ? 100 : 0);

            // 5. Historique sur 7 jours jusqu'à la date sélectionnée pour la courbe
            $chartStart = $parsedDate->copy()->subDays(6)->startOfDay();
            $historyCounts = CostumerContactHistory::whereBetween('contacted_at', [$chartStart, $endOfDay])
                ->selectRaw('DATE(contacted_at) as dt, COUNT(*) as aggregate')
                ->groupBy('dt')
                ->pluck('aggregate', 'dt');

            $prefDays = CostumerContactPreference::whereBetween('next_follow_up_at', [$chartStart, $endOfDay])
                ->whereNull('do_not_contact_at')
                ->selectRaw('DATE(next_follow_up_at) as dt, costumer_id')
                ->get()
                ->groupBy('dt');

            $histDays = CostumerContactHistory::whereBetween('follow_up_at', [$chartStart, $endOfDay])
                ->whereNotIn('costumer_id', CostumerContactPreference::query()
                    ->select('costumer_id')->whereNotNull('do_not_contact_at'))
                ->selectRaw('DATE(follow_up_at) as dt, costumer_id')
                ->get()
                ->groupBy('dt');

            $orderStart = $chartStart->copy()->subDays((int) $this->defaultFollowUpDays)->startOfDay();
            if ($orderStart->toDateString() < $startDate) {
                $orderStart = Carbon::parse($startDate)->startOfDay();
            }
            $orderEnd = $endOfDay->copy()->subDays((int) $this->defaultFollowUpDays)->endOfDay();
            $orderDays = Order::whereBetween(DB::raw('COALESCE(date_order, created_at)'), [$orderStart, $orderEnd])
                ->whereNotNull('costumer_id')
                ->where('costumer_id', '!=', '')
                ->whereNotIn('costumer_id', CostumerContactPreference::query()
                    ->select('costumer_id')->whereNotNull('do_not_contact_at'))
                ->selectRaw('DATE(COALESCE(date_order, created_at)) as dt, costumer_id')
                ->get()
                ->groupBy('dt');

            $doneIdsByDay = CostumerContactHistory::whereBetween('contacted_at', [$chartStart, $endOfDay])
                ->selectRaw('DATE(contacted_at) as dt, costumer_id')
                ->get()
                ->groupBy('dt');

            $chartDays = [];
            for ($i = 6; $i >= 0; $i--) {
                $cur = $parsedDate->copy()->subDays($i);
                $curDateStr = $cur->format('Y-m-d');
                $cDone = (int) ($historyCounts->get($curDateStr, 0));

                $orderTargetDate = $cur->copy()->subDays((int) $this->defaultFollowUpDays)->format('Y-m-d');
                $pIds = collect($prefDays->get($curDateStr, []))->pluck('costumer_id');
                $hIds = collect($histDays->get($curDateStr, []))->pluck('costumer_id');
                $oIds = collect($orderDays->get($orderTargetDate, []))->pluck('costumer_id');
                $cDoneIds = collect($doneIdsByDay->get($curDateStr, []))->pluck('costumer_id')->map(fn ($id) => (int) $id);

                $cRemaining = $pIds->merge($hIds)->merge($oIds)->map(fn ($id) => (int) $id)->unique()->diff($cDoneIds)->filter()->count();
                $cTotal = $cRemaining + $cDone;
                $cRate = $cTotal > 0
                    ? min(100, (int) round(($cDone / $cTotal) * 100))
                    : ($cDone > 0 ? 100 : 0);

                $chartDays[] = [
                    'date' => $curDateStr,
                    'label' => ucfirst($cur->locale('fr')->isoFormat('ddd D')),
                    'full_date' => ucfirst($cur->locale('fr')->isoFormat('dddd D MMMM')),
                    'is_selected' => $curDateStr === $date,
                    'is_today' => $curDateStr === now()->format('Y-m-d'),
                    'done' => $cDone,
                    'scheduled' => $cTotal,
                    'remaining' => $cRemaining,
                    'rate' => $cRate,
                    'overdue' => $cRemaining,
                ];
            }

            return [
                'date' => $date,
                'formattedDate' => ucfirst($parsedDate->locale('fr')->isoFormat('dddd D MMMM YYYY')),
                'isToday' => $date === now()->format('Y-m-d'),
                'doneCount' => $doneCount,
                'scheduledCount' => $scheduledCount,
                'totalScheduled' => $totalDayScheduled,
                'overdueCount' => $overdueCount,
                'progressRate' => $progressRate,
                'todayCustomerIds' => $todayCustomerIds,
                'overdueCustomerIds' => $overdueCustomerIds,
                'chartDays' => $chartDays,
            ];
        });
    }

    protected function loadFollowUpCounts(): array
    {
        return Cache::remember($this->followUpCountsCacheKey(), now()->addMinutes(5), function () {
            $sentimentCounts = CostumerContactHistory::query()
                ->whereNotNull('responded_at')
                ->selectRaw('sentiment, COUNT(*) as aggregate')
                ->groupBy('sentiment')
                ->pluck('aggregate', 'sentiment');

            $all = Costumer::count();
            $doNotContact = CostumerContactPreference::whereNotNull('do_not_contact_at')->count();
            $responded = $this->statusQuery('responded')->count();
            $closed = $this->statusQuery('closed')->count();
            $reviewRequired = $this->statusQuery('review_required')->count();
            $toFollowUp = $this->statusQuery('to_follow_up')->count();
            $contacted = $this->statusQuery('contacted')->count();
            $notContacted = max(0, $all - ($doNotContact + $responded + $closed + $reviewRequired + $toFollowUp + $contacted));

            return [
                'all' => $all,
                'not_contacted' => $notContacted,
                'contacted' => $contacted,
                'responded' => $responded,
                'to_follow_up' => $toFollowUp,
                'review_required' => $reviewRequired,
                'closed' => $closed,
                'do_not_contact' => $doNotContact,
                'feedback' => [
                    'positive' => (int) $sentimentCounts->get('positive', 0),
                    'neutral' => (int) $sentimentCounts->get('neutral', 0),
                    'negative' => (int) $sentimentCounts->get('negative', 0),
                    'unclassified' => (int) $sentimentCounts->get(null, 0),
                ],
            ];
        });
    }

    protected function loadFollowUpPage(array $dailyStats): array
    {
        $version = Cache::get('customer-follow-up-list-version', 1);
        $cacheKey = 'customer-follow-up-page:'.$version.':'.hash('sha256', serialize([
            $this->search,
            $this->statusFilter,
            $this->selectedDate,
            $this->followUpScope,
            (int) $this->page,
            $this->defaultFollowUpDays,
            $this->maxFollowUps,
            $this->followUpStartDate,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($dailyStats) {
            $query = Costumer::query();

            $this->searchQuery($query);

            if ($this->statusFilter === 'to_follow_up') {
                if ($this->followUpScope === 'today') {
                    $ids = $dailyStats['todayCustomerIds'];
                    if (empty($ids)) {
                        $query->whereRaw('0 = 1');
                    } else {
                        $query->whereIn('costumers.id', $ids);
                    }
                } elseif ($this->followUpScope === 'overdue') {
                    $ids = $dailyStats['overdueCustomerIds'];
                    if (empty($ids)) {
                        $query->whereRaw('0 = 1');
                    } else {
                        $query->whereIn('costumers.id', $ids);
                    }
                } else {
                    $this->statusQuery('to_follow_up', $query);
                }
            } elseif ($this->statusFilter !== 'all') {
                $this->statusQuery($this->statusFilter, $query);
            }

            $costumers = $query
                ->select('costumers.*')
                ->selectRaw('(SELECT COUNT(*) FROM orders WHERE orders.costumer_id = CAST(costumers.id AS CHAR)) as orders_count')
                ->selectRaw('(SELECT MAX(id) FROM orders WHERE orders.costumer_id = CAST(costumers.id AS CHAR)) as orders_latest_id')
                ->with(['latestContactHistory.user', 'contactPreference'])
                ->orderByDesc('orders_count')
                ->orderByDesc('orders_latest_id')
                ->orderBy('name')
                ->orderBy('costumers.id')
                ->paginate(15);

            $costumers->getCollection()->loadCount([
                'contactHistories as contact_histories_count' => fn ($history) => $history->whereNotNull('responded_at'),
                'contactHistories as follow_up_count' => fn ($history) => $history->where('contact_type', 'follow_up'),
            ]);

            // orders.costumer_id is VARCHAR in production while costumers.id is BIGINT.
            // Fetch latest orders by string IDs so MySQL can use the orders customer index.
            $customerIds = $costumers->getCollection()->pluck('id')->map(fn ($id) => (string) $id);
            $latestOrderIds = Order::query()
                ->selectRaw('MAX(id)')
                ->whereIn('costumer_id', $customerIds)
                ->groupBy('costumer_id');
            $latestOrders = Order::query()
                ->whereIn('id', $latestOrderIds)
                ->get()
                ->keyBy(fn ($order) => (string) $order->costumer_id);

            $costumers->getCollection()->each(function ($costumer) use ($latestOrders) {
                $costumer->setRelation('latestOrder', $latestOrders->get((string) $costumer->id));
            });

            $statuses = $costumers->getCollection()->mapWithKeys(fn ($costumer) => [$costumer->id => $this->statusFor($costumer)]);

            return [$costumers, $statuses];
        });
    }

    public function render()
    {
        $counts = $this->loadFollowUpCounts();
        $dailyStats = $this->loadDailyActivityStats();
        [$costumers, $statuses] = $this->loadFollowUpPage($dailyStats);

        $selectedCostumer = null;
        if ($this->selectedCostumerId) {
            $selectedCostumerQuery = Costumer::with('contactPreference');
            $selectedCostumer = $selectedCostumerQuery->find($this->selectedCostumerId);
            if ($selectedCostumer && ($this->showContactModal || $this->showResponseModal)) {
                $selectedCostumer->setRelation('latestOrder', Order::with('orderItems.product')
                    ->where('costumer_id', (string) $selectedCostumer->id)
                    ->orderByRaw('COALESCE(date_order, created_at) DESC')
                    ->orderByDesc('id')
                    ->first());
            }
        }
        $serviceCaseOrders = $this->showContactModal && $selectedCostumer && $this->canCreateQuickServiceCase()
            ? Order::with('orderItems.product')
                ->where('costumer_id', (string) $selectedCostumer->id)
                ->orderByRaw('COALESCE(date_order, created_at) DESC')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
            : collect();
        $selectedServiceCaseOrder = $serviceCaseOrders->firstWhere('id', (int) $this->quickServiceCaseOrderId);

        return view('livewire.costumer-follow-up', [
            'costumers' => $costumers,
            'counts' => $counts,
            'dailyStats' => $dailyStats,
            'statuses' => $statuses,
            'history' => $this->showHistoryModal && $this->selectedCostumerId
                ? CostumerContactHistory::with(['user', 'messageTemplate'])->where('costumer_id', $this->selectedCostumerId)->orderByDesc('contacted_at')->get()
                : collect(),
            'selectedCostumer' => $selectedCostumer,
            'canCreateServiceCase' => $this->canCreateQuickServiceCase(),
            'serviceCaseOrders' => $serviceCaseOrders,
            'selectedServiceCaseOrder' => $selectedServiceCaseOrder,
            'contactUrl' => $this->showContactModal && $selectedCostumer ? $this->contactUrl($selectedCostumer, $this->channel) : null,
            'messageTemplates' => CostumerContactMessageTemplate::where('active', true)->where('channel', $this->channel)->orderBy('name')->get(),
            'allMessageTemplates' => CostumerContactMessageTemplate::orderBy('channel')->orderBy('name')->get(),
            'selectedMessage' => $this->showContactModal && $selectedCostumer ? $this->selectedMessage($selectedCostumer) : null,
            'feedbacks' => $this->showFeedbackModal
                ? CostumerContactHistory::with('costumer')
                    ->whereNotNull('responded_at')
                    ->where('sentiment', $this->feedbackSentiment)
                    ->orderByDesc('responded_at')
                    ->paginate(10, ['*'], 'feedbackPage')
                : null,
        ])->extends('layouts.admin')->section('content');
    }
}
