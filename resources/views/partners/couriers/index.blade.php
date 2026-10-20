@extends('layouts.admin')

@section('content')
@include('partners.partials.styles')
<main class="container-fluid py-4 partner-portal partner-courier-page">
    <header class="partner-courier-hero">
        <div><span class="partner-courier-eyebrow">ÉQUIPE DE LIVRAISON · {{ $partner->country_code }}</span><h1>Mes livreurs</h1><p>Comptes des livreurs rattachés à ton pays uniquement.</p></div>
        <a class="btn btn-danger" href="{{ route('partner.couriers.create') }}"><span class="material-icons" aria-hidden="true">person_add</span> Ajouter un livreur</a>
    </header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <section class="partner-courier-list">
        @forelse ($couriers as $courier)
            <article class="partner-courier-card">
                <div class="partner-courier-avatar" aria-hidden="true">{{ mb_substr($courier->name, 0, 1) }}</div>
                <div class="partner-courier-details"><h2>{{ $courier->name }}</h2><a href="tel:{{ $courier->phone }}">{{ $courier->phone }}</a><span>{{ $courier->email }}</span><small>{{ $courier->adresse }}</small></div>
                <div class="partner-courier-meta"><span class="partner-courier-status {{ $courier->active ? 'is-active' : 'is-inactive' }}">{{ $courier->active ? 'Compte actif' : 'Compte désactivé' }}</span><span>{{ $courier->courierDocuments->count() }} document(s)</span>@foreach($courier->courierDocuments as $document)<a href="{{ route('partner.couriers.documents.show', [$courier->id, $document->id]) }}" target="_blank" rel="noopener">Voir la pièce d’identité</a>@endforeach
                    <div class="partner-courier-actions"><a href="{{ route('partner.couriers.edit', $courier) }}">Modifier</a>@if($courier->active)<form method="POST" action="{{ route('partner.couriers.destroy', $courier) }}" data-courier-confirm="Désactiver ce livreur ?" data-courier-detail="Son compte sera désactivé et son historique de commandes sera conservé.">@csrf @method('DELETE')<button type="submit">Désactiver</button></form>@else<form method="POST" action="{{ route('partner.couriers.restore', $courier) }}" data-courier-confirm="Réactiver ce livreur ?" data-courier-detail="Ce livreur pourra de nouveau se connecter et prendre des commandes.">@csrf @method('PATCH')<button type="submit">Réactiver</button></form>@endif</div>
                </div>
            </article>
        @empty
            <div class="partner-courier-empty"><span class="material-icons" aria-hidden="true">directions_bike</span><h2>Aucun livreur pour le moment</h2><p>Crée un compte pour permettre à un livreur de prendre les commandes de {{ $partner->country_code }}.</p><a class="btn btn-danger" href="{{ route('partner.couriers.create') }}">Créer le premier compte</a></div>
        @endforelse
    </section>
    @if ($couriers->hasPages())<div class="mt-3">{{ $couriers->links() }}</div>@endif
</main>
<div class="partner-courier-modal" id="partner-courier-confirm-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="partner-courier-modal-title">
    <div class="partner-courier-modal-backdrop" data-courier-modal-close></div>
    <section class="partner-courier-modal-panel" tabindex="-1">
        <span class="material-icons partner-courier-modal-icon" aria-hidden="true">manage_accounts</span>
        <h2 id="partner-courier-modal-title"></h2><p id="partner-courier-modal-detail"></p>
        <div class="partner-courier-modal-actions"><button type="button" class="partner-courier-modal-cancel" data-courier-modal-close>Annuler</button><button type="button" class="partner-courier-modal-confirm" id="partner-courier-modal-confirm">Confirmer</button></div>
    </section>
</div>
<style>
.partner-courier-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;margin-top:4px}.partner-courier-actions a,.partner-courier-actions button{padding:5px 8px;border:1px solid var(--pc-line);background:transparent;color:var(--pc-brand);font-size:10px;font-weight:750;cursor:pointer}.partner-courier-actions form{margin:0}.partner-courier-actions form button{color:#a33b3b}.partner-courier-page{--pc-brand:var(--app-primary,#147d25);--pc-ink:#202938;--pc-muted:#748093;--pc-line:#e5e9ef}.partner-courier-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:23px 26px;margin-bottom:20px;background:var(--app-surface,#fff);border:1px solid var(--pc-line);border-left:4px solid var(--pc-brand)}.partner-courier-eyebrow{color:var(--pc-brand);font-size:10px;font-weight:850;letter-spacing:.12em}.partner-courier-hero h1{margin:7px 0 3px;color:var(--pc-ink);font-weight:820}.partner-courier-hero p{margin:0;color:var(--pc-muted)}.partner-courier-hero .btn{display:inline-flex;align-items:center;gap:7px}.partner-courier-hero .material-icons{font-size:18px}.partner-courier-list{display:grid;gap:11px}.partner-courier-card{display:grid;grid-template-columns:44px minmax(0,1fr) auto;align-items:center;gap:15px;padding:17px 19px;background:var(--app-surface,#fff);border:1px solid var(--pc-line)}.partner-courier-avatar{display:grid;place-items:center;width:44px;height:44px;background:color-mix(in srgb,var(--pc-brand) 10%,#fff);color:var(--pc-brand);font-weight:850}.partner-courier-details{display:grid;gap:3px;min-width:0}.partner-courier-details h2{margin:0;color:var(--pc-ink);font-size:15px;font-weight:800}.partner-courier-details a,.partner-courier-details span,.partner-courier-details small{color:var(--pc-muted);font-size:11px;overflow-wrap:anywhere}.partner-courier-meta{display:grid;justify-items:end;gap:5px;color:var(--pc-muted);font-size:10px}.partner-courier-meta a{color:var(--pc-brand);font-weight:700}.partner-courier-status{padding:5px 8px;font-weight:750}.partner-courier-status.is-active{background:#e9f5ed;color:#287347}.partner-courier-status.is-inactive{background:#f7e9e9;color:#9f3b3b}.partner-courier-empty{display:grid;justify-items:center;gap:8px;padding:44px 18px;background:var(--app-surface,#fff);border:1px solid var(--pc-line);text-align:center}.partner-courier-empty>.material-icons{color:var(--pc-brand);font-size:30px}.partner-courier-empty h2{margin:0;color:var(--pc-ink);font-size:18px}.partner-courier-empty p{max-width:440px;margin:0 0 8px;color:var(--pc-muted);font-size:12px}html[data-app-theme="dark"] .partner-courier-page{--pc-ink:var(--app-text);--pc-muted:var(--app-muted);--pc-line:var(--app-border)}html[data-app-theme="dark"] .partner-courier-hero,html[data-app-theme="dark"] .partner-courier-card,html[data-app-theme="dark"] .partner-courier-empty{background:var(--app-surface)}@media(max-width:640px){.partner-courier-page{padding:14px 12px 28px!important}.partner-courier-hero{align-items:stretch;flex-direction:column;padding:18px 16px}.partner-courier-card{grid-template-columns:40px minmax(0,1fr);gap:12px;padding:14px}.partner-courier-avatar{width:40px;height:40px}.partner-courier-meta{grid-column:1/-1;justify-items:start;padding-top:10px;border-top:1px solid var(--pc-line)}}
.partner-courier-modal{--pc-brand:var(--app-primary,#147d25);--pc-ink:var(--app-text,#202938);--pc-muted:var(--app-muted,#748093);--pc-line:var(--app-border,#e5e9ef);position:fixed;z-index:12000;inset:0;display:none;align-items:center;justify-content:center;padding:20px}.partner-courier-modal.is-open{display:flex}.partner-courier-modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.partner-courier-modal-panel{position:relative;width:min(100%,430px);padding:27px;background:var(--app-surface,#fff);border:1px solid var(--pc-line);box-shadow:0 24px 80px rgba(0,0,0,.24);text-align:center}.partner-courier-modal-icon{display:grid;place-items:center;width:48px;height:48px;margin:0 auto 14px;background:color-mix(in srgb,var(--pc-brand) 10%,#fff);color:var(--pc-brand);font-size:24px}.partner-courier-modal-panel h2{margin:0;color:var(--pc-ink);font-size:19px;font-weight:800}.partner-courier-modal-panel p{margin:8px 0 22px;color:var(--pc-muted);font-size:13px;line-height:1.55}.partner-courier-modal-actions{display:flex;justify-content:center;gap:9px}.partner-courier-modal-actions button{min-height:40px;padding:9px 15px;border:1px solid var(--pc-line);font-size:12px;font-weight:750;cursor:pointer}.partner-courier-modal-cancel{background:transparent;color:var(--pc-ink)}.partner-courier-modal-confirm{display:inline-flex!important;align-items:center;justify-content:center;border-color:var(--pc-brand)!important;background:var(--pc-brand)!important;color:#fff!important}.partner-courier-modal-actions button:focus-visible{outline:3px solid color-mix(in srgb,var(--pc-brand) 30%,transparent);outline-offset:2px}html[data-app-theme="dark"] .partner-courier-modal-panel{background:var(--app-surface)}
</style>
<script>
(() => {
    const modal = document.getElementById('partner-courier-confirm-modal');
    if (!modal) return;
    let pendingForm = null;
    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    };
    document.querySelectorAll('form[data-courier-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        event.preventDefault();
        pendingForm = form;
        document.getElementById('partner-courier-modal-title').textContent = form.dataset.courierConfirm;
        document.getElementById('partner-courier-modal-detail').textContent = form.dataset.courierDetail;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.querySelector('.partner-courier-modal-cancel').focus();
    }));
    modal.querySelectorAll('[data-courier-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
    document.getElementById('partner-courier-modal-confirm').addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        pendingForm = null;
        form.submit();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
})();
</script>
@endsection
