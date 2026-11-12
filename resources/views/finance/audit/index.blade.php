@extends('layouts.admin')

@section('content')
<div class="container-fluid finance-audit-page">
    <header><div><span>TENACOS · CONTRÔLE INTERNE</span><h1>Historique des opérations financières</h1><p>Retrouvez qui a effectué une action, sur quel dossier et à quel moment.</p></div></header>
    <form method="GET" action="{{ route('finance.audit.index') }}" class="audit-filters">
        <label>Action<select name="action" class="form-control"><option value="">Toutes les actions</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(($filters['action']??'')===$action)>{{ \App\Support\FinanceDisplayLabels::action($action) }}</option>@endforeach</select></label>
        <label>Dossier concerné<select name="subject_type" class="form-control"><option value="">Tous les dossiers</option>@foreach(['FinanceCashRemittance'=>'Remise de fonds','FinanceExpenseRequest'=>'Dépense','FinanceSupplierInvoice'=>'Facture fournisseur','FinanceCashClosure'=>'Clôture de caisse','FinanceExternalLoan'=>'Emprunt bancaire'] as $type=>$label)<option value="{{ $type }}" @selected(($filters['subject_type']??'')===$type)>{{ $label }}</option>@endforeach</select></label>
        <label>Utilisateur<select name="user_id" class="form-control"><option value="">Tous</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['user_id']??'')===(string)$user->id)>{{ $user->name }}</option>@endforeach</select></label>
        <label>Du<input type="date" class="form-control" name="from" value="{{ $filters['from']??'' }}"></label>
        <label>Au<input type="date" class="form-control" name="to" value="{{ $filters['to']??'' }}"></label>
        <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>
    <section class="audit-list">
        @forelse($events as $event)
        <article class="audit-event">
            @php($beforeValues = \App\Support\FinanceDisplayLabels::auditValues($event->before_values))
            @php($afterValues = \App\Support\FinanceDisplayLabels::auditValues($event->after_values))
            <div class="audit-event-head"><div><strong>{{ \App\Support\FinanceDisplayLabels::action($event->action) }}</strong><small>{{ \App\Support\FinanceDisplayLabels::subjectType($event->subject_type) }}</small></div><div><strong>{{ $event->user?->name ?? 'Compte désactivé' }}</strong><small>{{ $event->created_at?->format('d/m/Y à H:i') }}</small></div></div>
            @if($event->reason)<p class="audit-reason"><strong>Commentaire :</strong> {{ $event->reason }}</p>@endif
            @if($beforeValues || $afterValues)
                <details><summary>Consulter les informations du dossier</summary><div class="audit-values">
                    @if($beforeValues)<div><h2>Avant l’action</h2><dl>@foreach($beforeValues as $item)<dt>{{ $item['label'] }}</dt><dd>{{ $item['value'] }}</dd>@endforeach</dl></div>@endif
                    @if($afterValues)<div><h2>Après l’action</h2><dl>@foreach($afterValues as $item)<dt>{{ $item['label'] }}</dt><dd>{{ $item['value'] }}</dd>@endforeach</dl></div>@endif
                </div></details>
            @endif
        </article>
        @empty<div class="audit-empty">Aucune action financière ne correspond à ces filtres.</div>@endforelse
        <div class="audit-pagination">{{ $events->links() }}</div>
    </section>
</div>
<style>
.finance-audit-page{width:100%;max-width:1450px;padding:clamp(10px,2vw,24px);box-sizing:border-box}
.finance-audit-page>header{min-width:0;padding:clamp(16px,3vw,28px);margin-bottom:16px;background:#fff;border:1px solid #e1e5eb}
.finance-audit-page>header span{color:#187b2a;font-weight:800;font-size:12px;letter-spacing:.1em}
.finance-audit-page h1{margin:6px 0;font-size:clamp(21px,3vw,28px);font-weight:800;line-height:1.2;overflow-wrap:anywhere}
.finance-audit-page>header p{margin:0;color:#778294;line-height:1.5}
.audit-filters{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1.3fr) minmax(0,1.1fr) minmax(0,.9fr) minmax(0,.9fr) auto;gap:12px;align-items:end;padding:16px 18px;margin-bottom:16px;background:#fff;border:1px solid #e1e5eb}
.audit-filters label{display:block;min-width:0;margin:0;font-weight:700}
.audit-filters .form-control{display:block;width:100%;min-width:0;max-width:100%;box-sizing:border-box;margin-top:5px}
.audit-filters button{min-height:38px;white-space:nowrap}
.audit-list{display:grid;gap:12px;min-width:0}
.audit-event{min-width:0;padding:17px 20px;background:#fff;border:1px solid #e1e5eb}
.audit-event-head{display:grid;grid-template-columns:minmax(0,1fr) minmax(150px,.55fr);align-items:start;gap:16px}
.audit-event-head>div{min-width:0;overflow-wrap:anywhere}
.audit-event-head>div+div{text-align:right}
.audit-event-head strong,.audit-event-head small{display:block}
.audit-event-head small{margin-top:4px;color:#758194}
.audit-reason{margin:12px 0 0;color:#515c6d;overflow-wrap:anywhere}
.audit-event details{min-width:0;margin-top:12px;padding-top:10px;border-top:1px solid #edf0f3}
.audit-event summary{cursor:pointer;font-weight:700;color:#187b2a;overflow-wrap:anywhere}
.audit-values{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:12px}
.audit-values>div{min-width:0;padding:12px;background:#f7f8fa;border:1px solid #e4e8ed}
.audit-values h2{font-size:14px;font-weight:800}
.audit-values dl{display:grid;grid-template-columns:minmax(110px,.8fr) minmax(0,1.2fr);gap:8px 12px;margin:0}
.audit-values dt,.audit-values dd{min-width:0;overflow-wrap:anywhere;word-break:break-word}
.audit-values dt{color:#687486;font-weight:600}
.audit-values dd{margin:0;color:#27303d}
.audit-empty{padding:clamp(24px,5vw,40px);text-align:center;background:#fff;color:#778294}
.audit-pagination{max-width:100%;overflow-x:auto;padding:4px 0}
.audit-pagination nav{min-width:max-content}
@media(max-width:1100px){.audit-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:760px){.audit-filters{grid-template-columns:repeat(2,minmax(0,1fr));padding:14px}.audit-filters button{grid-column:1/-1}.audit-values{grid-template-columns:minmax(0,1fr)}.audit-values dl{grid-template-columns:minmax(0,1fr) minmax(0,1.4fr)}}
@media(max-width:520px){.finance-audit-page{padding:10px}.finance-audit-page>header{padding:16px}.audit-filters{grid-template-columns:minmax(0,1fr);gap:10px;padding:12px}.audit-filters button{grid-column:auto;width:100%}.audit-event{padding:14px}.audit-event-head{grid-template-columns:minmax(0,1fr);gap:10px}.audit-event-head>div+div{text-align:left}.audit-values dl{grid-template-columns:minmax(0,1fr);gap:2px}.audit-values dd{margin-bottom:8px}.audit-pagination nav{min-width:0}}
</style>
@endsection
