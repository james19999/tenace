<?php

namespace App\Http\Livewire;

use App\Models\Costumer;
use App\Models\CostumerContactHistory;
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
    public $selectedCostumerId;
    public $selectedHistoryId;
    public $contactType = 'initial';
    public $channel = 'whatsapp';
    public $contactedAt;
    public $followUpAt;
    public $notes = '';
    public $response = '';
    public $defaultFollowUpDays = 14;
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

    public function openContactModal(int $costumerId, string $type = 'initial'): void
    {
        $costumer = Costumer::findOrFail($costumerId);
        $this->selectedCostumerId = $costumer->id;
        $this->contactType = $type === 'follow_up' ? 'follow_up' : 'initial';
        $this->channel = 'whatsapp';
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
            'notes' => 'nullable|string|max:5000',
        ]);

        CostumerContactHistory::create([
            'costumer_id' => $this->selectedCostumerId,
            'user_id' => Auth::id(),
            'contact_type' => $this->contactType,
            'channel' => $this->channel,
            'contacted_at' => $this->contactedAt,
            'follow_up_at' => $this->followUpAt,
            'notes' => $this->notes ?: null,
        ]);

        $this->showContactModal = false;
        $this->reset(['selectedCostumerId', 'notes']);
        session()->flash('messages', 'Contact enregistré dans l’historique.');
    }

    public function openResponseModal(int $historyId): void
    {
        $history = CostumerContactHistory::with('costumer')->findOrFail($historyId);
        $this->selectedHistoryId = $history->id;
        $this->selectedCostumerId = $history->costumer_id;
        $this->response = $history->response ?? '';
        $this->showResponseModal = true;
    }

    public function saveResponse(): void
    {
        $this->validate([
            'selectedHistoryId' => 'required|exists:costumer_contact_histories,id',
            'response' => 'required|string|max:10000',
        ]);

        CostumerContactHistory::whereKey($this->selectedHistoryId)->update([
            'response' => $this->response,
            'responded_at' => now(),
            'follow_up_at' => null,
        ]);

        $this->showResponseModal = false;
        $this->reset(['selectedHistoryId', 'selectedCostumerId', 'response']);
        session()->flash('messages', 'Réponse enregistrée. Le suivi de ce client est terminé.');
    }

    public function openHistory(int $costumerId): void
    {
        $this->selectedCostumerId = $costumerId;
        $this->showHistoryModal = true;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'defaultFollowUpDays' => 'required|integer|min:1|max:365',
            'defaultCountryCallingCode' => ['required', 'regex:/^\+?[0-9]{1,4}$/'],
        ]);

        CostumerContactSetting::query()->updateOrCreate(['id' => 1], [
            'default_follow_up_days' => $this->defaultFollowUpDays,
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
        }
    }

    protected function statusFor(Costumer $costumer): string
    {
        if ($costumer->contact_histories_count > 0) {
            return 'responded';
        }

        $latest = $costumer->latestContactHistory;
        if (!$latest) {
            return 'not_contacted';
        }

        return $latest->follow_up_at && $latest->follow_up_at->isPast() ? 'to_follow_up' : 'contacted';
    }

    protected function statusQuery(string $status): Builder
    {
        $query = Costumer::query();

        if ($status === 'not_contacted') {
            return $query->whereDoesntHave('contactHistories');
        }

        if ($status === 'responded') {
            return $query->whereHas('contactHistories', fn ($history) => $history->whereNotNull('responded_at'));
        }

        $query->whereDoesntHave('contactHistories', fn ($history) => $history->whereNotNull('responded_at'));

        if ($status === 'to_follow_up') {
            return $query->whereHas('latestContactHistory', fn ($history) => $history
                ->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now()));
        }

        if ($status === 'contacted') {
            return $query->whereHas('latestContactHistory', fn ($history) => $history
                ->where(function ($followUp) {
                    $followUp->whereNull('follow_up_at')->orWhere('follow_up_at', '>', now());
                }));
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

    protected function contactUrl(Costumer $costumer, string $channel): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $costumer->phone);
        if (!$phone) {
            return null;
        }

        if (str_starts_with(trim((string) $costumer->phone), '00')) {
            $phone = substr($phone, 2);
        } elseif (!str_starts_with(trim((string) $costumer->phone), '+')) {
            $callingCode = ltrim($this->defaultCountryCallingCode, '+');
            $phone = str_starts_with($phone, $callingCode) ? $phone : $callingCode.$phone;
        }

        if ($channel === 'whatsapp') {
            $message = 'Bonjour '.$costumer->name.', nous aimerions recueillir votre avis sur votre expérience avec nous. Merci de nous répondre.';
            return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
        }

        if ($channel === 'sms') {
            return 'sms:+'.$phone;
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
        ];

        $query = $this->searchQuery(Costumer::query())
            ->with(['latestContactHistory.user'])
            ->withCount(['contactHistories as contact_histories_count' => fn ($history) => $history->whereNotNull('responded_at')]);

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
                ? CostumerContactHistory::with('user')->where('costumer_id', $this->selectedCostumerId)->orderByDesc('contacted_at')->get()
                : collect(),
            'selectedCostumer' => $this->selectedCostumerId ? Costumer::find($this->selectedCostumerId) : null,
            'contactUrl' => $this->selectedCostumerId ? $this->contactUrl(Costumer::find($this->selectedCostumerId), $this->channel) : null,
        ])->extends('layouts.admin')->section('content');
    }
}
