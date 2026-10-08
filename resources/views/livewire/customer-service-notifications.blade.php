<li class="nav-item icon sc-global-notification" wire:init="showUnreadOnLogin" wire:poll.30s="checkForNewNotifications">
    <button type="button" class="{{ $notificationCount > 0 ? 'has-unread' : '' }}" wire:click="toggle" aria-label="Notifications du service client" aria-expanded="{{ $open ? 'true' : 'false' }}">
        <span class="material-icons">notifications</span>
        @if($notificationCount > 0)
            <span class="sc-global-notification-badge is-unread">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
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
                    <a href="{{ $notification->data['url'] ?? route('service-cases.index') }}" wire:click.prevent="openNotification('{{ $notification->id }}')">
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

    <audio id="customer-service-notification-audio" preload="auto">
        <source src="{{ asset('sounds/notification.ogg') }}" type="audio/ogg">
        <source src="{{ asset('sounds/notification.mp3') }}" type="audio/mpeg">
    </audio>
    <div id="customer-service-notification-push" class="sc-notification-push" role="status" aria-live="polite"></div>
</li>

@once
    <script>
        (function () {
            if (window.customerServiceNotificationPushBound) return;
            window.customerServiceNotificationPushBound = true;

            var pendingSound = false;
            var soundReady = false;
            var playSound = function () {
                var audio = document.getElementById('customer-service-notification-audio');
                if (!audio) return;
                audio.currentTime = 0;
                var attempt = audio.play();
                if (attempt && typeof attempt.then === 'function') {
                    attempt.then(function () {
                        pendingSound = false;
                        soundReady = true;
                    }).catch(function () {
                        pendingSound = true;
                    });
                }
            };

            var resumeSound = function () {
                if (!pendingSound || soundReady) return;
                playSound();
            };
            document.addEventListener('pointerdown', resumeSound);
            document.addEventListener('keydown', resumeSound);

            window.addEventListener('customer-service-notifications-push', function (event) {
                var payload = event.detail || {};
                var item = payload.notification || {};
                var host = document.getElementById('customer-service-notification-push');
                if (!host || !item.message) return;

                var toast = document.createElement('div');
                toast.className = 'sc-notification-toast';
                var icon = document.createElement('span');
                icon.className = 'material-icons sc-notification-toast-icon';
                icon.textContent = 'notifications_active';
                var copy = document.createElement('span');
                copy.className = 'sc-notification-toast-copy';
                var title = document.createElement('strong');
                title.textContent = item.title || 'Nouvelle notification';
                var message = document.createElement('span');
                message.textContent = item.message;
                copy.appendChild(title);
                copy.appendChild(message);
                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'sc-notification-toast-close';
                close.setAttribute('aria-label', 'Fermer');
                close.textContent = '×';
                close.addEventListener('click', function () { toast.remove(); });

                var link = document.createElement('a');
                link.className = 'sc-notification-toast-link';
                link.href = item.url || '#';
                link.setAttribute('aria-label', 'Ouvrir la notification');
                link.addEventListener('click', function (clickEvent) {
                    var root = host.closest('[wire\\:id]');
                    if (!item.id || !root || !window.Livewire) return;
                    clickEvent.preventDefault();
                    window.Livewire.find(root.getAttribute('wire:id')).call('openNotification', item.id);
                });
                link.appendChild(icon);
                link.appendChild(copy);
                toast.appendChild(link);
                toast.appendChild(close);
                host.appendChild(toast);
                window.setTimeout(function () {
                    toast.classList.add('is-visible');
                }, 20);
                window.setTimeout(function () {
                    toast.classList.remove('is-visible');
                    window.setTimeout(function () { toast.remove(); }, 300);
                }, 8000);

                if (payload.playSound) playSound();
            });
        })();
    </script>
@endonce
