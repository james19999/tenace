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

                    Ajouter un client
                    </h4>
                </div>
                <div class="card-body">
                <form  method="POST" action="{{ route('costumer.store') }}" >
                    @csrf
                        <div class="form-group">
                            <label for="name">Nom:</label>
                            <input type="text" class="form-control" id="name"   name="name"  placeholder="Nom"  value="{{ old('name') }}" >
                            @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone:</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Téléphone"  value="{{ old('phone') }}">
                            @error('phone') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Adresse:</label>
                            <input type="text" class="form-control" id="adresse" name="adresse" placeholder="Adresse"  value="{{ old('adresse') }}">
                            @error('adresse') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <a  href="{{ route('costumer.index') }}" class="btn btn-danger">Annuler</a>
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
