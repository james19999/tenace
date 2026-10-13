@extends('layouts.admin')

@section('content')
<div class="stock-control-page">
    <header class="stock-control-hero">
        <div class="stock-control-hero-copy"><span class="stock-control-kicker">TENACOS · FIABILITÉ DU STOCK</span><h1>Inventaires</h1><p>Comptage physique, contrôle indépendant et correction traçable des écarts.</p></div>
        <a href="{{ route('stock-control.counts.create') }}" class="stock-control-hero-action"><span class="material-icons">add</span>Nouvel inventaire</a>
        <div class="stock-control-hero-mark" aria-hidden="true"><span class="material-icons">fact_check</span></div>
    </header>
    @if (session('success'))<div class="stock-control-alert stock-control-alert-success" role="status"><span class="material-icons">check_circle</span>{{ session('success') }}</div>@endif
    <section class="stock-control-card stock-count-list-card">
        <div class="stock-control-section-head"><div><span class="stock-control-kicker">SUIVI</span><h2>Campagnes de comptage</h2></div><form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous les statuts</option>@foreach(['in_progress'=>'À compter','submitted'=>'À vérifier','completed'=>'Terminés'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></form></div>
        @if($counts->isEmpty())<div class="stock-control-empty"><span class="material-icons">fact_check</span><strong>Aucun inventaire</strong><p>Crée une campagne pour comparer les quantités physiques au journal d’un emplacement.</p><a class="stock-control-button stock-control-button-primary" href="{{ route('stock-control.counts.create') }}">Créer un inventaire</a></div>
        @else
            <div class="stock-count-table-wrap"><table class="stock-count-table"><thead><tr><th>Référence</th><th>Emplacement</th><th>Responsable du comptage</th><th>Date</th><th>Type</th><th>État</th><th></th></tr></thead><tbody>
                @foreach($counts as $count)<tr><td data-label="Référence"><a href="{{ route('stock-control.counts.show',$count) }}"><strong>{{ $count->count_number }}</strong></a></td><td data-label="Emplacement">{{ $count->location->name }}</td><td data-label="Responsable">{{ $count->counter?->name ?? '—' }}</td><td data-label="Date">{{ $count->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</td><td data-label="Type"><span class="stock-control-system-tag">{{ $count->is_opening_balance ? 'Reprise initiale' : 'Inventaire' }}</span></td><td data-label="État"><span class="stock-count-status status-{{ $count->status }}">{{ ['in_progress'=>'À compter','submitted'=>'À vérifier','completed'=>'Terminé'][$count->status] ?? $count->status }}</span></td><td data-label="Action"><a class="stock-control-button" href="{{ route('stock-control.counts.show',$count) }}">Ouvrir</a></td></tr>@endforeach
            </tbody></table></div>
            <div class="stock-control-pagination">{{ $counts->links() }}</div>
        @endif
    </section>
</div>
@endsection
