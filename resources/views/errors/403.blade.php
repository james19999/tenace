<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès refusé</title>
    <style>
        *{box-sizing:border-box}body{min-height:100vh;margin:0;padding:24px;display:grid;place-items:center;background:#191f2b;color:#e9edf4;font:16px Arial,sans-serif}.panel{width:min(100%,560px);text-align:center}.code{font-size:14px;letter-spacing:.18em;color:#a5b1c3}.panel h1{font-size:clamp(22px,5vw,30px);font-weight:500;line-height:1.45;margin:14px 0 28px}.actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}.actions button{min-height:46px;padding:0 20px;border:0;border-radius:5px;background:#187b2a;color:#fff;font-size:15px;font-weight:700;cursor:pointer}.actions button:hover{background:#126521}.hint{margin-top:20px;color:#a5b1c3;font-size:14px;line-height:1.5}
    </style>
</head>
<body>
    <main class="panel">
        <div class="code">403</div>
        <h1>Vous n’avez pas accès à cette page.</h1>
        <form class="actions" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Se déconnecter</button>
        </form>
        <p class="hint">Déconnecte-toi pour choisir un autre compte.</p>
        @auth
            <p class="hint">Compte connecté : {{ auth()->user()->name }} · rôle : {{ auth()->user()->user_type }}</p>
        @endauth
    </main>
</body>
</html>
