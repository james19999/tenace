<!doctype html>
<html lang="fr" data-app-theme="{{ $appTheme['appearance_mode'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $appTheme['primary_color'] }}">
    <title>Mon espace client · TENACOS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        :root{--red:{{ $appTheme['primary_color'] }};--red-dark:color-mix(in srgb,var(--red) 84%,#000);--red-contrast:{{ $appTheme['primary_contrast_color'] }};--ink:{{ $appTheme['text_color'] }};--muted:#70798a;--line:#e5e8ee;--paper:{{ $appTheme['surface_color'] }};--bg:{{ $appTheme['background_color'] }};--green:{{ $appTheme['accent_color'] }};--gold:{{ $appTheme['accent_color'] }}}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif}
        a{color:inherit}.shell{max-width:1120px;margin:0 auto;padding:0 22px 60px}.topbar{height:76px;display:flex;align-items:center;justify-content:space-between;gap:18px;border-bottom:1px solid var(--line);margin-bottom:28px}.brand{display:flex;align-items:center;gap:12px;text-decoration:none}.brand-mark{width:38px;height:38px;display:grid;place-items:center;background:var(--red);color:#fff;font-size:14px;font-weight:900;letter-spacing:-.05em}.brand-name{font-size:14px;font-weight:900;letter-spacing:.13em;color:var(--red)}.brand-sub{display:block;color:var(--muted);font-size:11px;font-weight:650;letter-spacing:.04em}.top-actions{display:flex;align-items:center;gap:9px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 15px;border:1px solid #d9dde5;background:#fff;color:var(--ink);text-decoration:none;font:inherit;font-size:14px;font-weight:750;cursor:pointer;transition:background .15s ease,border-color .15s ease}.button:hover{background:#f8f8f9;border-color:#bfc5d0}.button.primary{background:var(--red);border-color:var(--red);color:white}.button.primary:hover{background:var(--red-dark)}.button.quiet{color:var(--muted)}.inline-form{display:inline;margin:0}.flash,.errors{padding:13px 16px;margin:0 0 18px;border-left:4px solid var(--green);background:#edf7f0;color:#24543a}.errors{border-color:#b32929;background:#fff1f1;color:#8a1d1d}
        .page-intro{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:0 0 22px}.eyebrow{display:block;color:var(--red);font-size:11px;font-weight:900;letter-spacing:.17em;text-transform:uppercase}.page-intro h1{font-size:clamp(27px,4vw,38px);line-height:1.12;letter-spacing:-.035em;margin:7px 0 7px}.page-intro p{margin:0;color:var(--muted);max-width:620px}.intro-note{color:var(--muted);font-size:13px;text-align:right;white-space:nowrap}
        .panel{background:var(--paper);border:1px solid var(--line);margin-bottom:18px}.panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px 14px}.panel-head h2{font-size:17px;letter-spacing:-.01em;margin:0}.panel-head p{margin:4px 0 0;color:var(--muted);font-size:13px}.panel-body{padding:0 22px 22px}
        .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));border-top:1px solid var(--line)}.stat{padding:18px 22px;min-height:104px;border-right:1px solid var(--line)}.stat:last-child{border-right:0}.stat-label{display:block;color:var(--muted);font-size:13px;font-weight:650}.stat-value{display:block;margin-top:6px;font-size:27px;line-height:1.1;font-weight:850;letter-spacing:-.035em}.stat-value.level{font-size:21px;color:var(--red)}
        .loyalty{display:grid;grid-template-columns:minmax(0,1fr) 230px;align-items:center;gap:24px;padding:20px 22px;background:#fffaf3;border-top:1px solid #efe3cf}.loyalty-title{font-size:14px;font-weight:800}.loyalty-copy{color:var(--muted);font-size:13px;margin-top:3px}.score{text-align:right}.score-line{display:flex;align-items:baseline;justify-content:flex-end;gap:4px}.score strong{font-size:23px}.score span{color:var(--muted);font-size:12px}.meter{height:5px;background:#eadfcf;margin-top:8px}.meter i{display:block;height:100%;background:var(--gold);max-width:100%}
        .product-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:10px}.product{min-height:62px;display:flex;align-items:center;gap:11px;padding:12px;border:1px solid var(--line);font-size:14px;font-weight:750}.product-index{flex:0 0 30px;height:30px;display:grid;place-items:center;background:#f7eeee;color:var(--red);font-size:11px;font-weight:900}
        .orders{padding:0 22px 20px}.order{border:1px solid var(--line);margin-top:11px}.order-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:15px 16px 12px}.order-ref{font-size:15px;font-weight:850}.order-date{margin-top:2px;color:var(--muted);font-size:12px}.badge{display:inline-flex;align-items:center;min-height:27px;padding:4px 9px;background:#f1f2f4;color:#535d6d;font-size:11px;font-weight:800;white-space:nowrap}.badge.done{background:#e9f5ee;color:#176b45}.badge.cancel{background:#fff0ef;color:#a32c25}.order-items{padding:0 16px 13px;color:var(--muted);font-size:13px}.order-bottom{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:11px 16px;border-top:1px solid var(--line);background:#fcfcfd}.order-total{font-weight:850}.order-bottom .button{min-height:37px;padding:7px 12px;font-size:12px}
        .empty{padding:22px;border:1px dashed #cdd3dc;color:var(--muted);font-size:14px}.pagination{padding:0 22px 20px}.pagination nav{display:flex;justify-content:flex-end}.pagination nav>div:first-child{display:none}.pagination nav>div:last-child{width:100%}.pagination nav>div:last-child>span,.pagination nav>div:last-child>a{float:right}.pagination svg{width:18px}
        .profiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:9px;padding:0 22px 18px}.profile{padding:12px;border:1px solid var(--line);background:var(--paper)}.profile strong,.profile span,.profile small{display:block;overflow-wrap:anywhere}.profile strong{font-size:13px}.profile span,.profile small{margin-top:3px;color:var(--muted);font-size:11px}.profile-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:0 22px 22px}.profile-form .field{margin:0}.profile-form .field input{min-height:43px}.profile-form .button{align-self:end;min-height:43px}.profile-form .field.wide{grid-column:1/-1}.profile-form>.button{grid-column:1/-1}
        .profile-manager{margin-top:18px;padding:18px;border:1px solid var(--line);background:var(--paper)}.profile-manager[hidden]{display:none}.profile-manager-head{margin-bottom:14px}.profile-manager-head h2{margin:0;font-size:17px}.profile-manager-copy{margin:4px 0 0;color:var(--muted);font-size:12px}.profile-manager .profiles{padding:0 0 14px}.profile-manager .profile-form{padding:0}
        .account-dialog{width:min(460px,calc(100% - 28px));max-width:none;padding:0;border:1px solid var(--line);background:var(--paper);color:var(--ink);box-shadow:0 24px 80px #10182740}.account-dialog::backdrop{background:#14182199;backdrop-filter:blur(2px)}.account-dialog-head{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;padding:20px 22px 14px;border-bottom:1px solid var(--line)}.account-dialog-head h2{margin:0;font-size:19px}.account-dialog-head p{margin:4px 0 0;color:var(--muted);font-size:13px}.dialog-close{border:0;background:transparent;color:var(--muted);font-size:23px;line-height:1;cursor:pointer}.account-dialog-actions{display:grid;gap:10px;padding:17px 22px 22px}.account-dialog-actions form{margin:0}.account-dialog-actions .button{width:100%;justify-content:flex-start;text-align:left}.account-dialog-actions .button.primary{justify-content:center;text-align:center}.account-dialog-note{margin:0;color:var(--muted);font-size:12px}
        .catalog-link{white-space:nowrap;box-shadow:0 2px 8px color-mix(in srgb,var(--red) 20%,transparent)}.catalog-label-short{display:none}
        .login-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.95fr);min-height:470px;border:1px solid var(--line);background:#fff}.login-aside{display:flex;flex-direction:column;justify-content:space-between;padding:34px;background:var(--red);color:#fff}.login-aside .eyebrow{color:#f4cfca}.login-aside h1{font-size:clamp(28px,4vw,42px);line-height:1.08;letter-spacing:-.04em;margin:12px 0}.login-aside p{max-width:380px;color:#f2dedd;margin:0}.aside-foot{font-size:12px;color:#e9c6c3}.login-main{padding:34px}.login-main h2{font-size:20px;margin:0 0 5px}.login-main>p{color:var(--muted);font-size:13px;margin:0 0 22px}.field{margin:0 0 16px}.field label{display:block;margin-bottom:6px;font-size:13px;font-weight:800}.field input,.field select{width:100%;min-height:47px;padding:10px 12px;border:1px solid #d6dbe4;background:#fff;color:var(--ink);font:inherit}.field input:focus,.field select:focus{outline:2px solid #ead2d1;border-color:var(--red)}.field-row{display:grid;grid-template-columns:1fr 1.2fr;gap:12px}.login-main .button{width:100%;margin-top:2px}.help{margin-top:18px;padding-top:15px;border-top:1px solid var(--line);color:var(--muted);font-size:12px}.help a{color:var(--red);font-weight:750}
        @media(max-width:700px){.shell{padding:0 13px 36px}.topbar{height:65px;margin-bottom:22px}.brand-mark{width:34px;height:34px}.brand-name{font-size:12px}.top-actions{gap:6px}.top-actions .button{min-height:39px;padding:8px 10px;font-size:12px}.page-intro{display:block;margin-bottom:17px}.intro-note{margin-top:8px;text-align:left}.panel-head{padding:17px 16px 12px}.panel-body{padding:0 16px 17px}.stats{grid-template-columns:1fr}.stat{min-height:0;padding:13px 16px;border-right:0;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px}.stat:last-child{border-bottom:0}.stat-value{margin:0;text-align:right;font-size:22px}.stat-value.level{font-size:18px}.loyalty{grid-template-columns:1fr;gap:12px;padding:16px}.score{text-align:left}.score-line{justify-content:flex-start}.meter{max-width:260px}.product-list{grid-template-columns:1fr 1fr}.orders{padding:0 12px 15px}.order-top{padding:13px 12px 10px}.order-items{padding:0 12px 12px}.order-bottom{align-items:flex-start;flex-direction:column;padding:11px 12px}.order-bottom .button{width:100%}.pagination{padding:0 12px 15px}.login-layout{grid-template-columns:1fr}.login-aside{padding:22px;min-height:205px}.login-aside h1{font-size:30px}.aside-foot{margin-top:25px}.login-main{padding:22px}.field-row{grid-template-columns:1fr;gap:0}}
        @media(max-width:700px){.catalog-label-full{display:none}.catalog-label-short{display:inline}.top-actions .catalog-link{padding:8px 9px;font-size:11px}}
        @media(max-width:700px){.profiles{grid-template-columns:1fr;padding:0 16px 14px}.profile-form{grid-template-columns:1fr;padding:0 16px 17px}.profile-form .field.wide{grid-column:auto}}
        @media(max-width:390px){.top-actions .button{padding:7px 8px;font-size:11px}.product-list{grid-template-columns:1fr}.page-intro h1{font-size:27px}}
        .button.primary{color:var(--red-contrast)}
        .login-aside{color:var(--red-contrast)}
        .login-aside .eyebrow,.login-aside p,.aside-foot{color:color-mix(in srgb,var(--red-contrast) 78%,var(--red))}
        html[data-app-theme="dark"]{color-scheme:dark;--ink:#edf1f5;--muted:#a8b1bd;--line:#373f4b;--paper:#1e232b;--bg:#14171c}
        html[data-app-theme="dark"] .topbar{border-color:var(--line)}
        html[data-app-theme="dark"] .button:not(.primary){background:#252b34;color:var(--ink);border-color:var(--line)}
        html[data-app-theme="dark"] .button:not(.primary):hover{background:#303742}
        html[data-app-theme="dark"] .flash{background:#173b2c;color:#b7ebcb;border-color:#39815b}
        html[data-app-theme="dark"] .errors{background:#452729;color:#ffc0c0;border-color:#b85b5b}
        html[data-app-theme="dark"] .panel,html[data-app-theme="dark"] .login-layout{background:var(--paper);border-color:var(--line)}
        html[data-app-theme="dark"] .loyalty{background:#29271f;border-color:#51472e}
        html[data-app-theme="dark"] .product{background:var(--paper);border-color:var(--line)}
        html[data-app-theme="dark"] .product-index{background:color-mix(in srgb,var(--red) 20%,var(--paper));color:color-mix(in srgb,var(--red) 55%,#fff)}
        html[data-app-theme="dark"] .order{background:var(--paper);border-color:var(--line)}
        html[data-app-theme="dark"] .order-bottom{background:#252b34;border-color:var(--line)}
        html[data-app-theme="dark"] .badge{background:#303742;color:#e2e7ed}
        html[data-app-theme="dark"] .badge.done{background:#173b2c;color:#9be0b9}
        html[data-app-theme="dark"] .badge.cancel{background:#4a2527;color:#ffc0c0}
        html[data-app-theme="dark"] .empty{border-color:var(--line);color:var(--muted)}
        html[data-app-theme="dark"] .login-aside{color:var(--red-contrast)}
        html[data-app-theme="dark"] .login-main{color:var(--ink)}
        html[data-app-theme="dark"] .field input,html[data-app-theme="dark"] .field select{background:#171b21;color:var(--ink);border-color:var(--line)}
        html[data-app-theme="dark"] .field input:focus,html[data-app-theme="dark"] .field select:focus{outline-color:color-mix(in srgb,var(--red) 45%,transparent)}
        .select2-container{width:100%!important}.select2-container--default .select2-selection--single{height:47px;border:1px solid #d6dbe4;border-radius:0;background:#fff}.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:45px;padding-left:12px;color:var(--ink)}.select2-container--default .select2-selection--single .select2-selection__arrow{height:45px}.select2-dropdown{border-color:var(--line);border-radius:0;background:var(--paper);color:var(--ink)}.select2-search--dropdown .select2-search__field{min-height:38px;padding:7px 9px;background:var(--paper);color:var(--ink);border-color:var(--line)}.select2-container--default .select2-results__option{background:var(--paper);color:var(--ink)}.select2-container--default .select2-results__option--highlighted[aria-selected]{background:var(--red);color:var(--red-contrast)}
        html[data-app-theme="dark"] .select2-container--default .select2-selection--single{background:#171b21;border-color:var(--line)}
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <a class="brand" href="{{ route('public-order.app') }}" aria-label="TENACOS, catalogue">
            <span class="brand-mark">T</span><span><span class="brand-name">TENACOS</span><span class="brand-sub">ESPACE CLIENT</span></span>
        </a>
        <div class="top-actions">
            <a class="button primary catalog-link" href="{{ route('public-order.app') }}" aria-label="Voir les produits disponibles"><span class="catalog-label-full">Voir les produits disponibles</span><span class="catalog-label-short">Voir les produits</span></a>
            @if($account)
                <button class="button quiet" id="open-account-actions" type="button">Déconnexion</button>
            @endif
        </div>
    </header>

    @if(session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="errors" role="alert">{{ $errors->first() }}</div>@endif

    @if(!$account)
        <section class="login-layout">
            <div class="login-aside">
                <div><span class="eyebrow">VOTRE ESPACE PERSONNEL</span><h1>Vos commandes,<br>au même endroit.</h1><p>Connectez-vous pour suivre vos achats et retrouver facilement les produits que vous avez commandés.</p></div>
                <div class="aside-foot">Un espace sécurisé, réservé à vos commandes.</div>
            </div>
            <div class="login-main">
                <h2>Se connecter</h2><p>Utilisez le numéro indiqué lors de votre commande.</p>
                <form method="POST" action="{{ route('customer-portal.login') }}">@csrf
                    <div class="field-row">
                        <div class="field"><label for="country_code">Pays</label><select id="country_code" class="js-country-search" name="country_code" required>@foreach($countries as $code=>$country)<option value="{{ $code }}" @selected(old('country_code')===$code)>{{ $country['label'] }} (+{{ $dialCodes[$code] }})</option>@endforeach</select></div>
                        <div class="field"><label for="phone">Téléphone</label><input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" required></div>
                    </div>
                    <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
                    <button class="button primary" type="submit">Accéder à mon espace</button>
                </form>
                <div class="help">Pas encore de mot de passe ? Ouvrez le lien privé de suivi reçu après votre commande pour créer votre espace.</div>
            </div>
        </section>
    @else
        <section class="page-intro">
            <div><span class="eyebrow">VOTRE ESPACE CLIENT</span><h1>Bonjour{{ !empty($customerName) ? ', '.$customerName : '' }}</h1><p>Retrouvez vos commandes, leur avancement et les produits que vous avez déjà choisis.</p></div>
            <div class="intro-note">Compte client TENACOS</div>
        </section>

        <section class="panel" aria-labelledby="activity-title">
            <div class="panel-head"><div><h2 id="activity-title">Votre activité</h2><p>Un aperçu de vos achats chez TENACOS.</p></div></div>
            <div class="stats">
                <div class="stat"><span class="stat-label">Commandes enregistrées</span><strong class="stat-value">{{ $orders->total() }}</strong></div>
                <div class="stat"><span class="stat-label">Commandes livrées</span><strong class="stat-value">{{ $deliveredCount }}</strong></div>
                <div class="stat"><span class="stat-label">Niveau fidélité</span><strong class="stat-value level">{{ $profile ? ($categoryLabels[$profile->category] ?? 'Client') : 'En démarrage' }}</strong></div>
            </div>
            @if($profile)
                <div class="loyalty"><div><div class="loyalty-title">Votre indice de fidélité</div><div class="loyalty-copy">Il évolue avec votre activité et vos commandes.</div></div><div class="score"><div class="score-line"><strong>{{ (int)$profile->score }}</strong><span>/ 100</span></div><div class="meter" role="progressbar" aria-label="Indice de fidélité" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ max(0,min(100,(int)$profile->score)) }}"><i style="width:{{ max(0,min(100,(int)$profile->score)) }}%"></i></div></div></div>
            @endif
        </section>

        @if($recentProducts->isNotEmpty())
            <section class="panel" aria-labelledby="recent-title"><div class="panel-head"><div><h2 id="recent-title">Récemment commandés</h2><p>Les produits de vos dernières commandes.</p></div></div><div class="panel-body"><div class="product-list">@foreach($recentProducts as $item)<div class="product"><span class="product-index">{{ sprintf('%02d',$loop->iteration) }}</span><span>{{ $item->product?->name ?? 'Produit' }}</span></div>@endforeach</div></div></section>
        @endif

        <section class="panel" aria-labelledby="orders-title">
            <div class="panel-head"><div><h2 id="orders-title">Historique des commandes</h2><p>Consultez le détail et le suivi de chaque commande.</p></div></div>
            @if($orders->isEmpty())
                <div class="orders"><div class="empty">Aucune commande n’est encore rattachée à cet espace. Vos prochaines commandes apparaîtront ici.</div></div>
            @else
                <div class="orders">@foreach($orders as $order)
                    @php
                        $legacyStatusLabels = ['delivered' => 'Livrée', 'ordered' => 'Commande enregistrée', 'pending' => 'En attente', 'canceled' => 'Annulée'];
                        $orderStatus = $statusLabels[$order->partner_order_status] ?? ($legacyStatusLabels[strtolower((string) $order->status)] ?? 'Statut à vérifier');
                    @endphp
                    <article class="order">
                        <div class="order-top"><div><div class="order-ref">{{ $order->code ?: 'Commande' }}</div><div class="order-date">{{ optional($order->created_at)->format('d/m/Y à H:i') }}</div></div><span class="badge {{ ($order->partner_order_status === 'delivered' || $order->status === 'delivered') ? 'done' : ($order->partner_order_status === 'canceled' ? 'cancel' : '') }}">{{ $orderStatus }}</span></div>
                        <div class="order-items">@foreach($order->orderItems as $item){{ $item->product?->name ?? 'Produit' }} × {{ $item->quantity }}@if(!$loop->last)<span aria-hidden="true"> · </span>@endif @endforeach</div>
                        <div class="order-bottom"><strong class="order-total">Total : {{ number_format((float)($order->montant ?? $order->total),0,',',' ') }} F</strong>@if($order->public_tracking_token)<a class="button" href="{{ route('public-order.track',$order->public_tracking_token) }}">Voir le suivi</a>@endif</div>
                    </article>
                @endforeach</div>
                <div class="pagination">{{ $orders->links() }}</div>
            @endif
        </section>

        <section class="profile-manager" id="profile-manager" @unless($errors->any() || session('success')) hidden @endunless>
            <div class="profile-manager-head"><h2>Profils de commande</h2>
            <p class="profile-manager-copy">Choisis pour qui tu commandes dans le catalogue. Ajoute ici les personnes que tu commandes régulièrement.</p>
            </div>
            @if($profiles->isNotEmpty())
                <div class="profiles">
                    @foreach($profiles as $profile)
                        <article class="profile">
                            <strong>{{ $profile->name }}</strong>
                            @if($profile->phone)<span>{{ $profile->phone }}</span>@endif
                            @if($profile->email)<small>{{ $profile->email }}</small>@endif
                            @if($profile->address)<small>{{ $profile->address }}</small>@endif
                        </article>
                    @endforeach
                </div>
            @endif
            <form class="profile-form" method="POST" action="{{ route('customer-portal.profiles.store') }}">
                @csrf
                <div class="field"><label for="profile-name">Nom complet</label><input id="profile-name" name="name" maxlength="160" value="{{ old('name') }}" autocomplete="name" required></div>
                <div class="field"><label for="profile-phone">Téléphone</label><input id="profile-phone" name="phone" type="tel" maxlength="50" value="{{ old('phone') }}" autocomplete="tel" required></div>
                <div class="field"><label for="profile-email">E-mail (facultatif)</label><input id="profile-email" name="email" type="email" maxlength="160" value="{{ old('email') }}" autocomplete="email"></div>
                <div class="field"><label for="profile-address">Adresse habituelle (facultatif)</label><input id="profile-address" name="address" maxlength="180" value="{{ old('address') }}" autocomplete="street-address"></div>
                <button class="button primary" type="submit">Ajouter un profil</button>
            </form>
        </section>
    @endif
</main>
@if($account)
    <dialog class="account-dialog" id="account-actions" aria-labelledby="account-actions-title">
        <div class="account-dialog-head">
            <div><h2 id="account-actions-title">Gérer mon accès</h2><p>Choisis ce que tu souhaites faire.</p></div>
            <button class="dialog-close" type="button" data-close-account-dialog aria-label="Fermer">×</button>
        </div>
        <div class="account-dialog-actions">
            <form method="POST" action="{{ route('customer-portal.logout') }}">
                @csrf<input type="hidden" name="destination" value="login">
                <button class="button" type="submit">Changer de compte</button>
            </form>
            <button class="button" id="create-order-profile" type="button">Créer un profil de commande</button>
            <p class="account-dialog-note">Un profil permet de commander au nom d’une autre personne sans quitter ton espace.</p>
            <form method="POST" action="{{ route('customer-portal.logout') }}">
                @csrf<input type="hidden" name="destination" value="catalog">
                <button class="button primary" type="submit">Se déconnecter de tous les comptes sur cet appareil</button>
            </form>
        </div>
    </dialog>
    <script>
        (() => {
            const dialog = document.getElementById('account-actions');
            const manager = document.getElementById('profile-manager');
            document.getElementById('open-account-actions')?.addEventListener('click', () => dialog.showModal());
            dialog.querySelectorAll('[data-close-account-dialog]').forEach((button) => button.addEventListener('click', () => dialog.close()));
            dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
            document.getElementById('create-order-profile')?.addEventListener('click', () => {
                dialog.close();
                manager.hidden = false;
                manager.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => document.getElementById('profile-name').focus(), 250);
            });
        })();
    </script>
@endif
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#country_code').select2({width:'100%', placeholder:'Rechercher un pays…', language:{noResults:()=>'Aucun pays trouvé',searching:()=>'Recherche…'}});
    }
</script>
</body>
</html>
