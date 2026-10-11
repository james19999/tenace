@extends('layouts.admin')

@section('content')
    @php
        $periodLabel = $month ? ($montharray[$month] . ' ' . now()->year) : ('Année ' . now()->year);
        $displayedRevenue = $costumers->sum('orders_sum_total');
        $displayedOrders = $costumers->sum('orders_count');
    @endphp

    <div class="top-costumers-page">
        <div class="top-costumers-shell">
            <div class="top-costumers-heading">
                <div>
                    <p class="top-costumers-eyebrow">Relation client</p>
                    <h1>Meilleurs clients</h1>
                    <p>Classement selon le montant total de leurs commandes sur la période sélectionnée.</p>
                </div>
                <a href="{{ route('costumer.index') }}" class="top-costumers-back"><span class="material-icons" aria-hidden="true">arrow_back</span>Retour aux clients</a>
            </div>

            <form method="GET" action="{{ route('top-costumers') }}" class="top-costumers-filters">
                <div class="top-costumers-filter-intro">
                    <span class="top-costumers-filter-icon"><span class="material-icons" aria-hidden="true">tune</span></span>
                    <div><h2>Paramètres du classement</h2><p>Choisis la période et le nombre de clients à afficher.</p></div>
                </div>
                <div class="top-costumers-filter-controls">
                    <label class="top-costumers-field" for="top-customer-limit">
                        <span>Nombre de résultats <small>maximum 500</small></span>
                        <input type="number" name="limit" value="{{ $limit }}" min="1" max="500" id="top-customer-limit" class="form-control">
                    </label>
                    <label class="top-costumers-field" for="top-customer-month">
                        <span>Période</span>
                        <select name="month" id="top-customer-month" class="form-control">
                            <option value="" @selected(empty($month))>Tous les mois · {{ now()->year }}</option>
                            @foreach ($montharray as $key => $value)
                                <option value="{{ $key }}" @selected($month === (int) $key)>{{ $value }} · {{ now()->year }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="top-costumers-apply"><span class="material-icons" aria-hidden="true">filter_alt</span>Appliquer</button>
                </div>
            </form>

            @if (Session::has('messages'))
                <div class="alert alert-success top-costumers-alert"><strong>{{ session('messages') }}</strong></div>
            @endif

            <div class="top-costumers-summary">
                <article class="top-costumers-stat">
                    <span class="top-costumers-stat-icon"><span class="material-icons" aria-hidden="true">groups</span></span>
                    <div><small>Clients classés</small><strong>{{ number_format($costumers->count(), 0, ',', ' ') }}</strong></div>
                </article>
                <article class="top-costumers-stat top-costumers-stat-green">
                    <span class="top-costumers-stat-icon"><span class="material-icons" aria-hidden="true">shopping_bag</span></span>
                    <div><small>Commandes représentées</small><strong>{{ number_format($displayedOrders, 0, ',', ' ') }}</strong></div>
                </article>
                <article class="top-costumers-stat top-costumers-stat-blue">
                    <span class="top-costumers-stat-icon"><span class="material-icons" aria-hidden="true">payments</span></span>
                    <div><small>Montant cumulé affiché</small><strong>{{ number_format($displayedRevenue, 0, ',', ' ') }} F</strong></div>
                </article>
            </div>

            <section class="top-costumers-table-card" aria-labelledby="top-costumers-list-title">
                <div class="top-costumers-table-heading">
                    <div><h2 id="top-costumers-list-title">Classement · {{ $periodLabel }}</h2><p>Les clients sont classés du montant de commandes le plus élevé au plus faible.</p></div>
                    <span class="top-costumers-period"><span class="material-icons" aria-hidden="true">calendar_month</span>{{ $periodLabel }}</span>
                </div>
                <div class="top-costumers-table-wrap">
                    <table id="example" class="table top-costumers-table">
                        <thead><tr><th>Rang</th><th>Client</th><th>Téléphone</th><th>E-mail</th><th>Adresse</th><th>Activité sur la période</th><th>Fiche</th></tr></thead>
                        <tbody>
                            @forelse ($costumers as $costumer)
                                <tr>
                                    <td><span class="top-costumers-rank {{ $loop->first ? 'top-costumers-rank-first' : '' }}">{{ $loop->iteration }}</span></td>
                                    <td class="top-costumers-name">{{ $costumer->name }}</td>
                                    <td class="top-costumers-phone">{{ $costumer->phone }}</td>
                                    <td>{{ $costumer->email ?: '—' }}</td>
                                    <td>{{ $costumer->adresse ?: '—' }}</td>
                                    <td>
                                        <div class="top-costumers-activity">
                                            @if ($loop->first)<span class="top-costumers-best">Top 1</span>@endif
                                            <span><strong>{{ number_format($costumer->orders_count, 0, ',', ' ') }}</strong> commande(s)</span>
                                            <strong class="top-costumers-amount">{{ number_format($costumer->orders_sum_total ?? 0, 0, ',', ' ') }} F</strong>
                                        </div>
                                    </td>
                                    <td><a href="{{ route('view-costumers', $costumer) }}" class="top-costumers-view"><span class="material-icons" aria-hidden="true">arrow_forward</span><span>Voir</span></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="top-costumers-empty">Aucun client avec des commandes sur cette période.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <style>
        .top-costumers-page{--tc-red:#861b1b;--tc-ink:#292c36;--tc-muted:#777d89;--tc-line:#e7e9ee;padding:8px 0 32px;color:var(--tc-ink)}
        .top-costumers-page *{box-sizing:border-box}.top-costumers-shell{max-width:1480px;margin:0 auto;padding:0 18px}
        .top-costumers-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:8px 0 20px}
        .top-costumers-eyebrow{margin:0 0 6px;color:var(--tc-red);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .top-costumers-heading h1{margin:0;color:var(--tc-ink);font-size:clamp(27px,3vw,35px);font-weight:850;letter-spacing:-.6px;line-height:1.15}
        .top-costumers-heading p:not(.top-costumers-eyebrow){margin:8px 0 0;color:var(--tc-muted);font-size:14px}
        .top-costumers-back{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:9px 13px;border:1px solid var(--tc-line);border-radius:9px;background:#fff;color:#505663!important;font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap}
        .top-costumers-back:hover{border-color:#d5b8b6;background:#fcf7f6;color:var(--tc-red)!important}
        .top-costumers-filters{margin-bottom:14px;padding:16px 18px;border:1px solid var(--tc-line);border-radius:14px;background:#fff;box-shadow:0 5px 18px rgba(33,38,48,.035)}
        .top-costumers-filter-intro{display:flex;align-items:center;gap:10px;margin-bottom:14px}.top-costumers-filter-icon{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#f8eded;color:var(--tc-red)}
        .top-costumers-filter-intro h2{margin:0;font-size:14px;font-weight:800}.top-costumers-filter-intro p{margin:3px 0 0;color:var(--tc-muted);font-size:11px}
        .top-costumers-filter-controls{display:grid;grid-template-columns:minmax(160px,.7fr) minmax(220px,1fr) auto;align-items:end;gap:12px}
        .top-costumers-field{display:block;min-width:0;margin:0}.top-costumers-field>span{display:flex;align-items:center;gap:6px;margin-bottom:6px;color:#505560;font-size:11px;font-weight:750}.top-costumers-field>span small{color:#969ba4;font-size:10px;font-weight:500}
        .top-costumers-field .form-control{height:41px;border:1px solid #dfe2e8;border-radius:8px;box-shadow:none;font-size:12px}.top-costumers-field .form-control:focus{border-color:#bd7777;box-shadow:0 0 0 3px rgba(134,27,27,.08)}
        .top-costumers-apply{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-width:116px;height:41px;padding:8px 12px;border:1px solid var(--tc-red);border-radius:8px;background:var(--tc-red);color:#fff;font-size:12px;font-weight:750}
        .top-costumers-apply .material-icons{font-size:17px}.top-costumers-apply:hover{background:#6e1515}
        .top-costumers-alert{border:0;border-radius:10px}
        .top-costumers-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px;margin-bottom:14px}
        .top-costumers-stat{display:flex;align-items:center;gap:11px;min-width:0;padding:12px 14px;border:1px solid var(--tc-line);border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(33,38,48,.03)}
        .top-costumers-stat-icon{display:grid;place-items:center;width:36px;height:36px;flex:0 0 36px;border-radius:10px;background:#f8eded;color:var(--tc-red)}
        .top-costumers-stat-green .top-costumers-stat-icon{background:#edf6f0;color:#45805a}.top-costumers-stat-blue .top-costumers-stat-icon{background:#eef3fa;color:#5878a4}
        .top-costumers-stat small{display:block;color:var(--tc-muted);font-size:10px}.top-costumers-stat strong{display:block;overflow-wrap:anywhere;margin-top:2px;color:var(--tc-ink);font-size:17px;font-weight:850;line-height:1.25}
        .top-costumers-table-card{min-width:0;overflow:hidden;border:1px solid var(--tc-line);border-radius:14px;background:#fff;box-shadow:0 6px 21px rgba(33,38,48,.04)}
        .top-costumers-table-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--tc-line)}
        .top-costumers-table-heading h2{margin:0;color:var(--tc-ink);font-size:16px;font-weight:800}.top-costumers-table-heading p{margin:4px 0 0;color:var(--tc-muted);font-size:11px}
        .top-costumers-period{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:18px;background:#f8eeee;color:var(--tc-red);font-size:10px;font-weight:750;white-space:nowrap}.top-costumers-period .material-icons{font-size:15px}
        .top-costumers-table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}.top-costumers-table{width:100%;min-width:970px;margin:0;border-collapse:separate;border-spacing:0}
        .top-costumers-table thead th{padding:11px 12px;border:0;border-bottom:1px solid #e5e7ec;background:#f7f8fa;color:#727884;font-size:10px;font-weight:800;letter-spacing:.055em;text-transform:uppercase;white-space:nowrap}
        .top-costumers-table tbody td{padding:12px;border-top:1px solid #eff0f3;color:#505560;font-size:11px;vertical-align:middle}.top-costumers-table tbody tr:hover{background:#fcfaf9}
        .top-costumers-table tbody td:nth-child(4),.top-costumers-table tbody td:nth-child(5){max-width:190px;overflow-wrap:anywhere;color:#767c87}.top-costumers-name{font-size:12px!important;font-weight:750;color:#343842!important}.top-costumers-phone{color:#81201d!important;font-weight:650;white-space:nowrap}
        .top-costumers-rank{display:inline-grid;place-items:center;width:27px;height:27px;border-radius:9px;background:#f1f2f5;color:#626875;font-size:11px;font-weight:850}.top-costumers-rank-first{background:#fff1d7;color:#976810}
        .top-costumers-activity{display:grid;grid-template-columns:auto auto;align-items:center;gap:5px 8px;min-width:175px}.top-costumers-activity>span:nth-child(2){color:#6b707b;font-size:10px}.top-costumers-activity>span:nth-child(2) strong{color:#383c45}.top-costumers-best{width:max-content;padding:3px 7px;border-radius:14px;background:#fff1d7;color:#946710;font-size:9px;font-weight:800}.top-costumers-amount{color:#34764c;font-size:11px;white-space:nowrap}
        .top-costumers-view{display:inline-flex;align-items:center;gap:4px;padding:6px 9px;border:1px solid #e7d7d6;border-radius:7px;color:var(--tc-red)!important;font-size:10px;font-weight:750;text-decoration:none;white-space:nowrap}.top-costumers-view .material-icons{font-size:15px}.top-costumers-view:hover{background:#f9eeee}
        .top-costumers-empty{padding:35px 15px!important;text-align:center;color:var(--tc-muted)!important}
        .dataTables_wrapper{padding:12px 13px}.dataTables_wrapper .dt-buttons{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px}.dataTables_wrapper .dt-buttons .dt-button{padding:6px 9px!important;border:1px solid #e4e6eb!important;border-radius:7px!important;background:#fff!important;background-image:none!important;color:#555b67!important;font-size:10px!important;font-weight:700!important;box-shadow:none!important}
        .dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_length{margin-bottom:10px;color:#707682;font-size:10px}.dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{height:34px;margin-left:5px;padding:4px 8px;border:1px solid #dfe2e8;border-radius:7px}
        .dataTables_wrapper .dataTables_info{padding-top:10px!important;color:#858b96;font-size:10px}.dataTables_wrapper .dataTables_paginate{padding-top:5px}.dataTables_wrapper .dataTables_paginate .paginate_button{padding:5px 8px!important;border:0!important;border-radius:6px!important;color:#626875!important;font-size:10px}.dataTables_wrapper .dataTables_paginate .paginate_button.current,.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover{background:var(--tc-red)!important;color:#fff!important}
        @media(max-width:767.98px){.top-costumers-page{padding-top:0}.top-costumers-shell{padding:0 11px}.top-costumers-heading{display:block;margin:8px 0 16px}.top-costumers-heading h1{font-size:26px}.top-costumers-heading p:not(.top-costumers-eyebrow){font-size:13px;line-height:1.45}.top-costumers-back{width:100%;margin-top:12px}.top-costumers-filters{padding:13px;border-radius:12px}.top-costumers-filter-controls{grid-template-columns:1fr 1fr;gap:10px}.top-costumers-field:nth-child(2){grid-column:1/-1}.top-costumers-apply{grid-column:1/-1;width:100%}.top-costumers-summary{grid-template-columns:1fr;gap:7px}.top-costumers-stat{padding:10px 12px}.top-costumers-table-card{border-radius:12px}.top-costumers-table-heading{align-items:flex-start;padding:13px}.top-costumers-table-heading h2{font-size:14px}.top-costumers-table-heading p{max-width:230px;line-height:1.4}.top-costumers-period{font-size:9px}.top-costumers-table-wrap{overflow-x:auto}.top-costumers-table{min-width:850px}.dataTables_wrapper{padding:10px 8px}.dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_length{float:none!important;width:100%;text-align:left!important}.dataTables_wrapper .dataTables_filter input{display:block;width:100%;margin:5px 0 8px}.dataTables_wrapper .dataTables_info{float:none!important;text-align:center;padding-bottom:5px}.dataTables_wrapper .dataTables_paginate{float:none!important;text-align:center;padding-bottom:5px}}
        @media(max-width:380px){.top-costumers-filter-controls{grid-template-columns:1fr}.top-costumers-field:nth-child(2),.top-costumers-apply{grid-column:auto}.top-costumers-table-heading{display:block}.top-costumers-period{margin-top:9px}}
    </style>
@endsection
