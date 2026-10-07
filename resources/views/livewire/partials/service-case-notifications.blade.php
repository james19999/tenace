<div class="sc-notification-control">
    <button type="button" class="sc-notification-button" wire:click="toggleNotifications" aria-label="Notifications du service client" aria-expanded="{{ $showNotifications ? 'true' : 'false' }}">
        <span class="material-icons">notifications</span>
        <span>Notifications</span>
        @if($serviceNotificationCount > 0)
            <span class="sc-notification-badge">{{ $serviceNotificationCount > 99 ? '99+' : $serviceNotificationCount }}</span>
        @endif
    </button>

    @if($showNotifications)
        <section class="sc-notification-dropdown" aria-label="Notifications du service client">
            <header><strong>Notifications</strong><span>{{ $serviceNotificationCount }} non lue(s)</span></header>
            @forelse($serviceNotifications as $notification)
                <article>
                    <a href="{{ $notification->data['url'] ?? route('service-cases.index') }}">
                        <span class="material-icons">{{ str_contains($notification->data['reminder_type'] ?? '', 'due') || str_contains($notification->data['reminder_type'] ?? '', 'overdue') ? 'event' : 'folder_shared' }}</span>
                        <span><strong>{{ $notification->data['message'] ?? 'Mise à jour d’un dossier client.' }}</strong><small>{{ $notification->created_at->diffForHumans() }}</small></span>
                    </a>
                    <button type="button" wire:click="markNotificationRead('{{ $notification->id }}')" aria-label="Marquer comme lue"><span class="material-icons">done</span></button>
                </article>
            @empty
                <p class="sc-notification-empty">Aucune nouvelle notification.</p>
            @endforelse
        </section>
    @endif
</div>
