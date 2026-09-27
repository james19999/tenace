<?php

namespace App\Http\Livewire;

use App\Models\Costumer;
use App\Models\CostumerContactHistory;
use App\Models\CostumerContactMessageTemplate;
use App\Models\CostumerContactPreference;
use App\Models\CostumerContactSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CostumerFollowUp extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = 'all';
    public $showContactModal = false;
    public $showResponseModal = false;
    public $showHistoryModal = false;
    public $showSettings = false;
    public $showDoNotContactModal = false;
    public $showDeleteTemplateModal = false;
    public $selectedCostumerId;
    public $selectedHistoryId;
    public $deletingTemplateId;
    public $deletingTemplateName = '';
    public $contactType = 'initial';
    public $channel = 'whatsapp';
    public $contactedAt;
    public $followUpAt;
    public $notes = '';
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

    public function openContactModal(int $costumerId, string $type = 'initial'): void
    {
        $costumer = Costumer::with('contactPreference')->findOrFail($costumerId);
        abort_if($costumer->contactPreference && $costumer->contactPreference->do_not_contact_at, 403);
        $this->selectedCostumerId = $costumer->id;
        $this->contactType = $type === 'follow_up' ? 'follow_up' : 'initial';
        $this->channel = 'whatsapp';
        $this->selectedTemplateId = CostumerContactMessageTemplate::where('channel', 'whatsapp')->where('active', true)->value('id');
        $this->individualCallingCode = $costumer->contactPreference->calling_code ?? $this->defaultCountryCallingCode;
        $this->contactedAt = now()->format('Y-m-d\TH:i');
        $this->followUpAt = now()->addDays((int) $this->defaultFollowUpDays)->format('Y-m-d\TH:i');
        $this->notes = '';
        $this->showContactModal = true;
    }

    public function saveContact(): void
    {
        $this->validate([
            'selectedCostumerId' => 'required|exists:costumers,id',
            'contactType' => 'required|in:initial,follow_up',
            'channel' => 'required|in:whatsapp,sms,call,other',
            'contactedAt' => 'required|date',
            'followUpAt' => 'nullable|date|after:contactedAt',
            'individualCallingCode' => ['required', 'regex:/^\\+?[0-9]{1,4}$/'],
            'selectedTemplateId' => 'nullable|exists:costumer_contact_message_templates,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        abort_if(CostumerContactPreference::where('costumer_id', $this->selectedCostumerId)->whereNotNull('do_not_contact_at')->exists(), 403);

        $callingCode = str_starts_with($this->individualCallingCode, '+')
            ? $this->individualCallingCode
            : '+'.$this->individualCallingCode;

        CostumerContactPreference::updateOrCreate(
            ['costumer_id' => $this->selectedCostumerId],
            [
                'calling_code' => $callingCode,
                'next_follow_up_at' => $this->followUpAt ?: null,
                'updated_by' => Auth::id(),
            ]
        );

        CostumerContactHistory::create([
            'costumer_id' => $this->selectedCostumerId,
            'user_id' => Auth::id(),
            'contact_type' => $this->contactType,
            'channel' => $this->channel,
            'contacted_at' => $this->contactedAt,
            'follow_up_at' => $this->followUpAt,
            'message_template_id' => $this->selectedTemplateId,
            'notes' => $this->notes ?: null,
        ]);

        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-contact-modal', 'type' => 'contact']);
        session()->flash('messages', 'Contact enregistré dans l’historique.');
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

        $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => 'follow-up-response-modal', 'type' => 'response']);
        session()->flash('messages', 'Réponse enregistrée. Le suivi de ce client est terminé.');
    }

    public function openHistory(int $costumerId): void
    {
        $this->selectedCostumerId = $costumerId;
        $this->showHistoryModal = true;
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
        ];

        if (isset($modalTypes[$modal])) {
            $this->dispatchBrowserEvent('animate-follow-up-modal-close', ['modal' => $modal, 'type' => $modalTypes[$modal]]);
        }
    }

    public function finishClosingModal(string $modal): void
    {
        if ($modal === 'contact') {
            $this->showContactModal = false;
            $this->reset(['selectedCostumerId', 'notes', 'selectedTemplateId']);
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
        session()->flash('messages', $decision === 'continue'
            ? 'Une nouvelle série de relances est autorisée.'
            : 'Le suivi est clôturé après les relances prévues.');
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
        $nextFollowUpAt = $costumer->contactPreference?->next_follow_up_at ?? $latest?->follow_up_at;
        if (!$latest) {
            return $nextFollowUpAt && $nextFollowUpAt->isPast() ? 'to_follow_up' : 'not_contacted';
        }

        if ($latest->follow_up_decision === 'stop' && !$hasNewScheduledFollowUp) {
            return 'closed';
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
            return $query->whereHas('latestContactHistory', fn ($history) => $history->where('follow_up_decision', 'stop'));
        }

        if ($status === 'review_required') {
            return $query->whereHas('contactHistories', fn ($history) => $history->where('contact_type', 'follow_up'), '>=', $this->maxFollowUps)
                ->where(function ($due) {
                    $due->whereHas('contactPreference', fn ($preference) => $preference
                        ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()))
                        ->orWhere(function ($historyDue) {
                            $historyDue->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                                ->whereHas('latestContactHistory', fn ($history) => $history
                                    ->whereNull('follow_up_decision')->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now()));
                        });
                })
                ->whereHas('latestContactHistory', fn ($history) => $history->whereNull('follow_up_decision'));
        }

        if ($status === 'to_follow_up') {
            return $query->where(function ($due) {
                $due->whereHas('contactPreference', fn ($preference) => $preference
                    ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()))
                    ->orWhere(function ($historyDue) {
                        $historyDue->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                            ->where(function ($attempts) {
                                $attempts->whereHas('contactHistories', fn ($history) => $history->where('contact_type', 'follow_up'), '<', $this->maxFollowUps)
                                    ->orWhereHas('latestContactHistory', fn ($history) => $history->where('follow_up_decision', 'continue'));
                            })
                            ->whereHas('latestContactHistory', fn ($history) => $history
                                ->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now())
                                ->where(fn ($decision) => $decision->whereNull('follow_up_decision')->orWhere('follow_up_decision', 'continue')));
                    });
            });
        }

        if ($status === 'contacted') {
            return $query->whereHas('contactHistories')->where(function ($pending) {
                $pending->whereHas('contactPreference', fn ($preference) => $preference
                    ->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '>', now()))
                    ->orWhere(function ($historyPending) {
                        $historyPending->whereDoesntHave('contactPreference', fn ($preference) => $preference->whereNotNull('next_follow_up_at'))
                            ->whereHas('latestContactHistory', fn ($history) => $history
                                ->where(function ($followUp) {
                                    $followUp->whereNull('follow_up_at')->orWhere('follow_up_at', '>', now());
                                })->where(function ($decision) {
                                    $decision->whereNull('follow_up_decision')->orWhere('follow_up_decision', '!=', 'stop');
                                }));
                    });
            });
        }

        return $query;
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

        $order = $costumer->orders()->with('orderItems.product')->latest()->first();
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

    public function render()
    {
        $counts = [
            'all' => Costumer::count(),
            'not_contacted' => $this->statusQuery('not_contacted')->count(),
            'contacted' => $this->statusQuery('contacted')->count(),
            'responded' => $this->statusQuery('responded')->count(),
            'to_follow_up' => $this->statusQuery('to_follow_up')->count(),
            'review_required' => $this->statusQuery('review_required')->count(),
            'closed' => $this->statusQuery('closed')->count(),
            'do_not_contact' => $this->statusQuery('do_not_contact')->count(),
        ];

        $query = $this->searchQuery(Costumer::query())
            ->with(['latestContactHistory.user', 'contactPreference'])
            ->withCount([
                'contactHistories as contact_histories_count' => fn ($history) => $history->whereNotNull('responded_at'),
                'contactHistories as follow_up_count' => fn ($history) => $history->where('contact_type', 'follow_up'),
            ]);

        if ($this->statusFilter !== 'all') {
            $statusIds = $this->searchQuery($this->statusQuery($this->statusFilter))->select('costumers.id');
            $query->whereIn('costumers.id', $statusIds);
        }

        $costumers = $query->orderBy('name')->paginate(15);
        $statuses = $costumers->getCollection()->mapWithKeys(fn ($costumer) => [$costumer->id => $this->statusFor($costumer)]);

        return view('livewire.costumer-follow-up', [
            'costumers' => $costumers,
            'counts' => $counts,
            'statuses' => $statuses,
            'history' => $this->showHistoryModal && $this->selectedCostumerId
                ? CostumerContactHistory::with(['user', 'messageTemplate'])->where('costumer_id', $this->selectedCostumerId)->orderByDesc('contacted_at')->get()
                : collect(),
            'selectedCostumer' => $this->selectedCostumerId ? Costumer::find($this->selectedCostumerId) : null,
            'contactUrl' => $this->selectedCostumerId ? $this->contactUrl(Costumer::find($this->selectedCostumerId), $this->channel) : null,
            'messageTemplates' => CostumerContactMessageTemplate::where('active', true)->where('channel', $this->channel)->orderBy('name')->get(),
            'allMessageTemplates' => CostumerContactMessageTemplate::orderBy('channel')->orderBy('name')->get(),
            'selectedMessage' => $this->selectedCostumerId ? $this->selectedMessage(Costumer::find($this->selectedCostumerId)) : null,
        ])->extends('layouts.admin')->section('content');
    }
}
