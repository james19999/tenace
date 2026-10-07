<li class="nav-item icon sc-global-notification" wire:poll.60s>
    <button type="button" wire:click="toggle" aria-label="Notifications du service client" aria-expanded="{{ $open ? 'true' : 'false' }}">
        <span class="material-icons">notifications</span>
        @if($notificationCount > 0)
            <span class="sc-global-notification-badge">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
        @endif
    </button>

    @if($open)
        <section class="sc-global-notification-menu" aria-label="Notifications du service client">
            <header class="sc-global-notification-head">
                <span class="sc-global-notification-title-icon"><span class="material-icons">notifications_active</span></span>
                <span class="sc-global-notification-title"><strong>Notifications</strong><small>Service client</small></span>
                <span class="sc-global-notification-unread">{{ $notificationCount }} non lue(s)</span>
            </header>
            @forelse($notifications as $notification)
                <article>
                    <a href="{{ $notification->data['url'] ?? route('service-cases.index') }}">
                        <span class="sc-global-notification-icon"><span class="material-icons">{{ str_contains($notification->data['reminder_type'] ?? '', 'due') || str_contains($notification->data['reminder_type'] ?? '', 'overdue') ? 'event' : 'folder_shared' }}</span></span>
                        <span class="sc-global-notification-copy"><strong>{{ $notification->data['message'] ?? 'Mise à jour d’un dossier client.' }}</strong><small>@if(!empty($notification->data['case_number'])){{ $notification->data['case_number'] }} · @endif{{ $notification->created_at->diffForHumans() }}</small></span>
                    </a>
                    <button type="button" wire:click="markAsRead('{{ $notification->id }}')" aria-label="Marquer comme lue" title="Marquer comme lue"><span class="material-icons">done</span></button>
                </article>
            @empty
                <div class="sc-global-notification-empty"><span class="material-icons">notifications_none</span><strong>Tout est à jour</strong><span>Aucune notification non lue.</span></div>
            @endforelse
            <a class="sc-global-notification-footer" href="{{ route('service-cases.index') }}">Ouvrir le suivi des dossiers<span class="material-icons">arrow_forward</span></a>
        </section>
    @endif
</li>
