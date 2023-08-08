@extends('layouts.admin')

@section('content')
<div>
    @if (session()->has('messages'))
        <div class="alert alert-success">{{ session('messages') }}</div>
    @endif

    <div class="row d-flex justify-content-center">
        <div class="col-12 col-sm-7 ">
            <div class="card shadow">
                <div class="card-header" style="background-color: #7e1615">
                    <h4 style="text-align: center;font-weight: 900 ;color: white">

                    Modifier un membre
                    </h4>
                </div>
                <div class="card-body">
                <form  method="POST" action="{{ route('livreurs.update',$users) }}" >
                    @csrf

                    @method("PUT")
                        <div class="form-group">
                            <label for="name">Nom:</label>
                            <input type="text" class="form-control" id="name"   name="name"  placeholder="Nom"  value="{{$users->name }}" >
                            @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Email:</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{$users->email }}" required autocomplete="email">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone:</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Téléphone"  value="{{ $users->phone }}">
                            @error('phone') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Adresse:</label>
                            <input type="text" class="form-control" id="adresse" name="adresse" placeholder="Adresse"  value="{{$users->adresse }}">
                            @error('adresse') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        {{-- <div class="form-group">
                            <label for="password">Mot de passe:</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div> --}}
                        {{-- <div class="form-group">
                            <label for="password-confirm">Confirmez le mot de passe:</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                        </div> --}}

                        <div class="form-group">
                        <label for="password-confirm">Type:</label>

                         <select name="user_type" id="" class="form-control">

                             <option value="LVS">livreur</option>
                             <option value="PT">Partenaire</option>
                             <option value="ADMINUSER">Administrateur</option>
                         </select>
                        </div>

                        <a  href="{{ route('useradminlist') }}" class="btn btn-danger">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
            <div class="card-footer" style="background-color: #7e1615" >

            </div>
        </div>
    </div>

   </div>

</div>


@endsection
