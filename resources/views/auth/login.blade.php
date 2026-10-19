<!DOCTYPE html>
<html lang="fr" data-app-theme="{{ $appTheme['appearance_mode'] ?? 'light' }}">


<!-- Mirrored from htmlthemes.gitlab.io/admin/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 May 2023 21:04:31 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <meta charset="UTF-8">
    <title>{{ env('APP_NAME') }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <style>
        :root {
            --app-primary: {{ $appTheme['primary_color'] }};
            --app-secondary: {{ $appTheme['secondary_color'] }};
            --app-accent: {{ $appTheme['accent_color'] }};
            --app-background: {{ $appTheme['background_color'] }};
            --app-surface: {{ $appTheme['surface_color'] }};
            --app-text: {{ $appTheme['text_color'] }};
            --app-primary-contrast: {{ $appTheme['primary_contrast_color'] }};
            --app-radius: {{ ['square' => '0px', 'soft' => '8px', 'rounded' => '16px'][$appTheme['corner_style']] ?? '8px' }};
        }
        html, body, .main-wrapper, .login-shell { min-height:100%; }
        body { background:var(--app-background); color:var(--app-text); }
        .login-shell { display:flex; min-height:100vh; padding:28px 16px; background:linear-gradient(135deg,var(--app-primary),var(--app-secondary)); }
        .login-card { width:100%; max-width:430px; align-self:center; margin:0 auto !important; padding:34px 30px 24px !important; border:1px solid rgba(255,255,255,.28); border-radius:var(--app-radius) !important; background:var(--app-surface); color:var(--app-text); box-shadow:0 22px 60px rgba(0,0,0,.2); }
        .login-card h4 { color:var(--app-text); font-weight:750; }
        .login-card .form-control { min-height:46px; border-radius:var(--app-radius); }
        .login-card .btn-primary { min-height:46px; border-color:var(--app-primary); background:var(--app-primary); color:var(--app-primary-contrast); }
        .login-card .form-check-label { color:var(--app-text); }
        .login-card .login-credit { color:var(--app-primary); font-size:12px; font-weight:700; }
        html[data-app-theme="dark"] body { background:#14171c; }
        html[data-app-theme="dark"] .login-shell { background:radial-gradient(ellipse at top left,color-mix(in srgb,var(--app-primary) 24%,#14171c),transparent 55%),#14171c; }
        html[data-app-theme="dark"] .login-card { border-color:#373f4b; }
        html[data-app-theme="dark"] .login-card .form-control { background:#171b21; color:#edf1f5; border-color:#373f4b; }
        html[data-app-theme="dark"] .login-card .form-control::placeholder { color:#a8b1bd; }
        @media(max-width:575px) { .login-shell { padding:18px 12px; } .login-card { padding:26px 18px 18px !important; } }
    </style>

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">

    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css') }}">

    @include('partials.pwa-head')
</head>

<body>
    <div class="overlay-mask"></div>
    <div class="main-wrapper ">
        <div class="main-content container-fluid login-shell">
            @if (session()->has('messages'))
            <div class="alert alert-success">{{ session('messages') }}</div>
           @endif
            @if (session()->has('session_expired'))
            <div class="alert alert-warning d-flex align-items-center gap-2" role="alert" style="font-weight:600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                {{ session('session_expired') }}
            </div>
            @endif
            <div class="row w-100 h-100 m-0">
                <div class="col-10 col-sm-8 col-md-6 col-lg-4 col-xl-3 card shadow login-card">
                    @php
                    $settings=App\Models\Setting::all();
                    @endphp
                    @forelse ($settings as $setting )
                    <img src="{{ url('image/',$setting->img) }}" height="50" alt="Moss Logo" class="logo justify-content-center d-flex mx-auto mb-3">
                      <h4 style="text-align: center">{{ $setting->name ?? '' }}</h4>

                    @empty

                    <img src="{{ asset('assets/images/tena.png') }}" height="50" alt="Moss Logo" class="logo justify-content-center d-flex mx-auto mb-3">
                    @endforelse
                    <form id="login-form" method="POST" action="{{ route('login') }}" class="p-3">
                         @csrf
                        <div class="form-group">
                            <input  type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Email"required autocomplete="email" autofocus>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <input type="password" id="password" placeholder="Mot de passe" class="form-control @error('password') is-invalid @enderror" name="password" required >
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                           @enderror
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="showPassword">
                            <label class="form-check-label" for="showPassword" style="cursor: pointer;">Voir le mot de passe</label>
                        </div>

                        <button id="login-submit" type="submit" class="btn btn-primary btn-block my-4 d-flex align-items-center justify-content-center" style="min-height: 44px; font-weight: 600;">
                            <span id="login-spinner" class="spinner-border spinner-border-sm mr-2" style="display: none;" role="status" aria-hidden="true"></span>
                            <span id="login-submit-label">Connectez-vous</span>
                        </button>
                        <hr style="padding-top: 8px">
                        <a href="https://digital-services-home.com/" class="login-credit d-block text-center">@ DIGITAL SERVICES</a>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <script src="{{ asset('assets/js/vendor.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/app.bundle.js') }}"></script>

    <script>

        const passwordInput = document.getElementById('password');
        const showPasswordCheckbox = document.getElementById('showPassword');

        // Add an event listener to the checkbox to toggle password visibility
        showPasswordCheckbox.addEventListener('change', function() {
            // If the checkbox is checked, show the password; otherwise, hide it
            passwordInput.type = this.checked ? 'text' : 'password';
        });

        const loginForm = document.getElementById('login-form');
        const loginButton = document.getElementById('login-submit');
        const loginSpinner = document.getElementById('login-spinner');
        const loginButtonLabel = document.getElementById('login-submit-label');

        function startLoadingState() {
            loginSpinner.style.setProperty('display', 'inline-block', 'important');
            loginButtonLabel.textContent = 'Connexion en cours...';
            loginButton.style.pointerEvents = 'none';
            loginButton.classList.add('disabled');
        }

        function resetLoadingState() {
            loginButton.dataset.submitting = 'false';
            loginButton.style.pointerEvents = '';
            loginButton.classList.remove('disabled');
            loginSpinner.style.setProperty('display', 'none', 'important');
            loginButtonLabel.textContent = 'Connectez-vous';
        }

        loginForm.addEventListener('submit', function (event) {
            // Si des champs requis sont vides, laisser le navigateur afficher l'infobulle native
            if (loginForm.checkValidity && !loginForm.checkValidity()) {
                if (loginForm.reportValidity) {
                    loginForm.reportValidity();
                }
                return;
            }

            if (loginButton.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            loginButton.dataset.submitting = 'true';

            // Afficher immédiatement le spinner
            startLoadingState();

            // Laisser le temps au navigateur de dessiner le spinner avant la redirection/requête
            setTimeout(function () {
                HTMLFormElement.prototype.submit.call(loginForm);
            }, 500);
        });

        // En cas de retour en arrière navigateur (bfcache)
        window.addEventListener('pageshow', function () {
            resetLoadingState();
        });
    </script>

    @include('partials.pwa-scripts')
</body>


<!-- Mirrored from htmlthemes.gitlab.io/admin/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 May 2023 21:04:33 GMT -->
</html>
