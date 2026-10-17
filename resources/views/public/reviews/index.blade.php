<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Avis de nos clientes · TENACOS</title>
    <style>
        :root{--red:#861817;--ink:#252936;--muted:#70798a;--line:#e7e9ee}
        *{box-sizing:border-box}body{margin:0;background:#f6f7f9;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        .page{width:min(1120px,calc(100% - 32px));margin:auto;padding:18px 0 36px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.title{margin:0;color:var(--ink);font-size:19px;font-weight:850;letter-spacing:-.02em}
        .actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px}.button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px;padding:8px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--ink);font:inherit;font-size:12px;font-weight:750;text-decoration:none;cursor:pointer}.button-primary{border-color:var(--red);background:var(--red);color:#fff}
        .settings{position:relative}.settings summary{color:var(--muted);font-size:11px;cursor:pointer}.settings form{display:flex;align-items:end;gap:8px;flex-wrap:wrap;margin-top:9px;padding:10px;border:1px solid var(--line);border-radius:10px;background:#fff}.settings label{display:grid;gap:5px;color:#535b69;font-size:11px;font-weight:700}.settings select{min-width:min(290px,75vw);height:36px;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:white;color:var(--ink);font:inherit}.settings-error{color:#a52626;font-size:11px}
        .notice{margin:0 0 12px;padding:9px 12px;border:1px solid #efdfc4;border-radius:9px;background:#fffaf1;color:#745a32;font-size:11px}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.card{display:flex;flex-direction:column;min-height:190px;padding:19px;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:0 5px 16px #26304708;break-inside:avoid}.card-brand{display:flex;align-items:center;justify-content:space-between;color:var(--red);font-size:10px;font-weight:900;letter-spacing:.12em}.stars{color:#d59726;font-size:15px;letter-spacing:.1em}.quote{flex:1;margin:14px 0;color:#343a47;font-size:15px;line-height:1.6;white-space:pre-line;overflow-wrap:anywhere}.reviewer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:11px;border-top:1px solid #eff0f3}.reviewer strong{font-size:12px}.reviewer span{color:var(--muted);font-size:10px}.phone{margin-top:4px;color:var(--muted);font-size:10px}.empty{grid-column:1/-1;padding:32px 16px;border:1px dashed #d8dce4;border-radius:12px;background:white;text-align:center;color:var(--muted);font-size:13px}.pagination{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:18px}.pagination-pages{display:flex;align-items:center;gap:5px}.page-link{display:inline-flex;min-width:34px;height:34px;align-items:center;justify-content:center;padding:0 9px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);font-size:12px;font-weight:700;text-decoration:none}.page-link.is-current{border-color:var(--red);background:var(--red);color:#fff}.page-link.is-disabled{background:#f1f2f4;color:#aab0ba}.page-indicator{margin:0 5px;color:var(--muted);font-size:11px;white-space:nowrap}
        @media(max-width:600px){.page{width:calc(100% - 20px);padding-top:10px}.topbar{align-items:flex-start}.title{font-size:16px}.button{min-height:35px;padding:7px 9px;font-size:11px}.grid{grid-template-columns:1fr;gap:9px}.card{min-height:150px;padding:15px}.quote{margin:11px 0;font-size:14px}.pagination{gap:4px}.pagination-pages{gap:3px}.page-link{min-width:31px;height:32px;padding:0 7px;font-size:11px}.page-indicator{font-size:10px}}
        @media print{body{background:#fff}.page{width:100%;padding:0}.topbar,.settings,.notice,.pagination,.no-print{display:none!important}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.card{min-height:150px;padding:14px;box-shadow:none}.quote{font-size:12px;margin:10px 0}.reviewer strong{font-size:10px}}
    </style>
</head>
<body>
<main class="page">
    <header class="topbar">
        <h1 class="title">Avis positifs</h1>
        <div class="actions">
            <button class="button button-primary no-print" type="button" onclick="window.print()">Capturer / télécharger PDF</button>
        </div>
    </header>

    @if(session('review_settings_saved'))<div class="notice" role="status">{{ session('review_settings_saved') }}</div>@endif

    @if($canConfigure)
        <details class="settings no-print">
            <summary>Confidentialité des numéros (masqués par défaut)</summary>
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
            @error('public_review_phone_mode')<small class="settings-error">{{ $message }}</small>@enderror
        </details>
    @endif

    <section class="grid" aria-label="Avis positifs des clientes">
        @forelse($reviews as $review)
            <article class="card">
                <div class="card-brand"><span>TENACOS</span><span class="stars" aria-label="Avis positif">★★★★★</span></div>
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

    @if($reviews->hasPages())
        @php
            $firstReviewPage = max(1, $reviews->currentPage() - 2);
            $lastReviewPage = min($reviews->lastPage(), $reviews->currentPage() + 2);
        @endphp
        <nav class="pagination no-print" aria-label="Pagination des avis">
            @if($reviews->onFirstPage())
                <span class="page-link is-disabled" aria-disabled="true">Précédent</span>
            @else
                <a class="page-link" href="{{ $reviews->previousPageUrl() }}" rel="prev">Précédent</a>
            @endif
            <div class="pagination-pages">
                @for($reviewPage = $firstReviewPage; $reviewPage <= $lastReviewPage; $reviewPage++)
                    @if($reviewPage === $reviews->currentPage())
                        <span class="page-link is-current" aria-current="page">{{ $reviewPage }}</span>
                    @else
                        <a class="page-link" href="{{ $reviews->url($reviewPage) }}">{{ $reviewPage }}</a>
                    @endif
                @endfor
            </div>
            <span class="page-indicator">{{ $reviews->currentPage() }} / {{ $reviews->lastPage() }}</span>
            @if($reviews->hasMorePages())
                <a class="page-link" href="{{ $reviews->nextPageUrl() }}" rel="next">Suivant</a>
            @else
                <span class="page-link is-disabled" aria-disabled="true">Suivant</span>
            @endif
        </nav>
    @endif
</main>
</body>
</html>
