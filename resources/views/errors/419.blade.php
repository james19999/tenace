<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session expirée – TENACE COSMETIQUE</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <link rel="icon"          href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 52px 48px;
            text-align: center;
            max-width: 440px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            animation: fadeIn .5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0);    }
        }

        .icon-wrap {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: rgba(255, 193, 7, 0.15);
            border: 2px solid rgba(255, 193, 7, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
        }

        .icon-wrap svg {
            width: 44px;
            height: 44px;
        }

        .code {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ffc107;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 16px;
        }

        p {
            font-size: 15px;
            color: rgba(255,255,255,0.6);
            line-height: 1.7;
            margin-bottom: 36px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #e91e8c, #c2185b);
            color: #fff;
            text-decoration: none;
            padding: 13px 34px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(233, 30, 140, 0.35);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(233, 30, 140, 0.5);
            color: #fff;
            text-decoration: none;
        }

        .logo {
            margin-top: 40px;
            opacity: .35;
            font-size: 12px;
            color: #fff;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="card">

        <!-- Icône horloge -->
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
                <line x1="12" y1="2" x2="12" y2="4"/>
                <line x1="12" y1="20" x2="12" y2="22"/>
                <line x1="2"  y1="12" x2="4"  y2="12"/>
                <line x1="20" y1="12" x2="22" y2="12"/>
            </svg>
        </div>

        <div class="code">419 — Session expirée</div>

        <h1>Votre session a expiré</h1>

        <p>
            Pour des raisons de sécurité, votre session a été automatiquement fermée
            après une période d'inactivité.<br>
            Veuillez vous reconnecter pour continuer.
        </p>

        <a href="{{ route('login') }}" class="btn">
            <!-- Icône flèche -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Se reconnecter
        </a>

        <div class="logo">TENACE COSMETIQUE</div>

    </div>

    <!-- Redirection automatique après 10 secondes -->
    <script>
        setTimeout(function () {
            window.location.href = "{{ route('login') }}";
        }, 10000);
    </script>

</body>
</html>
