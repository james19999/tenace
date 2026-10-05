<!DOCTYPE html>
<html lang="en">


<!-- Mirrored from htmlthemes.gitlab.io/admin/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 May 2023 21:04:31 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <meta charset="UTF-8">
    <title>{{ env('APP_NAME') }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">

    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css') }}">

    @include('partials.pwa-head')
</head>

<body >
    <div class="overlay-mask"></div>
    <div class="main-wrapper ">
        <div class="main-content container-fluid h-100 bg-primary">
            @if (session()->has('messages'))
            <div class="alert alert-success">{{ session('messages') }}</div>
           @endif
            <div  style="background-color: #7e1615" class="row h-100">
                <div class="col-10 col-sm-8 col-md-6 col-lg-4 col-xl-3 m-auto px-3 pt-5 pb-4 card shadow m-3">
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
                        <a href="https://digital-services-home.com/" style="color: black" class="d-block text-center">@ DIGITAL SERVCICES</a>
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
