<!-- PWA Install & Update Notification UI & Script -->
<style>
    .pwa-toast-banner {
        position: fixed;
        bottom: 24px;
        right: 24px;
        max-width: 400px;
        width: calc(100% - 48px);
        background: #ffffff;
        color: #1e293b;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        border-left: 5px solid #7e1615;
        padding: 16px;
        display: none;
        align-items: flex-start;
        gap: 14px;
        z-index: 99999;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        box-sizing: border-box;
        animation: pwaSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes pwaSlideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .pwa-toast-banner img.pwa-app-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .pwa-toast-banner .pwa-icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #fdf2f2;
        color: #7e1615;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .pwa-toast-content {
        flex: 1;
        min-width: 0;
    }

    .pwa-toast-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px 0;
        line-height: 1.25;
    }

    .pwa-toast-desc {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 12px 0;
        line-height: 1.4;
    }

    .pwa-toast-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pwa-btn-primary {
        background-color: #7e1615;
        color: #ffffff !important;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.1s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pwa-btn-primary:hover {
        background-color: #631110;
        color: #ffffff;
    }

    .pwa-btn-primary:active {
        transform: scale(0.98);
    }

    .pwa-btn-secondary {
        background: transparent;
        color: #64748b !important;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s, color 0.2s;
    }

    .pwa-btn-secondary:hover {
        background: #f1f5f9;
        color: #334155 !important;
    }

    .pwa-toast-close {
        position: absolute;
        top: 10px;
        right: 10px;
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 18px;
        cursor: pointer;
        line-height: 1;
        padding: 4px;
    }

    .pwa-toast-close:hover {
        color: #475569;
    }

    @media (max-width: 480px) {
        .pwa-toast-banner {
            bottom: 12px;
            right: 12px;
            left: 12px;
            width: auto;
            max-width: none;
            padding: 14px;
        }
    }
</style>

<!-- Banner Installation PWA -->
<div id="pwa-install-banner" class="pwa-toast-banner" role="dialog" aria-labelledby="pwa-install-title">
    <button type="button" class="pwa-toast-close" id="pwa-btn-close-install" aria-label="Fermer">&times;</button>
    <img src="{{ asset('assets/images/tena.png') }}" alt="TENACE Icon" class="pwa-app-icon" />
    <div class="pwa-toast-content">
        <h4 id="pwa-install-title" class="pwa-toast-title">Installer l'application TENACE</h4>
        <p class="pwa-toast-desc">Installez l'application sur votre appareil pour un accès rapide, fluide et direct depuis votre écran d'accueil.</p>
        <div class="pwa-toast-actions">
            <button type="button" id="pwa-btn-install" class="pwa-btn-primary">Installer</button>
            <button type="button" id="pwa-btn-dismiss-install" class="pwa-btn-secondary">Plus tard</button>
        </div>
    </div>
</div>

<!-- Banner Mise à jour PWA -->
<div id="pwa-update-banner" class="pwa-toast-banner" role="alert" aria-live="assertive">
    <div class="pwa-icon-badge">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
        </svg>
    </div>
    <div class="pwa-toast-content">
        <h4 class="pwa-toast-title">Mise à jour disponible !</h4>
        <p class="pwa-toast-desc">Une nouvelle version de TENACE est prête. Mettez à jour maintenant pour profiter des dernières nouveautés.</p>
        <div class="pwa-toast-actions">
            <button type="button" id="pwa-btn-update" class="pwa-btn-primary">Mettre à jour</button>
            <button type="button" id="pwa-btn-dismiss-update" class="pwa-btn-secondary">Plus tard</button>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        // 1. Détection si l'application est déjà installée
        function isPwaInstalled() {
            return window.matchMedia('(display-mode: standalone)').matches ||
                   window.navigator.standalone === true ||
                   localStorage.getItem('pwa_installed') === 'true';
        }

        // Si l'application tourne déjà en standalone, s'assurer que pwa_installed est bien enregistré
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            localStorage.setItem('pwa_installed', 'true');
        }

        let deferredPrompt = null;
        const installBanner = document.getElementById('pwa-install-banner');
        const btnInstall = document.getElementById('pwa-btn-install');
        const btnDismissInstall = document.getElementById('pwa-btn-dismiss-install');
        const btnCloseInstall = document.getElementById('pwa-btn-close-install');

        function hideInstallBanner(sessionDismiss = true) {
            if (installBanner) {
                installBanner.style.display = 'none';
            }
            if (sessionDismiss) {
                sessionStorage.setItem('pwa_install_dismissed', 'true');
            }
        }

        // Écoute de l'événement natif d'installation Chrome / Edge / Android
        window.addEventListener('beforeinstallprompt', (e) => {
            // Si déjà installée, on n'affiche JAMAIS la bannière
            if (isPwaInstalled()) {
                return;
            }

            // Si l'utilisateur a fermé la bannière durant cette session, ne pas le déranger
            if (sessionStorage.getItem('pwa_install_dismissed') === 'true') {
                return;
            }

            e.preventDefault();
            deferredPrompt = e;

            if (installBanner) {
                installBanner.style.display = 'flex';
            }
        });

        // Clic sur "Installer"
        if (btnInstall) {
            btnInstall.addEventListener('click', async () => {
                if (!deferredPrompt) {
                    hideInstallBanner(false);
                    return;
                }

                deferredPrompt.prompt();
                const choice = await deferredPrompt.userChoice;

                if (choice.outcome === 'accepted') {
                    // Marqué comme définitivement installé
                    localStorage.setItem('pwa_installed', 'true');
                }

                deferredPrompt = null;
                hideInstallBanner(false);
            });
        }

        // Clic sur "Plus tard" ou la croix
        if (btnDismissInstall) {
            btnDismissInstall.addEventListener('click', () => hideInstallBanner(true));
        }
        if (btnCloseInstall) {
            btnCloseInstall.addEventListener('click', () => hideInstallBanner(true));
        }

        // Détection de la fin d'installation
        window.addEventListener('appinstalled', () => {
            localStorage.setItem('pwa_installed', 'true');
            deferredPrompt = null;
            hideInstallBanner(false);
        });

        // 2. Gestion du Service Worker et de la notification de mise à jour
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then((registration) => {
                    function promptUserForUpdate(waitingWorker) {
                        const updateBanner = document.getElementById('pwa-update-banner');
                        const btnUpdate = document.getElementById('pwa-btn-update');
                        const btnDismissUpdate = document.getElementById('pwa-btn-dismiss-update');

                        if (updateBanner) {
                            updateBanner.style.display = 'flex';
                        }

                        if (btnUpdate) {
                            btnUpdate.onclick = () => {
                                btnUpdate.disabled = true;
                                btnUpdate.textContent = 'Actualisation...';
                                if (waitingWorker) {
                                    waitingWorker.postMessage({ type: 'SKIP_WAITING' });
                                } else {
                                    window.location.reload();
                                }
                            };
                        }

                        if (btnDismissUpdate) {
                            btnDismissUpdate.onclick = () => {
                                if (updateBanner) {
                                    updateBanner.style.display = 'none';
                                }
                            };
                        }
                    }

                    // Cas 1 : Nouveau worker en cours d'installation
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        if (!newWorker) return;

                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                promptUserForUpdate(newWorker);
                            }
                        });
                    });

                    // Cas 2 : Un worker est déjà en attente (ex: page rouverte)
                    if (registration.waiting && navigator.serviceWorker.controller) {
                        promptUserForUpdate(registration.waiting);
                    }

                    // Vérification périodique des mises à jour toutes les 30 minutes
                    setInterval(() => {
                        registration.update().catch(() => {});
                    }, 30 * 60 * 1000);

                    // Vérification lorsque l'utilisateur revient sur l'onglet
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            registration.update().catch(() => {});
                        }
                    });
                }).catch((err) => {
                    console.warn('[PWA] Service Worker registration failed:', err);
                });

                // Dès que le nouveau service worker prend le contrôle, recharger la page
                let isReloading = false;
                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    if (!isReloading) {
                        isReloading = true;
                        window.location.reload();
                    }
                });
            });
        }
    })();
</script>
