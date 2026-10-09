<div class="service-cases" wire:keydown.escape="closeAllModals">
    <style>
        /* ===== ISOLATION ABSOLUE DES MODALES DU SERVICE CLIENT ===== */
        body.sc-modal-open {
            overflow: hidden !important;
        }
        .service-cases .sc-modal-backdrop {
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 999999 !important;
            background: rgba(18, 14, 18, 0.65) !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 24px 16px !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            margin: 0 !important;
        }
        .service-cases .sc-modal {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: min(840px, 96vw) !important;
            max-height: calc(100vh - 48px) !important;
            height: min(860px, calc(100vh - 48px)) !important;
            background: #ffffff !important;
            border-radius: 14px !important;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4) !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            margin: 0 auto !important;
        }
        .service-cases .sc-modal.sc-modal-narrow {
            width: min(580px, 96vw) !important;
            height: auto !important;
            max-height: calc(100vh - 48px) !important;
        }
        .service-cases .sc-modal > form {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            height: 100% !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .service-cases .sc-modal-head {
            position: relative !important;
            z-index: 100 !important;
            flex: 0 0 auto !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            gap: 16px !important;
            padding: 20px 24px !important;
            background: #ffffff !important;
            border-bottom: 1px solid #eef0f3 !important;
            box-sizing: border-box !important;
        }
        .service-cases .sc-modal-head > div {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
            gap: 4px !important;
        }
        .service-cases .sc-modal-head .sc-eyebrow {
            font-size: 11px !important;
            font-weight: 800 !important;
            color: #7e1615 !important;
            letter-spacing: 1.2px !important;
            text-transform: uppercase !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .service-cases .sc-modal-head h2 {
            font-size: 19px !important;
            font-weight: 750 !important;
            color: #24252f !important;
            margin: 2px 0 0 0 !important;
            padding: 0 !important;
            line-height: 1.25 !important;
        }
        .service-cases .sc-modal-head p {
            font-size: 13px !important;
            color: #787a86 !important;
            margin: 2px 0 0 0 !important;
            padding: 0 !important;
            line-height: 1.4 !important;
        }
        .service-cases .sc-modal-head .sc-icon-button {
            flex: 0 0 32px !important;
            width: 32px !important;
            height: 32px !important;
            margin: 0 !important;
            background: #f4f3f6 !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
        }
        .service-cases .sc-modal-body {
            position: relative !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
            padding: 22px 24px !important;
            background: #ffffff !important;
            box-sizing: border-box !important;
        }
        .service-cases .sc-response-suggestions {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 6px !important;
            margin-top: 8px !important;
        }
        .service-cases .sc-response-suggestions small {
            flex: 0 0 100% !important;
            color: #777985 !important;
            font-size: 11px !important;
            margin-bottom: 1px !important;
        }
        .service-cases .sc-response-suggestions button {
            max-width: 100% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            border: 1px solid #ead4d3 !important;
            border-radius: 14px !important;
            padding: 5px 10px !important;
            background: #fff8f7 !important;
            color: #79201e !important;
            font-size: 12px !important;
            cursor: pointer !important;
        }
        .service-cases .sc-response-suggestions button:hover {
            background: #f7e9e8 !important;
        }
        .service-cases .sc-modal-foot {
            position: relative !important;
            z-index: 100 !important;
            flex: 0 0 auto !important;
            display: flex !important;
            justify-content: flex-end !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 14px 24px !important;
            background: #fbfbfd !important;
            border-top: 1px solid #eef0f3 !important;
            box-sizing: border-box !important;
        }
        .service-cases .sc-activity-toggle {
            display: flex !important;
            gap: 10px !important;
            margin-bottom: 16px !important;
        }
        .service-cases .sc-activity-toggle label {
            flex: 1 !important;
            margin: 0 !important;
        }
        @media(max-width: 600px) {
            .service-cases .sc-modal-backdrop {
                padding: 8px !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .service-cases .sc-modal {
                width: calc(100vw - 16px) !important;
                height: min(92vh, 860px) !important;
                max-height: 92vh !important;
                border-radius: 14px !important;
                margin: auto !important;
            }
            .service-cases .sc-modal.sc-modal-narrow {
                height: auto !important;
                max-height: 92vh !important;
            }
            .service-cases .sc-modal-head {
                padding: 16px 18px 12px !important;
            }
            .service-cases .sc-modal-body {
                padding: 16px 18px !important;
            }
            .service-cases .sc-modal-foot {
                padding: 12px 18px !important;
            }
        }
    </style>
    @php
        $statusLabels = ['new' => 'Nouveau', 'analysis' => 'En cours d’analyse', 'waiting_customer' => 'En attente de la cliente', 'waiting_internal' => 'Action interne attendue', 'support_in_progress' => 'Accompagnement en cours', 'to_follow' => 'À suivre', 'resolved' => 'Résolu', 'closed' => 'Clôturé'];
        $typeLabels = ['complaint' => 'Réclamation', 'dissatisfied' => 'Insatisfaction', 'personalized_support' => 'Accompagnement', 'information' => 'Demande d’information'];
        $priorityLabels = ['low' => 'Faible', 'normal' => 'Normale', 'high' => 'Élevée', 'urgent' => 'Urgente'];
        $statusClasses = ['new' => 'sc-status-new', 'analysis' => 'sc-status-analysis', 'waiting_customer' => 'sc-status-waiting', 'waiting_internal' => 'sc-status-internal', 'support_in_progress' => 'sc-status-support', 'to_follow' => 'sc-status-follow', 'resolved' => 'sc-status-resolved', 'closed' => 'sc-status-closed'];
        $priorityClasses = ['low' => 'sc-priority-low', 'normal' => 'sc-priority-normal', 'high' => 'sc-priority-high', 'urgent' => 'sc-priority-urgent'];
    @endphp

    @if (session()->has('serviceCaseMessage'))
        <div class="alert sc-flash alert-dismissible fade show" role="alert">
            <span class="material-icons">check_circle</span><span>{{ session('serviceCaseMessage') }}</span>
            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if (session()->has('serviceCaseWarning'))
        <div class="alert sc-flash sc-flash-warning alert-dismissible fade show" role="alert">
            <span class="material-icons">info</span><span>{{ session('serviceCaseWarning') }}</span>
            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @if (!$case)
        <section class="sc-hero">
            <div class="sc-hero-copy">
                <div class="sc-eyebrow"><span class="material-icons">support_agent</span> SERVICE CLIENT</div>
                <h1>Réclamations et accompagnement</h1>
                <p>Chaque demande a un responsable, une prochaine étape et un historique clair.</p>
            </div>
            @if ($canCreateCases = in_array(Auth::user()->user_type, ['ADMINUSER', 'MNG', 'SCR', 'CALLCENTER'], true))
                <button type="button" class="btn sc-primary-btn" wire:click="openCreateModal"><span class="material-icons">add</span>Nouveau dossier</button>
            @endif
        </section>

        <div class="sc-metrics">
            <button type="button" class="sc-metric" wire:click="showOpenCases">
                <span class="sc-metric-icon sc-icon-red"><span class="material-icons">folder_open</span></span>
                <span class="sc-metric-copy"><small>Dossiers ouverts</small><strong>{{ number_format($counts['open']) }}</strong></span>
                <span class="sc-metric-caption">À traiter</span>
            </button>
            <button type="button" class="sc-metric" wire:click="showUrgentCases">
                <span class="sc-metric-icon sc-icon-amber"><span class="material-icons">priority_high</span></span>
                <span class="sc-metric-copy"><small>Priorité urgente</small><strong>{{ number_format($counts['urgent']) }}</strong></span>
                <span class="sc-metric-caption">À traiter en premier</span>
            </button>
            <button type="button" class="sc-metric" wire:click="showOverdueCases">
                <span class="sc-metric-icon sc-icon-orange"><span class="material-icons">event_busy</span></span>
                <span class="sc-metric-copy"><small>Suivis en retard</small><strong>{{ number_format($counts['overdue']) }}</strong></span>
                <span class="sc-metric-caption">Échéance dépassée</span>
            </button>
            <button type="button" class="sc-metric" wire:click="showCompletedCases">
                <span class="sc-metric-icon sc-icon-green"><span class="material-icons">task_alt</span></span>
                <span class="sc-metric-copy"><small>Résolus / clôturés</small><strong>{{ number_format($counts['resolved']) }}</strong></span>
                <span class="sc-metric-caption">Traitement terminé</span>
            </button>
        </div>

        <section class="sc-dashboard">
            <div class="sc-dashboard-head"><div><span class="sc-eyebrow">PILOTAGE</span><h2>Vue du service client</h2><p>Les chiffres suivent la période et le responsable sélectionnés.</p></div><div class="sc-dashboard-filters"><label><small>Du</small><input type="date" class="form-control" wire:model="dashboardFrom"></label><label><small>Au</small><input type="date" class="form-control" wire:model="dashboardTo"></label>@if($canManageCases)<label><small>Responsable</small><select class="form-control" wire:model="dashboardAssignee"><option value="">Toute l’équipe</option>@foreach($assignees as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></label>@endif</div></div>
            <div class="sc-dashboard-grid">
                <div class="sc-dashboard-summary"><div class="sc-dashboard-total"><span>Total des dossiers</span><strong>{{ number_format($dashboard['total']) }}</strong><small>{{ number_format($dashboard['closed']) }} clôturé(s)</small></div><div class="sc-dashboard-kpis"><article><small>Dossiers résolus</small><strong>{{ $dashboard['resolved_rate'] }}%</strong><span>{{ number_format($dashboard['closed']) }} clôturés</span></article><article><small>Délai moyen de résolution</small><strong>@if($dashboard['average_resolution_hours'] !== null){{ $dashboard['average_resolution_hours'] >= 24 ? number_format($dashboard['average_resolution_hours'] / 24, 1, ',', ' ').' j' : number_format($dashboard['average_resolution_hours'], 0, ',', ' ').' h' }}@else—@endif</strong><span>De la création à la résolution</span></article><article><small>Satisfaction moyenne</small><strong>@if($dashboard['average_satisfaction'] !== null){{ number_format($dashboard['average_satisfaction'], 1, ',', ' ') }}<em>/5</em>@else—@endif</strong><span>@if($dashboard['ratings_count']){{ $dashboard['satisfied_rate'] }}% de notes 4–5/5 · {{ $dashboard['ratings_count'] }} retour(s) @else Aucun retour après traitement @endif</span></article></div></div>
                <div class="sc-type-breakdown"><div class="sc-breakdown-title"><strong>Dossiers par nature</strong><small>{{ number_format($dashboard['total']) }} au total</small></div>@foreach([['complaint','Réclamations','#8a2522'],['dissatisfied','Insatisfactions','#bd542c'],['support','Accompagnements','#28716d'],['information','Demandes d’information','#7b65a1']] as [$key,$label,$color])@php $typeCount=$dashboard[$key]; $typeWidth=$dashboard['total'] ? round($typeCount/$dashboard['total']*100) : 0; @endphp<div class="sc-type-row"><div><span>{{ $label }}</span><strong>{{ number_format($typeCount) }}</strong></div><div class="sc-type-track"><span style="width:{{ $typeWidth }}%;background:{{ $color }}"></span></div></div>@endforeach</div>
                <div class="sc-agent-breakdown"><div class="sc-breakdown-title"><strong>Dossiers par responsable</strong><small>Top 5</small></div>@forelse($dashboard['by_assignee'] as $agent)<div class="sc-agent-row"><span class="sc-agent-avatar">{{ mb_substr($agent->assignee?->name ?: '—', 0, 1) }}</span><span class="sc-agent-name">{{ $agent->assignee?->name ?: 'Non attribué' }}</span><strong>{{ number_format($agent->case_count) }}</strong></div>@empty<div class="sc-empty-compact">Aucun dossier sur cette période.</div>@endforelse</div>
            </div>
        </section>

        <section class="sc-panel">
            <div class="sc-case-tabs" role="tablist" aria-label="Dossiers du service client">
                <button type="button" class="{{ $archiveView ? '' : 'is-active' }}" wire:click="showActiveCases" role="tab" aria-selected="{{ $archiveView ? 'false' : 'true' }}">
                    <span class="material-icons">folder_open</span>Dossiers
                </button>
                <button type="button" class="{{ $archiveView ? 'is-active' : '' }}" wire:click="showArchivedCases" role="tab" aria-selected="{{ $archiveView ? 'true' : 'false' }}">
                    <span class="material-icons">inventory_2</span>Archives <span class="sc-case-tab-count">{{ number_format($counts['archived']) }}</span>
                </button>
            </div>
            <div class="sc-panel-heading">
                <div><h2>{{ $archiveView ? 'Dossiers archivés' : 'Registre des dossiers' }}</h2><p>{{ $archiveView ? 'Les dossiers clôturés sont conservés ici et peuvent être restaurés.' : 'Retrouvez rapidement une cliente ou une demande.' }}</p></div>
                <div class="sc-result-count">{{ $caseRows->total() }} dossier(s)</div>
            </div>
            <div class="sc-filters">
                <label class="sc-search"><span class="material-icons">search</span><input type="search" wire:model.debounce.400ms="search" placeholder="N° dossier, nom, téléphone ou e-mail"></label>
                <label class="sc-filter-field"><span>Statut</span><select class="form-control" wire:model="statusFilter" aria-label="Filtrer par statut">
                    <option value="open">Dossiers ouverts</option><option value="overdue">Suivis en retard</option><option value="">Tous les statuts</option><option value="completed">Résolus et clôturés</option>
                    @foreach ($statusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></label>
                <label class="sc-filter-field"><span>Type de dossier</span><select class="form-control" wire:model="typeFilter" aria-label="Filtrer par type">
                    <option value="">Tous les types</option>@foreach ($typeLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></label>
                <label class="sc-filter-field"><span>Priorité</span><select class="form-control" wire:model="priorityFilter" aria-label="Filtrer par priorité">
                    <option value="">Toutes les priorités</option>@foreach ($priorityLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></label>
                @if ($canManageCases)
                    <label class="sc-filter-field"><span>Responsable</span><select class="form-control" wire:model="assigneeFilter" aria-label="Filtrer par responsable"><option value="">Tous les responsables</option>@foreach ($assignees as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></label>
                @endif
                <label class="sc-filter-field sc-date-field"><span>Créé à partir du</span><input class="form-control sc-date-filter" type="date" wire:model="dateFrom" aria-label="Créé à partir du"></label>
                <label class="sc-filter-field sc-date-field"><span>Créé jusqu’au</span><input class="form-control sc-date-filter" type="date" wire:model="dateTo" aria-label="Créé jusqu’au"></label>
            </div>

            <div class="table-responsive sc-table-wrap">
                <table class="table sc-table mb-0">
                    <thead><tr><th>Dossier / cliente</th><th>Nature</th><th>Produit</th><th>Responsable</th><th>Priorité</th><th>Statut</th><th>Prochain suivi</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($caseRows as $row)
                            @php
                                $isOverdue = $row->next_follow_up_at && $row->next_follow_up_at->isPast() && !in_array($row->status, ['resolved', 'closed'], true);
                                $archiveAccessRequest = $archiveView && ! $isServiceAdmin
                                    ? $row->archiveAccessRequests->first(fn ($access) => $access->archive_snapshot_at && $row->archived_at && $access->archive_snapshot_at->equalTo($row->archived_at))
                                    : null;
                                $canOpenArchivedCase = ! $archiveView || $isServiceAdmin || $archiveAccessRequest?->status === 'approved';
                            @endphp
                            <tr class="sc-case-row {{ $isOverdue ? 'sc-row-overdue' : '' }}">
                                <td data-label="{{ $archiveView && ! $canOpenArchivedCase ? 'Dossier' : 'Dossier / cliente' }}">
                                    @if($canOpenArchivedCase)<a class="sc-case-link" href="{{ route('service-cases.show', $row->id, false) }}{{ $archiveView ? '?archiveView=1' : '' }}">{{ $row->case_number }}</a>@else<strong class="sc-case-link">{{ $row->case_number }}</strong>@endif
                                    @if(! $archiveView || $canOpenArchivedCase)<span class="sc-customer-name">{{ $row->customer->name }}</span><span class="sc-customer-phone">{{ $row->customer->phone }}</span>@elseif($archiveAccessRequest?->status === 'pending')<span class="sc-customer-phone">Accès en attente de validation</span>@else<span class="sc-customer-phone">Accès soumis à validation administrative</span>@endif
                                </td>
                                <td data-label="Nature">@if(! $archiveView || $canOpenArchivedCase)<span class="sc-cell-value"><span class="sc-type-dot"></span>{{ $typeLabels[$row->case_type] ?? $row->case_type }}</span>@else—@endif</td>
                                <td data-label="Produit">@if(! $archiveView || $canOpenArchivedCase)<div class="sc-product-list">@forelse($row->products->pluck('name')->filter() as $productName)<span>{{ $productName }}</span>@empty<span>{{ $row->product?->name ?: '—' }}</span>@endforelse</div>@else—@endif</td>
                                <td data-label="Responsable">@if(! $archiveView || $canOpenArchivedCase)<span class="sc-cell-value">{{ $row->assignee?->name ?: 'À attribuer' }}</span>@else—@endif</td>
                                <td data-label="Priorité">@if(! $archiveView || $canOpenArchivedCase)<span class="sc-priority {{ $priorityClasses[$row->priority] ?? '' }}">{{ $priorityLabels[$row->priority] ?? $row->priority }}</span>@else—@endif</td>
                                <td data-label="Statut">@if(! $archiveView || $canOpenArchivedCase)<span class="sc-status {{ $statusClasses[$row->status] ?? '' }}">{{ $statusLabels[$row->status] ?? $row->status }}</span>@else Accès requis @endif</td>
                                <td data-label="Prochain suivi" class="{{ $isOverdue ? 'sc-overdue-date' : '' }}">@if(! $archiveView || $canOpenArchivedCase)<span class="sc-cell-value">{{ $row->next_follow_up_at?->format('d/m/Y H:i') ?: 'Non programmé' }} @if($isOverdue)<small>En retard</small>@endif</span>@else—@endif</td>
                                <td data-label="Actions"><div class="sc-case-row-actions">
                                    @if($canOpenArchivedCase)<a class="sc-open-link" href="{{ route('service-cases.show', $row->id, false) }}{{ $archiveView ? '?archiveView=1' : '' }}" aria-label="Consulter le dossier"><span class="material-icons">arrow_forward</span></a>@endif
                                    @if($isServiceAdmin && $archiveView)
                                        <button type="button" class="sc-open-link sc-archive-action" wire:click="openRestoreConfirmation({{ $row->id }})" aria-label="Restaurer le dossier" title="Restaurer"><span class="material-icons">unarchive</span></button>
                                    @elseif($canManageCases && ! $archiveView && $row->status === 'closed')
                                        <button type="button" class="sc-open-link sc-archive-action" wire:click="openArchiveConfirmation({{ $row->id }})" aria-label="Archiver le dossier" title="Archiver"><span class="material-icons">archive</span></button>
                                    @endif
                                    @if($archiveView && ! $isServiceAdmin && $archiveAccessRequest?->status !== 'approved')
                                        @if($archiveAccessRequest?->status === 'pending')<button type="button" class="sc-open-link sc-archive-action" disabled title="Demande en attente" aria-label="Demande d’accès en attente"><span class="material-icons">hourglass_top</span></button>
                                        @else<button type="button" class="sc-open-link sc-archive-action" wire:click="requestArchiveAccess({{ $row->id }})" aria-label="Demander l’accès" title="Demander l’accès"><span class="material-icons">lock_open</span></button>@endif
                                    @endif
                                </div></td>
                            </tr>
                        @empty
                            <tr class="sc-empty-row"><td colspan="8"><div class="sc-empty"><span class="material-icons">folder_off</span><strong>Aucun dossier trouvé</strong><span>Modifiez les filtres ou créez un nouveau dossier.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="sc-pagination">{{ $caseRows->links() }}</div>
        </section>

        @if ($showCreateModal)
            <div class="sc-modal-backdrop" role="presentation" wire:click.self="closeAllModals">
                <section class="sc-modal" role="dialog" aria-modal="true" aria-labelledby="sc-create-title">
                    <header class="sc-modal-head"><div><span class="sc-eyebrow">NOUVEAU SUIVI</span><h2 id="sc-create-title">Créer un dossier client</h2></div><button type="button" class="sc-icon-button" wire:click="closeAllModals" aria-label="Fermer"><span class="material-icons">close</span></button></header>
                    <form wire:submit.prevent="createCase">
                        <div class="sc-modal-body">
                            <div class="sc-form-section"><div class="sc-section-title"><span>01</span><div><strong>Cliente et achat</strong><small>Recherchez une fiche existante pour éviter les doublons.</small></div></div>
                                <label class="sc-label">Rechercher une cliente <b>*</b></label>
                                <div class="sc-customer-search"><input class="form-control" type="search" wire:model.debounce.350ms="customerSearch" placeholder="Nom, téléphone ou e-mail"><span class="material-icons">search</span></div>
                                @error('selectedCustomerId')<small class="text-danger">{{ $message }}</small>@enderror
                                @if ($customerSelected)
                                    <div class="sc-selected-customer"><span class="sc-avatar">{{ mb_substr($customerSelected->name, 0, 1) }}</span><div><strong>{{ $customerSelected->name }}</strong><small>{{ $customerSelected->phone }} @if($customerSelected->email) · {{ $customerSelected->email }} @endif</small></div><button type="button" wire:click="$set('selectedCustomerId', '')" aria-label="Changer la cliente"><span class="material-icons">edit</span></button></div>
                                @elseif ($customers->isNotEmpty())
                                    <div class="sc-customer-results">@foreach($customers as $customer)<button type="button" wire:key="sc-customer-{{ $customer->id }}" wire:click="chooseCustomer({{ $customer->id }})"><span class="sc-avatar">{{ mb_substr($customer->name, 0, 1) }}</span><span><strong>{{ $customer->name }}</strong><small>{{ $customer->phone }} @if($customer->email) · {{ $customer->email }} @endif</small></span><span class="material-icons">arrow_forward</span></button>@endforeach</div>
                                    <button type="button" class="sc-inline-action" wire:click="startNewCustomer">La cliente n’est pas dans la liste ? Créer sa fiche</button>
                                @elseif(strlen(trim($customerSearch)) >= 2 && !$createCustomer)
                                    <div class="sc-no-customer">Aucune fiche trouvée pour cette recherche.</div><button type="button" class="sc-inline-action" wire:click="startNewCustomer">Créer une nouvelle fiche cliente</button>
                                @elseif(!$createCustomer)
                                    <small class="sc-help">Saisissez au moins deux caractères pour rechercher parmi les clientes.</small>
                                @endif
                                @if($createCustomer)
                                    <div class="sc-new-customer-fields"><div class="sc-grid-2"><div><label class="sc-label">Nom et prénom <b>*</b></label><input class="form-control" wire:model.defer="newCustomerName">@error('newCustomerName')<small class="text-danger">{{ $message }}</small>@enderror</div><div><label class="sc-label">Téléphone <b>*</b></label><input class="form-control" wire:model.defer="newCustomerPhone" placeholder="Indicatif inclus si connu">@error('newCustomerPhone')<small class="text-danger">{{ $message }}</small>@enderror</div><div><label class="sc-label">E-mail</label><input class="form-control" type="email" wire:model.defer="newCustomerEmail"></div><div><label class="sc-label">Adresse</label><input class="form-control" wire:model.defer="newCustomerAddress"></div></div><div class="sc-inline-notice"><span class="material-icons">info</span>Une recherche de numéro similaire sera faite avant la création pour réutiliser une fiche déjà enregistrée.</div></div>
                                @endif
                                @if($customerSelected)
                                    <div class="sc-purchase-fields">
                                        <div class="sc-grid-2"><div><label class="sc-label">Commande concernée</label><select class="form-control" wire:model="newOrderId"><option value="">Aucune commande liée</option>@foreach($customerOrders as $order)<option value="{{ $order->id }}">{{ $order->code }} — {{ date('d/m/Y', strtotime($order->date_order ?: $order->created_at)) }} — {{ number_format((float)$order->total, 0, ',', ' ') }} FCFA</option>@endforeach</select></div><div><label class="sc-label">Date d’achat</label><input class="form-control" type="date" wire:model="newPurchaseDate"></div></div>
                                        <div class="sc-label mt-3">Produits concernés <small>(tous les articles de la commande sont cochés par défaut)</small></div>
                                        <div class="sc-product-options">
                                            @forelse($caseProducts as $productOption)
                                                <label class="sc-product-option" wire:key="case-product-{{ $productOption['id'] }}"><input type="checkbox" wire:model.defer="newProductIds" value="{{ $productOption['id'] }}"><span>{{ $productOption['name'] }}</span>@if($productOption['quantity'] !== null)<small>× {{ $productOption['quantity'] }}</small>@endif</label>
                                            @empty
                                                <span class="sc-help">Aucun produit disponible pour cette cliente.</span>
                                            @endforelse
                                        </div>
                                        @error('newProductIds')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                                    </div>
                                @endif
                            </div>
                            <div class="sc-form-section"><div class="sc-section-title"><span>02</span><div><strong>Demande et priorité</strong><small>Décrivez le problème ou le besoin avec des faits utiles.</small></div></div>
                                <div class="sc-grid-2"><div><label class="sc-label">Nature du dossier <b>*</b></label><select class="form-control" wire:model="newCaseType"><option value="complaint">Réclamation</option><option value="dissatisfied">Cliente insatisfaite</option><option value="personalized_support">Accompagnement personnalisé</option><option value="information">Demande d’information avec suivi</option></select></div><div><label class="sc-label">Priorité <b>*</b></label><select class="form-control" wire:model="newPriority"><option value="low">Faible</option><option value="normal">Normale</option><option value="high">Élevée</option><option value="urgent">Urgente</option></select></div></div>
                                <label class="sc-label mt-3">Description <b>*</b></label><textarea class="form-control sc-textarea" wire:model.defer="newDescription" rows="4" placeholder="Expliquez le problème, le besoin exprimé et les faits connus…"></textarea>@error('newDescription')<small class="text-danger">{{ $message }}</small>@enderror
                                <div class="sc-grid-2 mt-3"><div><label class="sc-label">Responsable</label>
                                    @if($canAssignCases)
                                        @if($newAssigneeId)
                                            <div class="sc-selected-customer sc-selected-assignee"><span class="sc-avatar">{{ mb_substr($newAssigneeSearch, 0, 1) }}</span><div><strong>{{ $newAssigneeSearch }}</strong><small>Responsable sélectionné</small></div><button type="button" wire:click="changeNewAssignee" aria-label="Changer le responsable"><span class="material-icons">edit</span></button></div>
                                        @else
                                            <div class="sc-customer-search"><input class="form-control" type="search" wire:model.debounce.250ms="newAssigneeSearch" placeholder="Saisir le nom du responsable"><span class="material-icons">search</span></div>
                                            @if($newAssigneeSuggestions->isNotEmpty())<div class="sc-customer-results sc-assignee-results">@foreach($newAssigneeSuggestions as $person)<button type="button" wire:key="new-assignee-{{ $person->id }}" wire:click="selectNewAssignee({{ $person->id }})"><span class="sc-avatar">{{ mb_substr($person->name, 0, 1) }}</span><span><strong>{{ $person->name }}</strong><small>{{ ['CALLCENTER' => 'SAV', 'MNG' => 'Gestionnaire', 'SCR' => 'Secrétaire', 'ADMINUSER' => 'Administrateur'][$person->user_type] ?? $person->user_type }}</small></span><span class="material-icons">arrow_forward</span></button>@endforeach</div>
                                            @elseif(strlen(trim($newAssigneeSearch)) >= 2)<small class="sc-help">Aucun responsable trouvé.</small>
                                            @else<small class="sc-help">Le dossier sera attribué à votre compte si vous ne choisissez personne.</small>@endif
                                        @endif
                                    @else
                                        <input class="form-control" value="{{ Auth::user()->name }}" disabled><small class="sc-help">Le dossier vous sera attribué.</small>
                                    @endif
                                    @error('newAssigneeId')<small class="text-danger">{{ $message }}</small>@enderror
                                </div><div><label class="sc-label">Prochain suivi</label><input class="form-control" type="datetime-local" wire:model.defer="newNextFollowUpAt"></div></div>
                                <div class="mt-3"><label class="sc-label">Photos ou documents <small>(JPG, PNG, WebP, PDF — 10 Mo max par fichier, 5 fichiers)</small></label><input type="file" class="form-control-file sc-file-input" wire:model="uploads" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"><div wire:loading wire:target="uploads" class="sc-uploading">Téléversement en cours…</div>@error('uploads.*')<small class="text-danger d-block">{{ $message }}</small>@enderror</div>
                            </div>
                            @if($newCaseType === 'personalized_support')
                                <div class="sc-form-section sc-support-form"><div class="sc-section-title"><span>03</span><div><strong>Programme d’accompagnement</strong><small>Définissez le besoin, l’objectif et les échéances à suivre.</small></div></div>
                                    <label class="sc-label">Besoin de la cliente <b>*</b></label><textarea class="form-control" rows="3" wire:model.defer="supportNeed" placeholder="Quel accompagnement la cliente attend-elle ?"></textarea>@error('supportNeed')<small class="text-danger">{{ $message }}</small>@enderror
                                    <div class="sc-grid-2 mt-3"><div><label class="sc-label">Objectifs</label><textarea class="form-control" rows="2" wire:model.defer="supportObjectives" placeholder="Résultat souhaité"></textarea></div><div><label class="sc-label">Date de début <b>*</b></label><input class="form-control" type="date" wire:model.defer="supportStartDate"></div></div>
                                    <label class="sc-label mt-3">Échéances proposées</label><div class="sc-milestone-choices">@foreach([3,7,15,30] as $day)<label><input type="checkbox" wire:model.defer="milestoneDays" value="{{ $day }}"><span>J+{{ $day }}</span></label>@endforeach</div>
                                </div>
                            @endif
                            <div class="sc-security-note"><span class="material-icons">history</span><span>La création, les changements de statut, les échanges et les pièces jointes seront conservés dans l’historique du dossier.</span></div>
                        </div>
                        <footer class="sc-modal-foot"><button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button><button type="submit" class="btn sc-primary-btn" wire:loading.attr="disabled" wire:target="createCase"><span class="material-icons">folder_shared</span>Créer le dossier</button></footer>
                    </form>
                </section>
            </div>
        @endif
    @else
        <section class="sc-detail-top">
            <div><a class="sc-back-link" href="{{ route('service-cases.index', $case->archived_at ? ['archiveView' => 1] : []) }}"><span class="material-icons">arrow_back</span>{{ $case->archived_at ? 'Retour aux archives' : 'Retour aux dossiers' }}</a><div class="sc-detail-heading"><span class="sc-case-number">{{ $case->case_number }}</span><span class="sc-status {{ $statusClasses[$case->status] ?? '' }}">{{ $statusLabels[$case->status] ?? $case->status }}</span><span class="sc-priority {{ $priorityClasses[$case->priority] ?? '' }}">{{ $priorityLabels[$case->priority] ?? $case->priority }}</span>@if($case->archived_at)<span class="sc-case-archived-label"><span class="material-icons">archive</span>Archivé le {{ $case->archived_at->format('d/m/Y') }}</span>@endif</div><h1>{{ $case->customer->name }}</h1><p>{{ $typeLabels[$case->case_type] ?? $case->case_type }} <span>·</span> créé le {{ $case->created_at->format('d/m/Y à H:i') }}</p></div>
            <div class="sc-detail-actions">
                @if(!$case->archived_at)
                    @if($canManageCases && $case->status !== 'closed')<button type="button" class="btn sc-secondary-btn" wire:click="openAssignForm"><span class="material-icons">person_add_alt</span>Attribuer</button><button type="button" class="btn sc-secondary-btn" wire:click="openPriorityForm"><span class="material-icons">low_priority</span>Priorité</button>@endif
                    @if($case->status !== 'closed')<button type="button" class="btn sc-primary-btn" wire:click="openActivityModal"><span class="material-icons">add_comment</span>Ajouter une intervention</button>@endif
                    @if($canManageCases && $case->status === 'closed')<button type="button" class="btn sc-secondary-btn" wire:click="openArchiveConfirmation({{ $case->id }})"><span class="material-icons">archive</span>Archiver</button>@endif
                @elseif($isServiceAdmin)
                    <button type="button" class="btn sc-secondary-btn" wire:click="openRestoreConfirmation({{ $case->id }})"><span class="material-icons">unarchive</span>Restaurer</button>
                @endif
            </div>
        </section>

        <div class="sc-detail-layout">
            <main class="sc-detail-main">
                <section class="sc-detail-card"><div class="sc-card-heading"><div><span class="sc-eyebrow">SUIVI DU DOSSIER</span><h2>Prochaine étape</h2></div>@if($case->next_follow_up_at && $case->status !== 'closed')<span class="sc-next-date {{ $case->next_follow_up_at->isPast() && !in_array($case->status,['resolved','closed'],true) ? 'is-overdue' : '' }}"><span class="material-icons">event</span>{{ $case->next_follow_up_at->format('d/m/Y à H:i') }}</span>@endif</div>
                    @if(!$case->archived_at && !in_array($case->status, ['resolved','closed'], true))
                        <div class="sc-workflow-actions">
                            <button wire:click="openActivityModal" type="button"><span class="material-icons">forum</span><strong>Enregistrer un échange</strong><small>WhatsApp, appel, SMS, e-mail…</small></button>
                            <button wire:click="openResolveModal" type="button"><span class="material-icons">published_with_changes</span><strong>Mettre à jour le statut</strong><small>Tracer la prochaine étape</small></button>
                            @php
                                $followupDefault = optional($case->next_follow_up_at)->format('Y-m-d\TH:i') ?: now()->addDay()->format('Y-m-d\TH:i');
                            @endphp
                            <button type="button"
                                data-followup-default="{{ $followupDefault }}"
                                onclick="(function(btn){var form=document.getElementById('sc-followup-form');if(!form)return;form.classList.toggle('d-none');if(!form.classList.contains('d-none')){var inp=form.querySelector('input[type=datetime-local]');if(inp&&!inp.value)inp.value=btn.dataset.followupDefault;}})(this)"><span class="material-icons">calendar_month</span><strong>Programmer un suivi</strong><small>Définir une échéance claire</small></button>
                        </div>
                        <form id="sc-followup-form" class="sc-inline-form d-none" wire:submit.prevent="saveFollowUp"><div><label class="sc-label">Prochaine date et heure</label><input class="form-control" type="datetime-local" wire:model.defer="nextFollowUpAt">@error('nextFollowUpAt')<small class="text-danger">{{ $message }}</small>@enderror</div><div><label class="sc-label">Note pour l’équipe <small>(facultatif)</small></label><input class="form-control" wire:model.defer="statusComment" placeholder="Ex. rappeler après utilisation du produit"></div><button class="btn sc-primary-btn" type="submit">Enregistrer</button></form>
                        @if($case->status === 'new')<div class="sc-status-shortcut"><button class="btn sc-soft-btn" wire:click="startAnalysis">Commencer l’analyse</button></div>@endif
                    @else
                        <div class="sc-resolution-banner"><span class="material-icons">verified</span><div><strong>{{ $case->status === 'closed' ? 'Dossier clôturé' : 'Résolution proposée' }}</strong><p>{{ $case->resolution ?: 'La solution et le résultat du traitement sont conservés ci-dessous.' }}</p></div></div>
                        @if(!$case->archived_at && $case->status === 'resolved' && $canManageCases)<button class="btn sc-primary-btn mt-3" wire:click="openCloseModal"><span class="material-icons">lock</span>Clôturer le dossier</button>@endif
                    @endif
                </section>

                <section class="sc-detail-card"><span class="sc-eyebrow">DESCRIPTION INITIALE</span><p class="sc-description">{{ $case->description }}</p></section>

                @if($case->supportPlan)
                    <section class="sc-detail-card sc-support-plan-card">
                        <div class="sc-card-heading sc-support-plan-heading">
                            <div><span class="sc-eyebrow">PROGRAMME PERSONNALISÉ</span><h2>Étapes d’accompagnement</h2></div>
                            <span class="sc-plan-start"><span class="material-icons">event_available</span><span><small>Début du programme</small><strong>{{ $case->supportPlan->started_at->format('d/m/Y') }}</strong></span></span>
                        </div>
                        <div class="sc-plan-summary">
                            <article><span>Besoin de la cliente</span><p>{{ $case->supportPlan->customer_need }}</p></article>
                            @if($case->supportPlan->objectives)<article><span>Objectifs</span><p>{{ $case->supportPlan->objectives }}</p></article>@endif
                        </div>
                        <div class="sc-milestone-list">
                            @forelse($case->supportPlan->milestones as $milestone)
                                <article class="sc-milestone {{ $milestone->completed_at ? 'is-complete' : ($milestone->due_at->isPast() ? 'is-late' : '') }}">
                                    <span class="sc-milestone-marker">@if($milestone->completed_at)<span class="material-icons">check</span>@else{{ $loop->iteration }}@endif</span>
                                    <div class="sc-milestone-info">
                                        <strong>{{ $milestone->day_offset ? 'Suivi J+'.$milestone->day_offset : 'Suivi personnalisé' }}</strong>
                                        <small><span class="material-icons">schedule</span>{{ $milestone->due_at->format('d/m/Y à H:i') }}</small>
                                        @if($milestone->completed_at)<span class="sc-milestone-state">Réalisé le {{ $milestone->completed_at->format('d/m/Y') }}</span>@elseif($milestone->due_at->isPast())<span class="sc-milestone-state is-late-label">En retard</span>@endif
                                        @if($milestone->observation)<p>{{ $milestone->observation }}</p>@endif
                                    </div>
                                    @if(!$milestone->completed_at && $case->status !== 'closed')
                                        <div class="sc-milestone-actions">
                                            <button class="btn sc-milestone-action" type="button" onclick="document.getElementById('milestone-date-{{ $milestone->id }}')?.classList.toggle('d-none')">Modifier la date</button>
                                            <button class="btn sc-milestone-action" type="button" onclick="document.getElementById('milestone-note-{{ $milestone->id }}')?.classList.toggle('d-none')">Enregistrer l’étape</button>
                                        </div>
                                    @endif
                                </article>
                                @if(!$milestone->completed_at && $case->status !== 'closed')
                                    <form id="milestone-date-{{ $milestone->id }}" class="sc-milestone-form d-none" wire:submit.prevent="updateMilestoneDate({{ $milestone->id }})"><input class="form-control" type="datetime-local" wire:model.defer="milestoneDates.{{ $milestone->id }}" required>@error('milestoneDates.'.$milestone->id)<small class="text-danger">{{ $message }}</small>@enderror<button class="btn sc-primary-btn btn-sm">Enregistrer la date</button></form>
                                    <form id="milestone-note-{{ $milestone->id }}" class="sc-milestone-form d-none" wire:submit.prevent="completeMilestone({{ $milestone->id }})"><input class="form-control" wire:model.defer="milestoneObservation" placeholder="Observation de cette étape" required><button class="btn sc-primary-btn btn-sm">Valider l’étape</button></form>
                                @endif
                            @empty
                                <div class="sc-empty-compact">Aucune échéance programmée.</div>
                            @endforelse
                        </div>
                        @if($canManageCases)<div class="sc-support-review"><label class="sc-label">Bilan final de l’accompagnement</label><textarea class="form-control" rows="3" wire:model.defer="supportObjectives" placeholder="Synthèse des résultats et recommandations…"></textarea><button class="btn sc-secondary-btn mt-2" wire:click="saveSupportReview">Enregistrer le bilan</button></div>@endif
                    </section>
                @endif

                @if(in_array($case->status, ['resolved', 'closed'], true))
                    <section class="sc-detail-card"><div class="sc-card-heading"><div><span class="sc-eyebrow">RETOUR APRÈS TRAITEMENT</span><h2>Satisfaction de la cliente</h2></div>@if($case->customer_satisfaction)<span class="sc-satisfaction-score">{{ $case->customer_satisfaction }} / 5</span>@endif</div>@if($case->customer_satisfaction)<p class="sc-result-text">{{ $case->satisfaction_comment ?: 'Aucun commentaire complémentaire.' }}</p>@else<form wire:submit.prevent="saveSatisfaction"><label class="sc-label">Note communiquée par la cliente <b>*</b></label><div class="sc-rating-options">@foreach([1=>'Très insatisfaite',2=>'Insatisfaite',3=>'Mitigée',4=>'Satisfaite',5=>'Très satisfaite'] as $score=>$label)<label><input type="radio" wire:model="satisfactionRating" value="{{ $score }}"><span><strong>{{ $score }}/5</strong><small>{{ $label }}</small></span></label>@endforeach</div>@error('satisfactionRating')<small class="text-danger d-block">{{ $message }}</small>@enderror<label class="sc-label mt-3">Commentaire de la cliente <small>(facultatif)</small></label><textarea class="form-control" rows="2" wire:model.defer="satisfactionComment" placeholder="Résumé de son retour"></textarea>@error('satisfactionComment')<small class="text-danger">{{ $message }}</small>@enderror<button class="btn sc-secondary-btn mt-3" type="submit">Enregistrer son retour</button></form>@endif</section>
                @endif

                @if($case->resolution_result)
                    <section class="sc-detail-card"><span class="sc-eyebrow">RÉSULTAT DU TRAITEMENT</span><p class="sc-result-text">{{ $case->resolution_result }}</p>@if($case->closure_reason)<div class="sc-closure-reason"><strong>Justification de clôture</strong><p>{{ $case->closure_reason }}</p></div>@endif</section>
                @endif

            </main>

            <aside class="sc-detail-aside">
                <section class="sc-detail-card sc-client-card"><span class="sc-eyebrow">FICHE CLIENTE</span><div class="sc-client-identity"><span class="sc-avatar sc-avatar-large">{{ mb_substr($case->customer->name, 0, 1) }}</span><div><h2>{{ $case->customer->name }}</h2><small>Cliente TENACOS</small></div></div><dl><div><dt>Téléphone</dt><dd><a href="tel:{{ $case->customer->phone }}">{{ $case->customer->phone }}</a></dd></div><div><dt>E-mail</dt><dd>{{ $case->customer->email ?: 'Non renseigné' }}</dd></div><div><dt>Adresse</dt><dd>{{ $case->customer->adresse ?: 'Non renseignée' }}</dd></div></dl><a href="{{ route('view-costumers', $case->customer->id) }}" class="sc-client-link">Ouvrir la fiche cliente<span class="material-icons">open_in_new</span></a></section>
                <section class="sc-detail-card"><span class="sc-eyebrow">DOSSIER</span><dl class="sc-case-facts"><div><dt>Responsable</dt><dd>{{ $case->assignee?->name ?: 'À attribuer' }}</dd></div><div><dt>Créé par</dt><dd>{{ $case->creator?->name ?: 'Compte supprimé' }}</dd></div><div><dt>Produits concernés</dt><dd>{{ $case->products->pluck('name')->filter()->join(', ') ?: ($case->product?->name ?: 'Non précisé') }}</dd></div><div><dt>Date d’achat</dt><dd>{{ $case->purchase_date?->format('d/m/Y') ?: 'Non renseignée' }}</dd></div><div><dt>Commande</dt><dd>@if($case->order)<a href="{{ route('ordershow', $case->order->id) }}">{{ $case->order->code }}</a>@else Non liée @endif</dd></div><div><dt>Priorité</dt><dd><span class="sc-priority {{ $priorityClasses[$case->priority] ?? '' }}">{{ $priorityLabels[$case->priority] ?? $case->priority }}</span></dd></div></dl></section>
                @if($case->attachments->isNotEmpty())<section class="sc-detail-card"><span class="sc-eyebrow">PIÈCES JOINTES</span><div class="sc-attachments sc-attachments-aside">@foreach($case->attachments as $attachment)<a href="{{ route('service-cases.attachments.download', $attachment->id) }}"><span class="material-icons">{{ str_contains($attachment->mime_type ?: '', 'pdf') ? 'picture_as_pdf' : 'image' }}</span><span>{{ $attachment->original_name }}<small>{{ number_format($attachment->size / 1024, 0) }} Ko · Télécharger</small></span></a>@endforeach</div></section>@endif
            </aside>
        </div>

        <section class="sc-detail-card sc-history-card">
            <div class="sc-card-heading">
                <div><span class="sc-eyebrow">JOURNAL NON EFFAÇABLE</span><h2>Historique des interventions</h2></div>
                <span class="sc-history-count">{{ $activities->total() }} entrée(s)</span>
            </div>
            <div class="sc-timeline">
                @forelse($activities as $activity)
                    <article class="sc-timeline-item">
                        <span class="sc-timeline-icon {{ $activity->activity_type === 'status_change' ? 'is-status' : ($activity->activity_type === 'communication' ? 'is-communication' : '') }}">
                            <span class="material-icons">{{ ['created' => 'fiber_new', 'status_change' => 'sync_alt', 'priority_change' => 'low_priority', 'communication' => 'forum', 'internal_note' => 'sticky_note_2', 'follow_up_scheduled' => 'event', 'assignment' => 'person_add_alt', 'milestone' => 'flag', 'satisfaction' => 'sentiment_satisfied_alt', 'support_review' => 'fact_check', 'archive' => 'inventory_2', 'archive_access' => 'admin_panel_settings'][$activity->activity_type] ?? 'history' }}</span>
                        </span>
                        <div class="sc-timeline-content">
                            <div class="sc-timeline-head"><strong>{{ $activity->actor_name ?: ($activity->user?->name ?: 'Système') }}</strong><span>{{ $activity->occurred_at->format('d/m/Y à H:i') }}</span></div>
                            <div class="sc-activity-kind">{{ $activity->activity_type === 'communication' ? 'Échange client · '.(['whatsapp'=>'WhatsApp','call'=>'Appel téléphonique','sms'=>'SMS','email'=>'E-mail','visit'=>'Visite physique','other'=>'Autre'][$activity->channel] ?? $activity->channel) : (['created'=>'Création du dossier','status_change'=>'Changement de statut','priority_change'=>'Changement de priorité','internal_note'=>'Note interne','follow_up_scheduled'=>'Programmation du suivi','assignment'=>'Affectation','milestone'=>'Étape d’accompagnement','support_review'=>'Bilan d’accompagnement','satisfaction'=>'Satisfaction après traitement','archive'=>'Archivage / restauration','archive_access'=>'Demande d’accès aux archives'][$activity->activity_type] ?? 'Intervention') }}</div>
                            @if($activity->old_status && $activity->new_status)
                                <div class="sc-status-transition"><span>{{ $statusLabels[$activity->old_status] ?? $activity->old_status }}</span><span class="material-icons">arrow_forward</span><strong>{{ $statusLabels[$activity->new_status] ?? $activity->new_status }}</strong></div>
                            @endif
                            @if($activity->body)<p>{{ $activity->body }}</p>@endif
                            @if($activity->attachments->isNotEmpty())
                                <div class="sc-attachments">@foreach($activity->attachments as $attachment)<a href="{{ route('service-cases.attachments.download', $attachment->id) }}"><span class="material-icons">{{ str_contains($attachment->mime_type ?: '', 'pdf') ? 'picture_as_pdf' : 'image' }}</span>{{ $attachment->original_name }}</a>@endforeach</div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="sc-empty-compact">Aucune intervention n’a encore été enregistrée.</div>
                @endforelse
            </div>
            @if($activities->total() > 0)
                <div class="sc-history-footer">
                    <small>Affichage de {{ $activities->firstItem() }} à {{ $activities->lastItem() }} sur {{ $activities->total() }} interventions</small>
                    @if($activities->hasPages())<div class="sc-history-pagination">{{ $activities->links() }}</div>@endif
                </div>
            @endif
        </section>

        @if($showActivityForm)
            <div class="sc-modal-backdrop" wire:click.self="closeAllModals">
                <section class="sc-modal sc-modal-narrow" role="dialog" aria-modal="true">
                    <header class="sc-modal-head"><div><span class="sc-eyebrow">DOSSIER {{ $case->case_number }}</span><h2>Ajouter une intervention</h2></div><button type="button" class="sc-icon-button" wire:click="closeAllModals"><span class="material-icons">close</span></button></header>
                    <form wire:submit.prevent="saveActivity">
                        <div class="sc-modal-body">
                            <div class="sc-activity-toggle">
                                <label><input type="radio" wire:model="activityType" value="internal_note"><span><span class="material-icons">sticky_note_2</span>Note interne</span></label>
                                <label><input type="radio" wire:model="activityType" value="communication"><span><span class="material-icons">forum</span>Échange client</span></label>
                            </div>
                            @if($activityType === 'communication')
                                <label class="sc-label mt-3">Canal de communication <b>*</b></label>
                                <select class="form-control" wire:model="activityChannel"><option value="">Choisir un canal</option><option value="whatsapp">WhatsApp</option><option value="call">Appel téléphonique</option><option value="sms">SMS</option><option value="email">E-mail</option><option value="visit">Visite physique</option><option value="other">Autre</option></select>
                                @error('activityChannel')<small class="text-danger">{{ $message }}</small>@enderror
                                @if(!empty($caseContactLinks[$activityChannel] ?? null))
                                    <div class="sc-contact-launch">
                                        <span>Contacter {{ $case->customer->name }}</span>
                                        <a class="btn sc-contact-launch-button" href="{{ $caseContactLinks[$activityChannel] }}" @if($activityChannel === 'whatsapp') target="_blank" rel="noopener noreferrer" @endif>
                                            <span class="material-icons">{{ ['whatsapp' => 'forum', 'call' => 'call', 'sms' => 'sms', 'email' => 'mail'][$activityChannel] ?? 'open_in_new' }}</span>
                                            {{ ['whatsapp' => 'Ouvrir WhatsApp', 'call' => 'Appeler la cliente', 'sms' => 'Ouvrir les SMS', 'email' => 'Envoyer un e-mail'][$activityChannel] ?? 'Contacter' }}
                                        </a>
                                    </div>
                                @elseif(in_array($activityChannel, ['whatsapp', 'call', 'sms'], true))
                                    <small class="sc-contact-unavailable">Aucun numéro de téléphone n’est renseigné pour cette cliente.</small>
                                @elseif($activityChannel === 'email')
                                    <small class="sc-contact-unavailable">Aucune adresse e-mail n’est renseignée pour cette cliente.</small>
                                @endif
                            @endif
                            <label class="sc-label mt-3">{{ $activityType === 'communication' ? 'Résumé de l’échange' : 'Observation pour l’équipe' }} <b>*</b></label>
                            <textarea class="form-control" rows="5" wire:model.defer="activityBody" placeholder="Décrivez les points abordés, la réponse ou la prochaine action…"></textarea>
                            @error('activityBody')<small class="text-danger">{{ $message }}</small>@enderror
                            <label class="sc-label mt-3">Joindre des documents <small>(5 fichiers max)</small></label>
                            <input type="file" class="form-control-file sc-file-input" wire:model="uploads" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                            @error('uploads.*')<small class="text-danger d-block">{{ $message }}</small>@enderror
                        </div>
                        <footer class="sc-modal-foot"><button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button><button class="btn sc-primary-btn" type="submit" wire:loading.attr="disabled" wire:target="saveActivity"><span class="material-icons">save</span>Enregistrer l’intervention</button></footer>
                    </form>
                </section>
            </div>
        @endif

        @if($showResolveForm)
            <div class="sc-modal-backdrop" wire:click.self="closeAllModals">
                <section class="sc-modal sc-modal-narrow" role="dialog" aria-modal="true">
                    <header class="sc-modal-head">
                        <div><span class="sc-eyebrow">MISE À JOUR</span><h2>{{ $newStatus === 'closed' ? 'Clôturer le dossier' : ($newStatus === 'resolved' ? 'Proposer une résolution' : 'Faire avancer le dossier') }}</h2></div>
                        <button type="button" class="sc-icon-button" wire:click="closeAllModals"><span class="material-icons">close</span></button>
                    </header>
                    <div class="sc-modal-body">
                        <label class="sc-label">Nouveau statut <b>*</b></label>
                        <select class="form-control" wire:model="newStatus">
                            <option value="">Choisir le statut</option>
                            @foreach($availableTransitions as $transition)<option value="{{ $transition }}">{{ $statusLabels[$transition] ?? $transition }}</option>@endforeach
                        </select>
                        @error('newStatus')<small class="text-danger">{{ $message }}</small>@enderror

                        <label class="sc-label mt-3">Commentaire {{ $newStatus === 'closed' ? '(facultatif)' : 'obligatoire *' }}</label>
                        <textarea class="form-control" rows="3" wire:model.defer="statusComment" placeholder="Pourquoi le dossier passe-t-il à ce statut ?"></textarea>
                        @error('statusComment')<small class="text-danger">{{ $message }}</small>@enderror
                        @if(!empty($responseSuggestions['statusComment']))
                            <div class="sc-response-suggestions"><small>Réutiliser une réponse</small>@foreach($responseSuggestions['statusComment'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('statusComment', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>
                        @endif

                        @if($newStatus === 'resolved')
                            <div class="sc-resolve-fields">
                                <label class="sc-label mt-3">Solution apportée <b>*</b></label>
                                <textarea class="form-control" rows="3" wire:model.defer="resolution" placeholder="Ce qui a été proposé ou réalisé"></textarea>
                                @error('resolution')<small class="text-danger">{{ $message }}</small>@enderror
                                @if(!empty($responseSuggestions['resolution']))<div class="sc-response-suggestions"><small>Solutions déjà utilisées</small>@foreach($responseSuggestions['resolution'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('resolution', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>@endif
                                <label class="sc-label mt-3">Résultat obtenu <b>*</b></label>
                                <textarea class="form-control" rows="3" wire:model.defer="resolutionResult" placeholder="Retour de la cliente et résultat concret"></textarea>
                                @error('resolutionResult')<small class="text-danger">{{ $message }}</small>@enderror
                                @if(!empty($responseSuggestions['resolutionResult']))<div class="sc-response-suggestions"><small>Résultats déjà utilisés</small>@foreach($responseSuggestions['resolutionResult'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('resolutionResult', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>@endif
                                <div class="sc-inline-notice"><span class="material-icons">verified_user</span>La validation de la résolution est réservée à un responsable.</div>
                            </div>
                        @elseif($newStatus === 'closed')
                            <label class="sc-label mt-3">Résultat du traitement <small>(facultatif)</small></label>
                            <textarea class="form-control" rows="3" wire:model.defer="resolutionResult"></textarea>
                            @error('resolutionResult')<small class="text-danger">{{ $message }}</small>@enderror
                            @if(!empty($responseSuggestions['resolutionResult']))<div class="sc-response-suggestions"><small>Résultats déjà utilisés</small>@foreach($responseSuggestions['resolutionResult'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('resolutionResult', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>@endif
                            <label class="sc-label mt-3">Justification de clôture <small>(facultatif)</small></label>
                            <textarea class="form-control" rows="3" wire:model.defer="closureReason" placeholder="Tu peux préciser le motif de clôture"></textarea>
                            @error('closureReason')<small class="text-danger">{{ $message }}</small>@enderror
                            @if(!empty($responseSuggestions['closureReason']))<div class="sc-response-suggestions"><small>Justifications déjà utilisées</small>@foreach($responseSuggestions['closureReason'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('closureReason', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>@endif
                        @endif
                    </div>
                    <footer class="sc-modal-foot">
                        <button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button>
                        <button type="button" class="btn sc-primary-btn" wire:click="changeStatus('{{ $newStatus }}')" wire:loading.attr="disabled"><span class="material-icons">save</span>{{ $newStatus === 'closed' ? 'Confirmer la clôture' : 'Enregistrer la mise à jour' }}</button>
                    </footer>
                </section>
            </div>
        @endif

        @if($showAssignForm)
            <div class="sc-modal-backdrop" wire:click.self="closeAllModals"><section class="sc-modal sc-modal-narrow" role="dialog" aria-modal="true"><header class="sc-modal-head"><div><span class="sc-eyebrow">ORGANISATION</span><h2>Attribuer le dossier</h2></div><button type="button" class="sc-icon-button" wire:click="closeAllModals"><span class="material-icons">close</span></button></header><div class="sc-modal-body"><label class="sc-label">Responsable <b>*</b></label>
                @if($assignedTo)
                    <div class="sc-selected-customer sc-selected-assignee"><span class="sc-avatar">{{ mb_substr($assigneeSearch, 0, 1) }}</span><div><strong>{{ $assigneeSearch }}</strong><small>Responsable sélectionné</small></div><button type="button" wire:click="changeAssignee" aria-label="Changer le responsable"><span class="material-icons">edit</span></button></div>
                @else
                    <div class="sc-customer-search"><input class="form-control" type="search" wire:model.debounce.250ms="assigneeSearch" placeholder="Saisir le nom du responsable"><span class="material-icons">search</span></div>
                    @if($assigneeSuggestions->isNotEmpty())<div class="sc-customer-results sc-assignee-results">@foreach($assigneeSuggestions as $person)<button type="button" wire:key="assign-agent-{{ $person->id }}" wire:click="selectAssignee({{ $person->id }})"><span class="sc-avatar">{{ mb_substr($person->name, 0, 1) }}</span><span><strong>{{ $person->name }}</strong><small>{{ ['CALLCENTER' => 'SAV', 'MNG' => 'Gestionnaire', 'SCR' => 'Secrétaire', 'ADMINUSER' => 'Administrateur'][$person->user_type] ?? $person->user_type }}</small></span><span class="material-icons">arrow_forward</span></button>@endforeach</div>
                    @elseif(strlen(trim($assigneeSearch)) >= 2)<small class="sc-help">Aucun responsable trouvé.</small>
                    @else<small class="sc-help">Saisissez au moins deux caractères pour rechercher un responsable.</small>@endif
                @endif
                @error('assignedTo')<small class="text-danger">{{ $message }}</small>@enderror</div><footer class="sc-modal-foot"><button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button><button type="button" class="btn sc-primary-btn" wire:click="assignCase" wire:loading.attr="disabled">Enregistrer l’affectation</button></footer></section></div>
        @endif

        @if($showPriorityForm)
            <div class="sc-modal-backdrop" wire:click.self="closeAllModals"><section class="sc-modal sc-modal-narrow" role="dialog" aria-modal="true"><header class="sc-modal-head"><div><span class="sc-eyebrow">PILOTAGE DU DOSSIER</span><h2>Modifier la priorité</h2></div><button type="button" class="sc-icon-button" wire:click="closeAllModals"><span class="material-icons">close</span></button></header><div class="sc-modal-body"><label class="sc-label">Nouvelle priorité <b>*</b></label><select class="form-control" wire:model="newPriority"><option value="low">Faible</option><option value="normal">Normale</option><option value="high">Élevée</option><option value="urgent">Urgente</option></select>@error('newPriority')<small class="text-danger">{{ $message }}</small>@enderror<label class="sc-label mt-3">Motif du changement <b>*</b></label><textarea class="form-control" rows="3" wire:model.defer="statusComment" placeholder="Ex. délai court, impact important pour la cliente…"></textarea>@error('statusComment')<small class="text-danger">{{ $message }}</small>@enderror</div><footer class="sc-modal-foot"><button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button><button type="button" class="btn sc-primary-btn" wire:click="updatePriority"><span class="material-icons">save</span>Enregistrer</button></footer></section></div>
        @endif

        @if($showCloseForm)
            <div class="sc-modal-backdrop" wire:click.self="closeAllModals"><section class="sc-modal sc-modal-narrow" role="dialog" aria-modal="true"><header class="sc-modal-head"><div><span class="sc-eyebrow">VALIDATION RESPONSABLE</span><h2>Clôturer le dossier</h2></div><button type="button" class="sc-icon-button" wire:click="closeAllModals"><span class="material-icons">close</span></button></header><div class="sc-modal-body"><label class="sc-label">Résultat du traitement <small>(facultatif)</small></label><textarea class="form-control" rows="3" wire:model.defer="resolutionResult"></textarea>@error('resolutionResult')<small class="text-danger">{{ $message }}</small>@enderror @if(!empty($responseSuggestions['resolutionResult']))<div class="sc-response-suggestions"><small>Résultats déjà utilisés</small>@foreach($responseSuggestions['resolutionResult'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('resolutionResult', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>@endif<label class="sc-label mt-3">Justification de clôture <small>(facultatif)</small></label><textarea class="form-control" rows="4" wire:model.defer="closureReason" placeholder="Ex. cliente satisfaite, solution confirmée le…"></textarea>@error('closureReason')<small class="text-danger">{{ $message }}</small>@enderror @if(!empty($responseSuggestions['closureReason']))<div class="sc-response-suggestions"><small>Justifications déjà utilisées</small>@foreach($responseSuggestions['closureReason'] as $suggestion)<button type="button" wire:click="useResponseSuggestion('closureReason', @js($suggestion))">{{ \Illuminate\Support\Str::limit($suggestion, 90) }}</button>@endforeach</div>@endif</div><footer class="sc-modal-foot"><button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button><button type="button" class="btn sc-primary-btn" wire:click="closeCase"><span class="material-icons">lock</span>Confirmer la clôture</button></footer></section></div>
        @endif

    @endif

    @if($showArchiveConfirm)
            <div class="sc-modal-backdrop" wire:click.self="closeAllModals">
                <section class="sc-modal sc-modal-narrow" role="dialog" aria-modal="true" aria-labelledby="sc-archive-confirm-title">
                    <header class="sc-modal-head">
                        <div><span class="sc-eyebrow">{{ $archiveOperation === 'archive' ? 'CONSERVATION DU DOSSIER' : 'RETOUR AU REGISTRE' }}</span><h2 id="sc-archive-confirm-title">{{ $archiveOperation === 'archive' ? 'Archiver ce dossier ?' : 'Restaurer ce dossier ?' }}</h2></div>
                        <button type="button" class="sc-icon-button" wire:click="closeAllModals" aria-label="Fermer"><span class="material-icons">close</span></button>
                    </header>
                    <div class="sc-modal-body">
                        <div class="sc-archive-confirm-note"><span class="material-icons">{{ $archiveOperation === 'archive' ? 'inventory_2' : 'unarchive' }}</span><p>{{ $archiveOperation === 'archive' ? 'Le dossier clôturé sera déplacé dans l’onglet Archives. Son historique et ses pièces jointes seront conservés.' : 'Le dossier sera retiré des archives et réapparaîtra dans le registre des dossiers clôturés.' }}</p></div>
                    </div>
                    <footer class="sc-modal-foot"><button type="button" class="btn sc-secondary-btn" wire:click="closeAllModals">Annuler</button><button type="button" class="btn sc-primary-btn" wire:click="confirmArchiveAction" wire:loading.attr="disabled" wire:target="confirmArchiveAction"><span class="material-icons">{{ $archiveOperation === 'archive' ? 'archive' : 'unarchive' }}</span>{{ $archiveOperation === 'archive' ? 'Archiver le dossier' : 'Restaurer le dossier' }}</button></footer>
                </section>
            </div>
    @endif


    <script>
    (function () {
        /* ---------- 1. Bloquer le scroll body quand un modal est ouvert ---------- */
        function syncBodyLock() {
            var hasModal = document.querySelector('.sc-modal-backdrop') !== null;
            document.body.classList.toggle('sc-modal-open', hasModal);
        }

        // Observer les ajouts / suppressions d'éléments dans .service-cases
        var scRoot = document.querySelector('.service-cases');
        if (scRoot) {
            var observer = new MutationObserver(syncBodyLock);
            observer.observe(scRoot, { childList: true, subtree: true });
        }

        // Relancer après chaque mise à jour Livewire (cas rérender)
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', function () {
                syncBodyLock();
            });
        });

        /* ---------- 2. Empêcher le scroll en haut pour les boutons toggle (J+3 etc.) ---------- */
        // Les boutons de type "button" dans .sc-workflow-actions utilisent onclick pour
        // toggler des forms. Si Livewire a défini wire:click sur ces boutons, le composant
        // re-render et scrolle en haut. On fixe ça en sauvant et restituant scrollY.
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.sc-workflow-actions button, .sc-milestone-action');
            if (!btn) return;
            // Sauvegarder la position
            var savedY = window.scrollY;
            // Après le re-render Livewire éventuel, on restitue
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    window.scrollTo({ top: savedY, behavior: 'instant' });
                });
            });
        });

        /* ---------- 3. Labels checkbox J+3 : empêcher la remontée au top ---------- */
        // Les <label> contenant un <input type="checkbox"> qui toggle via wire:model
        // peuvent faire remonter la page car Livewire scroll vers l'ancre #.
        document.addEventListener('change', function (e) {
            var input = e.target;
            if (input.closest('.sc-milestone-choices')) {
                var savedY = window.scrollY;
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        window.scrollTo({ top: savedY, behavior: 'instant' });
                    });
                });
            }
        });
    })();
    </script>

</div>
