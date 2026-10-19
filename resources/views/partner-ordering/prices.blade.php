@extends('layouts.admin')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-danger font-weight-bold small">COMMANDES PARTENAIRES</span>
            <h1 class="h3 mb-1">Prix par pays · {{ $countries[$country]['label'] }}</h1>
            <p class="text-muted mb-0">Seuls les produits activés avec un prix renseigné seront proposés dans ce pays.</p>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: .5rem">
            <label for="country-picker" class="mb-0">Pays</label>
            <select id="country-picker" class="form-control" style="width: auto" onchange="if(this.value) window.location.href=this.value">
                @foreach ($countries as $code => $item)
                    <option value="{{ route('partner-ordering.prices', $code) }}" @selected($code === $country)>{{ $item['label'] }}</option>
                @endforeach
            </select>
            <a href="{{ route('partner-ordering.links') }}" class="btn btn-outline-danger">Gérer les liens</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('partner-ordering.prices.save', $country) }}">
        @csrf
        @method('PUT')
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light"><tr><th>Produit</th><th style="width: 220px">Prix de vente (FCFA)</th><th style="width: 150px">Disponible dans le pays</th></tr></thead>
                    <tbody>
                    @forelse ($products as $product)
                        @php($countryPrice = $product->countryPrices->first())
                        <tr>
                            <td><strong>{{ $product->name }}</strong><small class="d-block text-muted">Réf. {{ $product->id }}</small></td>
                            <td><input class="form-control" type="number" min="1" step="1" name="prices[{{ $product->id }}]" value="{{ old('prices.'.$product->id, $countryPrice?->price) }}" placeholder="Non configuré"></td>
                            <td>
                                <input type="hidden" name="available[{{ $product->id }}]" value="0">
                                <label class="mb-0"><input type="checkbox" name="available[{{ $product->id }}]" value="1" @checked(old('available.'.$product->id, $countryPrice?->is_available ?? false))> Actif</label>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Aucun produit.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
            <div class="card-footer text-right"><button class="btn btn-danger" type="submit">Enregistrer les prix de cette page</button></div>
        </div>
    </form>
</div>
@endsection
