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
        body { background:var(--app-background); color:var(--app-text); }
        .welcome-login-background { background:{{ $appTheme['primary_color'] }} !important; }
        .welcome-login-card { background:var(--app-surface); color:var(--app-text); }
        .welcome-login-card .btn-primary { background:{{ $appTheme['primary_color'] }} !important; border-color:{{ $appTheme['primary_color'] }} !important; color:{{ $appTheme['primary_contrast_color'] }} !important; }
        .welcome-login-credit { color:var(--app-primary) !important; }
        html[data-app-theme="dark"] body { background:#14171c; }
        html[data-app-theme="dark"] .welcome-login-card { background:#20252d; color:#edf1f5; border-color:#373f4b; }
        html[data-app-theme="dark"] .welcome-login-card .form-control { background:#171b21; color:#edf1f5; border-color:#373f4b; }
        html[data-app-theme="dark"] .welcome-login-card .form-control::placeholder { color:#a8b1bd; }
    </style>

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">

    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css') }}">


</head>

<body >
    <div class="overlay-mask"></div>
    <div class="main-wrapper ">
        <div class="main-content container-fluid h-100 bg-primary">
            <div class="row h-100 welcome-login-background">

                <div class="col-10 col-sm-8 col-md-6 col-lg-4 col-xl-3 m-auto px-3 pt-5 pb-4 card shadow m-3 welcome-login-card">
                    @php
                    $settings=App\Models\Setting::all();
                    @endphp
                    @forelse ($settings as $setting )
                    <img src="{{ url('image/',$setting->img) }}" height="50" alt="Moss Logo" class="logo justify-content-center d-flex mx-auto mb-3">
                      <h4 style="text-align: center">{{ $setting->name ?? '' }}</h4>

                    @empty

                    <img src="{{ asset('assets/images/tena.png') }}" height="50" alt="Moss Logo" class="logo justify-content-center d-flex mx-auto mb-3">
                    @endforelse
                    <form    method="POST" action="{{ route('login') }}" class="p-3" >
                         @csrf
                        <div class="form-group">
                            <input  type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="form-group mt-2">
                            <input type="password" id="password" class="form-control @error('password') is-invalid   @enderror"  placeholder="Mot de passe" name="password" required >
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                           @enderror
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="showPassword">
                            <label class="form-check-label" for="exampleCheck1">Voir le mot de passe</label>
                        </div>

                        <button class="btn btn-primary btn-block my-4">
                            Connectez-vous
                        </button>
                        <hr style="padding-top: 8px">
                        <a href="https://digital-services-home.com/" class="d-block text-center welcome-login-credit">@ DIGITAL SERVCICES</a>
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
    </script>


</body>


</html>
