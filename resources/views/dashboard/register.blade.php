<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <title>TENACE COSMETIQUE</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.jpg') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.jpg') }}" type="image/png">

    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css') }}">


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
                    <img src="{{ asset('assets/images/tena.jpg') }}" height="50" alt="Moss Logo" class="logo justify-content-center d-flex mx-auto mb-3">
                    <form  method="POST" action="{{ route('storepathner') }}" >
                        @csrf
                            <div class="form-group">
                                <label for="name">Nom:</label>
                                <input type="text" class="form-control" id="name"   name="name"  placeholder="Nom"  value="{{ old('name') }}" >
                                @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="name">Email:</label>
                                <input id="email" type="email"  placeholder="E-mail" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="phone">Téléphone:</label>
                                <input type="number" class="form-control" id="phone" name="phone" placeholder="Téléphone"  value="{{ old('phone') }}">
                                @error('phone') <span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="phone">Adresse:</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" placeholder="Adresse"  value="{{ old('adresse') }}">
                                @error('adresse') <span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="password">Mot de passe:</label>
                                <input id="password"  placeholder="Mot de passe" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password-confirm">Confirmez le mot de passe:</label>
                                <input id="password-confirm"  placeholder="Confirmation du mot de passe" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                            </div>

                            <button type="reset" class="btn btn-danger">Annuler</button>
                            <button type="submit" class="btn btn-primary">S'inscrire</button>

                            <hr style="padding-top: 8px">
                            <a href="https://digital-services-home.com/" style="color: black" class="d-block text-center">@ DIGITAL SERVCICES</a>
                        </form>
                </div>
            </div>
        </div>
    </div>



    <script src="{{ asset('assets/js/vendor.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/app.bundle.js') }}"></script>


</body>


<!-- Mirrored from htmlthemes.gitlab.io/admin/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 May 2023 21:04:33 GMT -->
</html>
