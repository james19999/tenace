@extends('layouts.admin')

@section('content')
<div class="col-12 courier-workspace">
    @if (auth()->user()?->user_type === 'LVS')
        @include('livreurs.partials.section-navigation')
    @endif

    <header class="courier-page-heading">
        <div><span class="courier-kicker">Performance de la semaine</span><h1>Classement des livreurs</h1><p>Nombre de commandes livrées par jour et total de la semaine.</p></div>
    </header>

    @if ($canSelectDeliveryCountry)
        <form method="GET" action="{{ route('show-deliveries') }}" class="courier-filter-form">
            <div><label for="delivery-ranking-country">Pays du classement</label><select id="delivery-ranking-country" name="country_code" class="form-control js-country-search" onchange="this.form.submit()">
                <option value="ALL" @selected($selectedCountry === null)>Tous les pays</option>
                @foreach ($countryOptions as $code => $country)
                    <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $country['label'] }}</option>
                @endforeach
            </select></div>
        </form>
    @else
        <p class="text-muted mb-3">Livreurs · {{ $countryOptions[$selectedCountry]['label'] ?? 'Pays non configuré' }}</p>
    @endif

    <section class="courier-section-panel">
        <div class="courier-panel-heading"><div><h2>Résultats hebdomadaires</h2><p>Les résultats sont classés du plus grand au plus petit nombre de livraisons.</p></div><span class="courier-count">{{ count($formatted) }}</span></div>
        @if (count($formatted) === 0)
            <div class="courier-empty"><span class="material-icons" aria-hidden="true">leaderboard</span><strong>Aucun résultat pour cette période</strong><p>Le classement apparaîtra ici dès qu’une livraison sera confirmée.</p></div>
        @else
            <div class="courier-ranking-grid">
                @foreach ($formatted as $user)
                    <article class="courier-ranking-card">
                        <div class="courier-ranking-head"><span class="courier-rank-number">{{ $loop->iteration }}</span><strong class="courier-ranking-name">{{ $user['name'] }}</strong><div class="courier-ranking-total"><strong>{{ $user['total_deliveries'] }}</strong><span>livraison(s)</span></div></div>
                        <div class="courier-week-grid" aria-label="Livraisons par jour pour {{ $user['name'] }}">
                            <div class="courier-week-day"><span>Lun</span><strong>{{ $user['days']['Monday'] }}</strong></div>
                            <div class="courier-week-day"><span>Mar</span><strong>{{ $user['days']['Tuesday'] }}</strong></div>
                            <div class="courier-week-day"><span>Mer</span><strong>{{ $user['days']['Wednesday'] }}</strong></div>
                            <div class="courier-week-day"><span>Jeu</span><strong>{{ $user['days']['Thursday'] }}</strong></div>
                            <div class="courier-week-day"><span>Ven</span><strong>{{ $user['days']['Friday'] }}</strong></div>
                            <div class="courier-week-day"><span>Sam</span><strong>{{ $user['days']['Saturday'] }}</strong></div>
                            <div class="courier-week-day"><span>Dim</span><strong>{{ $user['days']['Sunday'] }}</strong></div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
