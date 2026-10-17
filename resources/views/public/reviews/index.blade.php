<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Avis de nos clientes · TENACOS</title>
    <style>
        :root{--red:#861817;--ink:#252936;--muted:#70798a;--line:#e7e9ee;--cream:#fbf8f4}
        *{box-sizing:border-box}body{margin:0;background:#f6f7f9;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        .page{width:min(1120px,calc(100% - 36px));margin:auto;padding:28px 0 56px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:36px}.brand{font-size:15px;font-weight:900;letter-spacing:.16em;color:var(--red)}
        .actions{display:flex;flex-wrap:wrap;gap:10px}.button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 15px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink);font:inherit;font-size:13px;font-weight:750;text-decoration:none;cursor:pointer}.button-primary{border-color:var(--red);background:var(--red);color:#fff}
        .hero{position:relative;overflow:hidden;padding:clamp(28px,6vw,58px);border-radius:22px;background:linear-gradient(125deg,#741413,#a3322d);color:white}.hero:after{position:absolute;right:-55px;bottom:-120px;width:330px;height:330px;border:1px solid #ffffff30;border-radius:50%;content:""}.eyebrow{font-size:11px;font-weight:850;letter-spacing:.18em;text-transform:uppercase;opacity:.82}.hero h1{max-width:700px;margin:13px 0;font-size:clamp(32px,6vw,58px);line-height:1.03;letter-spacing:-.04em}.hero p{max-width:590px;margin:0;color:#f4dedd;font-size:16px;line-height:1.65}.review-count{display:inline-block;margin-top:24px;padding:9px 13px;border:1px solid #ffffff45;border-radius:99px;font-size:12px;font-weight:750}
        .settings,.notice{margin:18px 0;padding:17px 19px;border:1px solid var(--line);border-radius:14px;background:#fff}.settings h2{margin:0 0 5px;font-size:16px}.settings p,.notice{color:var(--muted);font-size:12px;line-height:1.5}.settings form{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-top:12px}.settings label{display:grid;gap:6px;color:#535b69;font-size:12px;font-weight:700}.settings select{min-width:min(320px,80vw);height:42px;padding:8px 10px;border:1px solid var(--line);border-radius:9px;background:white;color:var(--ink);font:inherit}.notice{border-color:#efdfc4;background:#fffaf1;color:#745a32}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:24px}.card{display:flex;flex-direction:column;min-height:230px;padding:22px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:0 8px 24px #26304708;break-inside:avoid}.stars{color:#d59726;font-size:17px;letter-spacing:.12em}.quote{flex:1;margin:18px 0;color:#343a47;font-size:15px;line-height:1.7;white-space:pre-line;overflow-wrap:anywhere}.reviewer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:14px;border-top:1px solid #eff0f3}.reviewer strong{font-size:13px}.reviewer span{color:var(--muted);font-size:11px}.phone{margin-top:5px;color:var(--muted);font-size:11px}.empty{grid-column:1/-1;padding:50px 20px;border:1px dashed #d8dce4;border-radius:16px;background:white;text-align:center;color:var(--muted)}.pagination{margin-top:24px}.pagination nav{display:flex;justify-content:center}.footer{margin-top:34px;color:#89909e;text-align:center;font-size:11px}
        @media(max-width:800px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.page{width:calc(100% - 22px);padding-top:15px}.topbar{align-items:flex-start;margin-bottom:20px}.grid{grid-template-columns:1fr;gap:11px}.card{min-height:190px;padding:18px}.hero{border-radius:16px}.topbar .button{padding:8px 10px;font-size:11px}}
        @media print{body{background:white}.page{width:100%;padding:0}.topbar,.settings,.notice,.pagination,.footer,.no-print{display:none!important}.hero{margin-bottom:18px;padding:25px;border-radius:0;print-color-adjust:exact;-webkit-print-color-adjust:exact}.hero h1{font-size:32px}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}.card{min-height:170px;padding:15px;box-shadow:none}.quote{font-size:12px;margin:12px 0}.reviewer strong{font-size:11px}}
    </style>
</head>
<body>
<main class="page">
    <header class="topbar">
        <a class="brand" href="{{ url('/') }}" aria-label="TENACOS accueil">TENACOS</a>
        <div class="actions">
            <button class="button button-primary no-print" type="button" onclick="window.print()">Télécharger / imprimer en PDF</button>
        </div>
    </header>

    <section class="hero">
        <span class="eyebrow">Vos retours nous font avancer</span>
        <h1>Les mots de nos clientes</h1>
        <p>Découvrez les avis positifs reçus par TENACOS après nos échanges avec les clientes.</p>
        <span class="review-count">{{ number_format($reviews->total(), 0, ',', ' ') }} avis positifs</span>
    </section>

    @if(session('review_settings_saved'))<div class="notice" role="status">{{ session('review_settings_saved') }}</div>@endif

    @if($canConfigure)
        <section class="settings no-print">
            <h2>Affichage public des numéros</h2>
            <p>Ce réglage s’applique à tous les visiteurs de cette page. Les numéros restent masqués par défaut.</p>
            <form method="POST" action="{{ route('public-reviews.phone-display') }}">
                @csrf
                <label>Numéro affiché sur la page publique
                    <select name="public_review_phone_mode">
                        <option value="hidden" @selected($phoneMode === 'hidden')>Masquer entièrement</option>
                        <option value="mask_last_two" @selected($phoneMode === 'mask_last_two')>Masquer les deux derniers chiffres</option>
                        <option value="full" @selected($phoneMode === 'full')>Afficher le numéro complet</option>
                    </select>
                </label>
                <button class="button button-primary" type="submit">Enregistrer</button>
            </form>
            @error('public_review_phone_mode')<small style="color:#a52626">{{ $message }}</small>@enderror
        </section>
    @endif

    <section class="grid" aria-label="Avis positifs des clientes">
        @forelse($reviews as $review)
            <article class="card">
                <div class="stars" aria-label="Avis positif">★★★★★</div>
                <p class="quote">{{ $review->response }}</p>
                <footer class="reviewer">
                    <div><strong>{{ $review->public_customer_name }}</strong>@if($review->public_phone)<div class="phone">{{ $review->public_phone }}</div>@endif</div>
                    <span>{{ $review->responded_at?->translatedFormat('F Y') }}</span>
                </footer>
            </article>
        @empty
            <div class="empty">Les avis positifs publiables apparaîtront ici dès qu’ils auront été enregistrés.</div>
        @endforelse
    </section>

    @if($reviews->hasPages())<div class="pagination no-print">{{ $reviews->links() }}</div>@endif
    <footer class="footer">TENACOS Cosmétiques · Merci pour votre confiance.</footer>
</main>
</body>
</html>
