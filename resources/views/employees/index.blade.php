@extends('layouts.admin')

@section('content')
<main class="container-fluid employee-crm">
    <header class="ec-heading">
        <div><span class="ec-eyebrow">ÉQUIPE TENACOS</span><h1>Dossiers employés</h1><p>Coordonnées et informations professionnelles des membres de l’équipe. Cette page ne gère pas les salaires ni les accès aux comptes.</p></div>
        <a class="btn btn-outline-primary" href="{{ route('useradminlist') }}"><span class="material-icons">manage_accounts</span>Comptes et accès</a>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <section class="ec-stats"><article><span>Employés actifs</span><strong>{{ number_format($activeCount) }}</strong></article><article><span>Comptes désactivés</span><strong>{{ number_format($inactiveCount) }}</strong></article><article><span>Résultats affichés</span><strong>{{ $employees->total() }}</strong></article></section>

    <section class="ec-card">
        <form class="ec-filters" method="GET" action="{{ route('employees.index') }}">
            <label class="ec-search">Rechercher<input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, téléphone, poste ou service"></label>
            <label>Rôle<select class="form-control" name="role"><option value="">Tous les rôles</option>@foreach($roles as $key=>$label)<option value="{{ $key }}" @selected(($filters['role'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></label>
            <label>État du compte<select class="form-control" name="status"><option value="">Tous</option><option value="active" @selected(($filters['status'] ?? '')==='active')>Actif</option><option value="inactive" @selected(($filters['status'] ?? '')==='inactive')>Désactivé</option></select></label>
            <div class="ec-filter-actions"><button class="btn btn-primary">Filtrer</button><a class="btn btn-light" href="{{ route('employees.index') }}">Effacer</a></div>
        </form>

        <div class="table-responsive"><table class="table ec-table"><thead><tr><th>Employé</th><th>Poste / service</th><th>Téléphone</th><th>Rôle du compte</th><th>Boutique confiée</th><th>État</th><th></th></tr></thead><tbody>
            @forelse($employees as $employee)
                <tr><td><strong>{{ $employee->name }}</strong><small>{{ $employee->email }}</small></td><td>{{ $employee->employeeProfile?->job_title ?? 'Poste non renseigné' }}<small>{{ $employee->employeeProfile?->department ?? 'Service non renseigné' }}</small></td><td>{{ $employee->phone ?: '—' }}</td><td>{{ $roles[$employee->user_type] ?? 'Autre' }}</td><td>{{ $employee->responsibleStockLocations->pluck('name')->join(', ') ?: 'Aucune' }}</td><td><span class="ec-status {{ $employee->active ? 'is-active' : 'is-inactive' }}">{{ $employee->active ? 'Actif' : 'Désactivé' }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('employees.show',$employee->employeeProfile->tracking_id) }}">Ouvrir le dossier</a></td></tr>
            @empty<tr><td colspan="7" class="ec-empty">Aucun employé ne correspond à cette recherche.</td></tr>@endforelse
        </tbody></table></div>
        <div class="ec-pagination">{{ $employees->onEachSide(1)->links() }}</div>
    </section>
</main>
<style>
.employee-crm{max-width:1500px;padding:clamp(14px,2vw,28px);color:var(--app-text)}.ec-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px}.ec-eyebrow{font-size:11px;letter-spacing:.08em;font-weight:800;color:var(--app-primary)}.ec-heading h1{margin:5px 0;font-size:clamp(25px,3vw,34px);font-weight:850}.ec-heading p{max-width:780px;margin:0;color:var(--app-muted);line-height:1.55}.ec-heading .btn{display:flex;align-items:center;gap:7px;white-space:nowrap}.ec-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}.ec-stats article,.ec-card{background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius);padding:clamp(14px,2vw,22px)}.ec-stats article span,.ec-stats article strong{display:block}.ec-stats article span{color:var(--app-muted);font-size:13px}.ec-stats article strong{margin-top:5px;color:var(--app-primary);font-size:25px}.ec-filters{display:grid;grid-template-columns:minmax(220px,1.6fr) minmax(160px,.8fr) minmax(150px,.7fr) auto;align-items:end;gap:10px;margin-bottom:16px}.ec-filters label{display:block;margin:0;color:var(--app-text);font-size:13px;font-weight:700}.ec-filters .form-control{margin-top:5px}.ec-filter-actions{display:flex;gap:7px}.ec-table{min-width:820px;margin:0;color:var(--app-text)}.ec-table th{background:color-mix(in srgb,var(--app-primary) 7%,var(--app-surface));color:var(--app-muted);font-size:11px;text-transform:uppercase;white-space:nowrap}.ec-table td{vertical-align:middle}.ec-table td strong,.ec-table td small{display:block}.ec-table td small{margin-top:3px;color:var(--app-muted)}.ec-status{display:inline-block;padding:5px 9px;border-radius:20px;font-size:12px;font-weight:700}.ec-status.is-active{background:#e8f5eb;color:#187b36}.ec-status.is-inactive{background:#f2e9e9;color:#8a3434}.ec-empty{text-align:center;padding:30px!important;color:var(--app-muted)}.ec-pagination{display:flex;justify-content:center;overflow-x:auto;margin-top:14px}.ec-pagination .pagination{flex-wrap:wrap;justify-content:center}.ec-pagination .page-link{color:var(--app-primary)}.ec-pagination .page-item.active .page-link{background:var(--app-primary);border-color:var(--app-primary);color:var(--app-primary-contrast)}
@media(max-width:800px){.ec-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.ec-heading{align-items:flex-start}}
@media(max-width:560px){.employee-crm{padding:12px 9px}.ec-heading{display:block}.ec-heading .btn{margin-top:12px;width:max-content}.ec-filters{grid-template-columns:minmax(0,1fr)}.ec-filter-actions .btn{flex:1}}
</style>
@endsection
