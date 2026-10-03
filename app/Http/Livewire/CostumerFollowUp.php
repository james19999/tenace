<?php

namespace App\Http\Livewire;

use App\Models\Costumer;
use App\Models\CostumerContactHistory;
use App\Models\CostumerContactMessageTemplate;
use App\Models\CostumerContactPreference;
use App\Models\CostumerContactSetting;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
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
    public $defaultCountryCallingCode = '+228';

    protected $queryString = ['search', 'statusFilter'];

    protected $listeners = ['showFeedbackDetails' => 'openFeedbackDetails'];

    public function mount(): void
    {
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

    public function updatedChannel(): void
    {
        $this->selectedTemplateId = CostumerContactMessageTemplate::where('channel', $this->channel)
            ->where('active', true)->value('id');
    }

    public function updatedResponseReceivedNow($value = null): void
    {
        $this->resetValidation($this->responseReceivedNow ? 'followUpAt' : 'immediateResponse');
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
        $this->showContactModal = true;
    }

    public function saveContact(): void
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
        if ($this->responseReceivedNow) {
            $rules['immediateResponse'] = 'required|string|max:10000';
        } else {
            $rules['followUpAt'] = 'nullable|date|after:contactedAt';
        }
        $this->validate($rules);

        abort_if(CostumerContactPreference::where('costumer_id', $this->selectedCostumerId)->whereNotNull('do_not_contact_at')->exists(), 403);

        $callingCode = str_starts_with($this->individualCallingCode, '+')
            ? $this->individualCallingCode
            : '+'.$this->individualCallingCode;

        $followUpAt = $this->responseReceivedNow ? null : ($this->followUpAt ?: null);
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
        $this->forgetFollowUpData();
        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-contact-modal', 'type' => 'contact']);
        session()->flash('messages', $this->responseReceivedNow
            ? 'Contact et réponse enregistrés. Le suivi de ce client est terminé.'
            : 'Contact enregistré dans l’historique.');
    }

    public function openResponseModal(int $historyId): void
    {
        $history = CostumerContactHistory::with('costumer')->findOrFail($historyId);
        $this->selectedHistoryId = $history->id;
        $this->selectedCostumerId = $history->costumer_id;
        $this->response = $history->response ?? '';
        $this->sentiment = $history->sentiment ?? 'neutral';
        $this->showResponseModal = true;
    }

    public function saveResponse(): void
    {
        $this->validate([
            'selectedHistoryId' => 'required|exists:costumer_contact_histories,id',
            'response' => 'required|string|max:10000',
            'sentiment' => 'required|in:positive,neutral,negative',
        ]);

        CostumerContactHistory::whereKey($this->selectedHistoryId)->update([
            'response' => $this->response,
            'sentiment' => $this->sentiment,
            'responded_at' => now(),
            'follow_up_at' => null,
        ]);
        CostumerContactPreference::where('costumer_id', $this->selectedCostumerId)
            ->update(['next_follow_up_at' => null]);
        $this->forgetFollowUpData();
        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-response-modal', 'type' => 'response']);
        session()->flash('messages', 'Réponse enregistrée. Le suivi de ce client est terminé.');
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
            $this->reset(['selectedCostumerId', 'notes', 'selectedTemplateId', 'responseReceivedNow', 'immediateResponse', 'immediateSentiment']);
        } elseif ($modal === 'response') {
            $this->showResponseModal = false;
            $this->reset(['selectedHistoryId', 'selectedCostumerId', 'response', 'sentiment']);
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
            'defaultCountryCallingCode' => ['required', 'regex:/^\+?[0-9]{1,4}$/'],
        ]);

        CostumerContactSetting::query()->updateOrCreate(['id' => 1], [
            'default_follow_up_days' => $this->defaultFollowUpDays,
            'max_follow_ups' => $this->maxFollowUps,
            'default_country_calling_code' => str_starts_with($this->defaultCountryCallingCode, '+')
                ? $this->defaultCountryCallingCode
                : '+'.$this->defaultCountryCallingCode,
        ]);

        $this->defaultCountryCallingCode = str_starts_with($this->defaultCountryCallingCode, '+')
            ? $this->defaultCountryCallingCode
            : '+'.$this->defaultCountryCallingCode;

        session()->flash('messages', 'Les paramètres de relance ont été enregistrés.');
    }

    protected function loadSettings(): void
    {
        $settings = CostumerContactSetting::first();
        if ($settings) {
            $this->defaultFollowUpDays = $settings->default_follow_up_days;
            $this->defaultCountryCallingCode = $settings->default_country_calling_code;
            $this->maxFollowUps = $settings->max_follow_ups;
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
            $nextFollowUpAt = Carbon::parse($orderDate)->addDays((int) $this->defaultFollowUpDays);
        }
        if (!$latest) {
            return $nextFollowUpAt && $nextFollowUpAt->isPast() ? 'to_follow_up' : 'not_contacted';
        }

        if ($latest->follow_up_decision === 'stop' && !$hasNewScheduledFollowUp) {
            return 'closed';
        }

        if ($latest->follow_up_decision === 'stop' && $hasNewScheduledFollowUp) {
            return $nextFollowUpAt && $nextFollowUpAt->isPast() ? 'to_follow_up' : 'contacted';
        }

        if ($costumer->follow_up_count >= $this->maxFollowUps
            && $latest->follow_up_decision !== 'continue'
            && $nextFollowUpAt && $nextFollowUpAt->isPast()) {
            return 'review_required';
        }

        return $nextFollowUpAt && $nextFollowUpAt->isPast() ? 'to_follow_up' : 'contacted';
    }

    protected function statusQuery(string $status): Builder
    {
        $query = Costumer::query();

        if ($status === 'not_contacted') {
            return $query->whereDoesntHave('contactHistories')
                ->whereDoesntHave('contactPreference', fn ($preference) => $preference
                    ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()))
                ->whereNotExists(fn ($dueOrder) => $this->constrainLatestOrderDue($dueOrder))
                ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('do_not_contact_at'));
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
                        ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()))
                        ->orWhere(function ($historyDue) {
                        $historyDue->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                                ->where(function ($latestHistory) {
                                    $this->whereLatestContactHistory($latestHistory, fn ($history) => $history
                                    ->whereNull('follow_up_decision')->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now()));
                                });
                        });
                })
                ->where(function ($latestHistory) {
                    $this->whereLatestContactHistory($latestHistory, fn ($history) => $history->whereNull('follow_up_decision'));
                });
        }

        if ($status === 'to_follow_up') {
            return $query->where(function ($due) {
                $due->where(function ($scheduledDue) {
                    $scheduledDue->whereHas('contactPreference', fn ($preference) => $preference
                        ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()))
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
                                    ->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now())
                                    ->where(fn ($decision) => $decision->whereNull('follow_up_decision')->orWhere('follow_up_decision', 'continue')));
                            });
                    })
                    ->orWhere(function ($orderDue) {
                        $orderDue->whereDoesntHave('contactHistories')
                            ->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                            ->whereExists(fn ($dueOrder) => $this->constrainLatestOrderDue($dueOrder));
                    });
            });
        }

        if ($status === 'contacted') {
            return $query->whereHas('contactHistories')->where(function ($pending) {
                $pending->whereHas('contactPreference', fn ($preference) => $preference
                    ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '>', now()))
                    ->orWhere(function ($historyPending) {
                        $historyPending->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                            ->where(function ($latestHistory) {
                                $this->whereLatestContactHistory($latestHistory, fn ($history) => $history
                                ->where(function ($followUp) {
                                    $followUp->whereNull('follow_up_at')->orWhere('follow_up_at', '>', now());
                                })->where(function ($decision) {
                                    $decision->whereNull('follow_up_decision')->orWhere('follow_up_decision', '!=', 'stop');
                                }));
                            });
                    });
            });
        }

        return $query;
    }

    /**
     * Match the legacy VARCHAR orders.costumer_id to the numeric customer ID
     * without casting the indexed orders column. This lets MySQL use
     * orders_costumer_id_id_follow_up_idx instead of materializing every
     * customer's latest order for each follow-up count.
     */
    protected function constrainLatestOrderDue(\Illuminate\Database\Query\Builder $query): \Illuminate\Database\Query\Builder
    {
        $now = now();
        $days = (int) $this->defaultFollowUpDays;

        return $query->selectRaw('1')
            ->from('orders as follow_up_due_orders')
            ->whereRaw('follow_up_due_orders.costumer_id = CAST(costumers.id AS CHAR)')
            ->whereRaw(
                'follow_up_due_orders.id = (SELECT MAX(latest_follow_up_order.id) FROM orders AS latest_follow_up_order WHERE latest_follow_up_order.costumer_id = follow_up_due_orders.costumer_id)'
            )
            ->whereRaw(
                'DATE_ADD(COALESCE(follow_up_due_orders.date_order, follow_up_due_orders.created_at), INTERVAL '.$days.' DAY) <= ?',
                [$now]
            );
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

    protected function followUpCountsCacheKey(): string
    {
        return 'customer-follow-up-counts:'.$this->defaultFollowUpDays.':'.$this->maxFollowUps;
    }

    protected function forgetFollowUpData(): void
    {
        Cache::forget($this->followUpCountsCacheKey());
        $versionKey = 'customer-follow-up-list-version';
        Cache::add($versionKey, 1);
        Cache::increment($versionKey);
    }

    protected function loadFollowUpCounts(): array
    {
        return Cache::remember($this->followUpCountsCacheKey(), now()->addSeconds(20), function () {
            $sentimentCounts = CostumerContactHistory::query()
                ->whereNotNull('responded_at')
                ->selectRaw('sentiment, COUNT(*) as aggregate')
                ->groupBy('sentiment')
                ->pluck('aggregate', 'sentiment');

            return [
                'all' => Costumer::count(),
                'not_contacted' => $this->statusQuery('not_contacted')->count(),
                'contacted' => $this->statusQuery('contacted')->count(),
                'responded' => $this->statusQuery('responded')->count(),
                'to_follow_up' => $this->statusQuery('to_follow_up')->count(),
                'review_required' => $this->statusQuery('review_required')->count(),
                'closed' => $this->statusQuery('closed')->count(),
                'do_not_contact' => $this->statusQuery('do_not_contact')->count(),
                'feedback' => [
                    'positive' => (int) $sentimentCounts->get('positive', 0),
                    'neutral' => (int) $sentimentCounts->get('neutral', 0),
                    'negative' => (int) $sentimentCounts->get('negative', 0),
                    'unclassified' => (int) $sentimentCounts->get(null, 0),
                ],
            ];
        });
    }

    protected function loadFollowUpPage(): array
    {
        $version = Cache::get('customer-follow-up-list-version', 1);
        $cacheKey = 'customer-follow-up-page:'.$version.':'.hash('sha256', serialize([
            $this->search,
            $this->statusFilter,
            (int) $this->page,
            $this->defaultFollowUpDays,
            $this->maxFollowUps,
        ]));

        return Cache::remember($cacheKey, now()->addSeconds(20), function () {
            $orderMetrics = Order::query()
                ->select('costumer_id')
                ->selectRaw('COUNT(*) as orders_count')
                ->selectRaw('MAX(created_at) as orders_max_created_at')
                ->groupBy('costumer_id');

            $query = $this->searchQuery(Costumer::query())
                ->leftJoinSub($orderMetrics, 'order_metrics', fn ($join) => $join
                    ->whereRaw('order_metrics.costumer_id = CAST(costumers.id AS CHAR)'))
                ->select('costumers.*')
                ->selectRaw('COALESCE(order_metrics.orders_count, 0) as orders_count')
                ->addSelect('order_metrics.orders_max_created_at')
                ->with(['latestContactHistory.user', 'contactPreference'])
                ->withCount([
                    'contactHistories as contact_histories_count' => fn ($history) => $history->whereNotNull('responded_at'),
                    'contactHistories as follow_up_count' => fn ($history) => $history->where('contact_type', 'follow_up'),
                ]);

            if ($this->statusFilter !== 'all') {
                $statusIds = $this->searchQuery($this->statusQuery($this->statusFilter))->select('costumers.id');
                $query->whereIn('costumers.id', $statusIds);
            }

            $costumers = $query
                ->orderByDesc('orders_count')
                ->orderByDesc('orders_max_created_at')
                ->orderBy('name')
                ->paginate(15);

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
        [$costumers, $statuses] = $this->loadFollowUpPage();

        $selectedCostumer = null;
        if ($this->selectedCostumerId) {
            $selectedCostumerQuery = Costumer::with('contactPreference');
            $selectedCostumer = $selectedCostumerQuery->find($this->selectedCostumerId);
            if ($selectedCostumer && $this->showContactModal) {
                $selectedCostumer->setRelation('latestOrder', Order::with('orderItems.product')
                    ->where('costumer_id', (string) $selectedCostumer->id)
                    ->orderByDesc('id')
                    ->first());
            }
        }

        return view('livewire.costumer-follow-up', [
            'costumers' => $costumers,
            'counts' => $counts,
            'statuses' => $statuses,
            'history' => $this->showHistoryModal && $this->selectedCostumerId
                ? CostumerContactHistory::with(['user', 'messageTemplate'])->where('costumer_id', $this->selectedCostumerId)->orderByDesc('contacted_at')->get()
                : collect(),
            'selectedCostumer' => $selectedCostumer,
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
