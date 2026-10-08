<div class="loyalty-page loyalty-settings-page">
    @if (session()->has('loyaltyMessage'))
        <div class="alert alert-success">{{ session('loyaltyMessage') }}</div>
    @endif

    @include('livewire.customer-loyalty-rules-form')

    <div class="loyalty-settings-bottom">
        <a href="{{ route('customer-loyalty.index') }}" class="btn loyalty-secondary-button"><span class="material-icons">arrow_back</span>Quitter les règles et revenir au tableau fidélité</a>
    </div>
</div>
