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

                    modifier le produit
                    </h4>
                </div>
                <div class="card-body">
                <form  method="POST" action="{{ route('updateproduct',$product) }}" >
                    @csrf

                    @method("PUT")
                        <div class="form-group">
                            <label for="name">Nom:</label>
                            <input type="text" class="form-control" id="name"   name="name"  placeholder="Nom"  value="{{$product->name }}" >
                            @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="price">Prix:</label>
                            <input id="price" type="number" class="form-control @error('price') is-invalid @enderror" name="price" value="{{$product->price }}" required autocomplete="price">

                            @error('price')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
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
