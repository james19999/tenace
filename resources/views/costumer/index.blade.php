@extends('layouts.admin')

@section('content')
    <div class="customer-directory-page">
        <div class="customer-directory-shell">
            <div class="customer-directory-heading">
                <div>
                    <p class="customer-directory-eyebrow">Relation client</p>
                    <h1>Répertoire des clients</h1>
                    <p>Recherche et consulte les coordonnées des clients enregistrés.</p>
                </div>
                <div class="customer-directory-actions">
                    <a href="{{ route('top-costumers') }}" class="customer-secondary-action"><span class="material-icons" aria-hidden="true">leaderboard</span>Top clients</a>
                    <a href="{{ route('costumer.create') }}" class="customer-primary-action"><span class="material-icons" aria-hidden="true">person_add</span>Ajouter un client</a>
                </div>
            </div>

            @if (Session::has('messages'))
                <div class="alert alert-success customer-directory-alert"><strong>{{ session('messages') }}</strong></div>
            @endif

            <section class="customer-directory-card" aria-labelledby="customer-directory-table-title">
                <div class="customer-directory-card-heading">
                    <div class="customer-directory-card-icon"><span class="material-icons" aria-hidden="true">groups</span></div>
                    <div>
                        <h2 id="customer-directory-table-title">Tous les clients</h2>
                        <p>Utilise la recherche pour retrouver rapidement une fiche.</p>
                    </div>
                </div>

                <div class="customer-directory-table-wrap">
                    <table id="customer-table" class="table customer-directory-table" data-source="{{ route('costumer.data') }}">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Nom</th>
                                <th>Téléphone</th>
                                <th>E-mail</th>
                                <th>Adresse</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <style>
        .customer-directory-page{--customer-red:#861b1b;--customer-ink:#292c36;--customer-muted:#777d89;--customer-line:#e7e9ee;padding:8px 0 30px;color:var(--customer-ink)}
        .customer-directory-page *{box-sizing:border-box}.customer-directory-shell{max-width:1480px;margin:0 auto;padding:0 18px}
        .customer-directory-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:8px 0 22px}
        .customer-directory-eyebrow{margin:0 0 6px;color:var(--customer-red);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .customer-directory-heading h1{margin:0;color:var(--customer-ink);font-size:clamp(27px,3vw,35px);font-weight:850;letter-spacing:-.6px;line-height:1.15}
        .customer-directory-heading>div:first-child>p:last-child{margin:8px 0 0;color:var(--customer-muted);font-size:14px}
        .customer-directory-actions{display:flex;align-items:center;gap:9px;flex-shrink:0}
        .customer-directory-actions a{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:9px 13px;border:1px solid transparent;border-radius:9px;font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap;transition:background .15s ease,transform .15s ease}
        .customer-directory-actions a:hover{transform:translateY(-1px)}.customer-directory-actions .material-icons{font-size:18px}
        .customer-primary-action{background:var(--customer-red);color:#fff!important}.customer-primary-action:hover{background:#6e1515}
        .customer-secondary-action{border-color:#e3e5ea!important;background:#fff;color:#565c68!important}.customer-secondary-action:hover{background:#f8f8fa}
        .customer-directory-alert{border:0;border-radius:10px;margin-bottom:16px}
        .customer-directory-card{min-width:0;overflow:hidden;border:1px solid var(--customer-line);border-radius:15px;background:#fff;box-shadow:0 7px 24px rgba(33,38,48,.045)}
        .customer-directory-card-heading{display:flex;align-items:center;gap:11px;padding:17px 20px;border-bottom:1px solid var(--customer-line)}
        .customer-directory-card-icon{display:grid;place-items:center;width:38px;height:38px;flex:0 0 38px;border-radius:11px;background:#f8eded;color:var(--customer-red)}
        .customer-directory-card-heading h2{margin:0;color:var(--customer-ink);font-size:16px;font-weight:800}.customer-directory-card-heading p{margin:4px 0 0;color:var(--customer-muted);font-size:12px}
        .customer-directory-table-wrap{width:100%;min-width:0;padding:0 15px 12px;overflow-x:auto;-webkit-overflow-scrolling:touch}
        #customer-table{width:100%!important;min-width:880px;margin:0!important;border-collapse:separate;border-spacing:0}
        #customer-table thead th{padding:12px 11px;border:0;border-bottom:1px solid #e5e7ec;background:#f7f8fa;color:#707682;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}
        #customer-table tbody td{padding:12px 11px;border-top:1px solid #eff0f3;color:#414650;font-size:12px;vertical-align:middle}
        #customer-table tbody tr:hover{background:#fcfaf9}#customer-table tbody td:nth-child(2){font-weight:750;color:#333741}
        #customer-table tbody td:nth-child(3){white-space:nowrap;color:#81201d;font-weight:650}#customer-table tbody td:nth-child(4),#customer-table tbody td:nth-child(5){max-width:220px;overflow-wrap:anywhere;color:#686e7a}
        #customer-table tbody td:last-child{min-width:205px;white-space:nowrap}#customer-table tbody td form{display:inline-flex;margin:0}
        #customer-table tbody td .btn-group{display:inline-flex;gap:5px}#customer-table tbody td .btn{display:inline-flex;align-items:center;gap:3px;padding:6px 8px;border:0;border-radius:7px;font-size:10px;font-weight:700}
        #customer-table tbody td .material-icons{font-size:15px}
        .dataTables_wrapper{padding:15px 15px 0}.dataTables_wrapper .dt-buttons{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
        .dataTables_wrapper .dt-buttons .dt-button{padding:7px 10px!important;border:1px solid #e4e6eb!important;border-radius:7px!important;background:#fff!important;background-image:none!important;color:#555b67!important;font-size:11px!important;font-weight:700!important;box-shadow:none!important}
        .dataTables_wrapper .dt-buttons .dt-button:hover{border-color:#d8b8b6!important;background:#fbf5f4!important;color:var(--customer-red)!important}
        .dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_length{margin-bottom:12px;color:#707682;font-size:11px}
        .dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{height:36px;margin-left:6px;padding:5px 9px;border:1px solid #dfe2e8;border-radius:8px;outline:0}
        .dataTables_wrapper .dataTables_filter input:focus,.dataTables_wrapper .dataTables_length select:focus{border-color:#bd7777;box-shadow:0 0 0 3px rgba(134,27,27,.08)}
        .dataTables_wrapper .dataTables_info{padding-top:13px!important;color:#858b96;font-size:11px}.dataTables_wrapper .dataTables_paginate{padding-top:8px}
        .dataTables_wrapper .dataTables_paginate .paginate_button{padding:6px 10px!important;border:0!important;border-radius:7px!important;color:#626875!important;font-size:11px}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover{background:var(--customer-red)!important;color:#fff!important}
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:#f8eded!important;color:var(--customer-red)!important}
        @media(max-width:767.98px){.customer-directory-page{padding-top:0}.customer-directory-shell{padding:0 11px}.customer-directory-heading{display:block;margin:8px 0 16px}.customer-directory-heading h1{font-size:26px}.customer-directory-heading>div:first-child>p:last-child{font-size:13px;line-height:1.45}.customer-directory-actions{display:grid;grid-template-columns:1fr 1fr;margin-top:13px}.customer-directory-actions a{min-height:40px;padding:8px 6px;font-size:11px}.customer-directory-card{border-radius:12px}.customer-directory-card-heading{padding:14px}.customer-directory-table-wrap{padding:0 9px 10px}.dataTables_wrapper{padding:12px 10px 0}.dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_length{float:none!important;text-align:left!important;width:100%;margin:0 0 10px}.dataTables_wrapper .dataTables_filter input{display:block;width:100%;margin:5px 0 0}.dataTables_wrapper .dataTables_length select{margin-left:4px}.dataTables_wrapper .dt-buttons{gap:4px}.dataTables_wrapper .dt-buttons .dt-button{padding:6px 7px!important;font-size:10px!important}.dataTables_wrapper .dataTables_info{float:none!important;text-align:center;padding-bottom:5px}.dataTables_wrapper .dataTables_paginate{float:none!important;text-align:center;padding-bottom:8px}.dataTables_wrapper .dataTables_paginate .paginate_button{padding:6px 8px!important}}
        @media(max-width:360px){.customer-directory-actions{grid-template-columns:1fr}.customer-directory-actions a{width:100%}.customer-directory-card-heading p{font-size:11px}}
        .customer-directory-page .customer-directory-actions a,.customer-directory-page .customer-directory-alert,
        .customer-directory-page .customer-directory-card,.customer-directory-page .customer-directory-card-icon,
        .customer-directory-page #customer-table tbody td .btn,.customer-directory-page .dataTables_wrapper .dt-button,
        .customer-directory-page .dataTables_wrapper .dataTables_filter input,
        .customer-directory-page .dataTables_wrapper .dataTables_length select,
        .customer-directory-page .dataTables_wrapper .dataTables_paginate .paginate_button{border-radius:0!important}
        .customer-directory-page .customer-directory-card{box-shadow:none;border-color:#d9dde5}
        .customer-directory-page .customer-directory-card-heading{background:#fff}
    </style>
@endsection
