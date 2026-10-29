@extends('layouts.admin')
@section('content')
<div class="container-fluid finance-page">
    <header class="finance-page-heading"><div><a href="{{ route('finance.dashboard') }}">← Trésorerie</a><h1>Remises des boutiques</h1><p>Chaque collecte a son bon, ses signatures et sa confirmation par le DG.</p></div>@if(auth()->user()->isManager())<a class="btn btn-primary" href="{{ route('finance.remittances.create') }}">Préparer une remise</a>@endif</header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <section class="finance-panel"><div class="table-responsive"><table class="table finance-table"><thead><tr><th>Bon</th><th>Boutique / période</th><th>Collecteur</th><th>Montant compté</th><th>État</th><th></th></tr></thead><tbody>
    @forelse($remittances as $item)
        <tr><td><strong>{{ $item->reference }}</strong><small class="d-block text-muted">{{ $item->created_at?->format('d/m/Y H:i') }}</small></td><td>{{ $item->location?->name }}<small class="d-block text-muted">{{ $item->period_start?->format('d/m/Y') }} – {{ $item->period_end?->format('d/m/Y') }}</small></td><td>{{ $item->collector?->name ?? '—' }}</td><td class="font-weight-bold">{{ number_format($item->handed_over_amount, 0, ',', ' ') }} F</td><td><span class="badge badge-{{ $item->status === 'received' ? 'success' : ($item->status === 'discrepancy_open' ? 'danger' : 'warning') }}">{{ \App\Support\FinanceDisplayLabels::status($item->status) }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('finance.remittances.show', $item) }}">Ouvrir</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune remise enregistrée.</td></tr>@endforelse
    </tbody></table></div>{{ $remittances->links() }}</section>
</div>
<style>.finance-page{max-width:1500px;padding:18px 22px 40px}.finance-page-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.finance-page-heading h1{font-weight:800;margin:8px 0}.finance-page-heading p{color:#758194;margin:0}.finance-panel{padding:20px;background:#fff;border:1px solid #e1e5eb}.finance-table th{font-size:12px;color:#697485;text-transform:uppercase;white-space:nowrap}.finance-table td{vertical-align:middle}.finance-page .badge{padding:8px 10px;text-transform:capitalize}@media(max-width:600px){.finance-page{padding:10px}.finance-page-heading{align-items:flex-start;gap:10px}.finance-page-heading .btn{white-space:nowrap}.finance-panel{padding:12px}}</style>
@endsection
