<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#7e1615"><title>Créer mon espace client · TENACOS</title>
<style>:root{--red:#7e1615;--ink:#252a34;--muted:#687386;--line:#e3e7ed}*{box-sizing:border-box}body{margin:0;background:#f5f6f8;color:var(--ink);font:16px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{max-width:620px;margin:7vh auto;padding:18px}.card{background:#fff;border:1px solid var(--line);padding:24px}.eyebrow{color:var(--red);font-size:12px;font-weight:850;letter-spacing:.14em}h1{font-size:29px;line-height:1.2;margin:8px 0}p{color:var(--muted)}.order{padding:14px;background:#f6f7f9;border:1px solid var(--line);margin:18px 0}.field{margin:15px 0}.field label{display:block;font-weight:750;margin-bottom:6px}.field input{width:100%;min-height:49px;padding:11px 12px;border:1px solid #cfd5df;font:inherit}.button{display:inline-flex;justify-content:center;align-items:center;min-height:46px;padding:11px 16px;border:1px solid var(--red);background:var(--red);color:#fff;font:inherit;font-weight:800;text-decoration:none;cursor:pointer}.button.light{background:#fff;color:var(--ink);border-color:var(--line)}.errors{padding:12px;border-left:4px solid #b32929;background:#fff1f1;color:#8a1d1d}@media(max-width:600px){.wrap{margin:2vh auto;padding:12px}.card{padding:18px}.button{width:100%}}</style></head>
<body><main class="wrap"><section class="card"><span class="eyebrow">ESPACE CLIENT TENACOS</span>
    @if($accountExists)<h1>Retrouve cette commande</h1><p>Connecte-toi pour l’ajouter à ton espace, ou choisis un nouveau mot de passe si tu as oublié l’ancien.</p>
        <a class="button light" href="{{ route('customer-portal.index') }}">Me connecter</a>
    @else<h1>Retrouve tes commandes</h1><p>Choisis un mot de passe pour retrouver cette commande et les prochaines dans un seul espace client.</p>@endif
        <div class="order"><strong>Commande {{ $order->code }}</strong><br><span>Numéro associé : {{ $order->costumer?->phone }}</span></div>
        @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('customer-portal.store',$order->public_tracking_token) }}">@csrf
            <div class="field"><label for="password">{{ $accountExists ? 'Nouveau mot de passe (8 caractères minimum)' : 'Mot de passe (8 caractères minimum)' }}</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
            <div class="field"><label for="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></div>
            <button class="button" type="submit">{{ $accountExists ? 'Mettre à jour mon mot de passe' : 'Créer mon espace client' }}</button>
        </form>
    <p><a href="{{ route('public-order.track',$order->public_tracking_token) }}" style="color:var(--red)">Retour au suivi de la commande</a></p>
</section></main></body></html>
