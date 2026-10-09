<div>
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="text-uppercase text-muted mb-1">Suivi de la clientèle</h4>
            <p class="text-muted mb-0">Contacts, réponses et relances clients.</p>
        </div>
    </div>

    @if (session()->has('messages'))
        <div class="alert alert-success">{{ session('messages') }}</div>
    @endif

    <div class="row">
        @foreach ([
            ['all', 'Total des clients', 'groups'],
            ['not_contacted', 'Clients à contacter', 'person_add'],
            ['contacted', 'En attente de réponse', 'hourglass_empty'],
            ['to_follow_up', 'Relances à effectuer', 'notifications_active'],
            ['review_required', 'Décision requise', 'rule'],
            ['responded', 'Avis reçus', 'rate_review'],
            ['closed', 'Suivi clôturé', 'task_alt'],
            ['do_not_contact', 'Ne plus solliciter', 'do_not_disturb_alt'],
        ] as [$key, $label, $icon])
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <a href="#" wire:click.prevent="$set('statusFilter', '{{ $key }}')" class="text-decoration-none">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="text-uppercase text-muted mb-0 card-title">{{ $label }}</h5>
                                    <span style="font-size: 130%" class="h1 font-weight-bold mb-0">{{ $counts[$key] }}</span>
                                </div>
                                <div class="col-auto col">
                                    <button type="button" class="btn btn-transparent-primary btn-lg btn-circle" tabindex="-1">
                                        <span class="material-icons">{{ $icon }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Pilotage Journalier & Courbe d'Activité --}}
    @php
        $chartDays = $dailyStats['chartDays'] ?? [];
        $maxVal = max(1, ...array_map(fn ($d) => max($d['done'] ?? 0, $d['scheduled'] ?? 0), $chartDays ?: [[]]));
        $scaleMax = max(2, (int) ceil($maxVal * 1.25));

        $chartPoints = collect($chartDays)->values()->map(function ($d, $i) use ($scaleMax) {
            $x = 45 + ($i * 72);
            $done = $d['done'] ?? 0;
            $scheduled = $d['scheduled'] ?? 0;
            $yDone = 145 - (int) round(($done / $scaleMax) * 110);
            $yScheduled = 145 - (int) round(($scheduled / $scaleMax) * 110);

            return [
                'x' => $x,
                'y_done' => $yDone,
                'y_scheduled' => $yScheduled,
                'done' => $done,
                'scheduled' => $scheduled,
                'rate' => $d['rate'] ?? 0,
                'overdue' => $d['overdue'] ?? 0,
                'label' => $d['label'] ?? '',
                'full_date' => $d['full_date'] ?? '',
                'date' => $d['date'] ?? '',
                'is_selected' => $d['is_selected'] ?? false,
                'is_today' => $d['is_today'] ?? false,
            ];
        });

        $firstPoint = $chartPoints->first();
        $lastPoint = $chartPoints->last();
        $firstX = $firstPoint ? $firstPoint['x'] : 45;
        $lastX = $lastPoint ? $lastPoint['x'] : 477;

        $lineDone = $chartPoints->map(fn ($p) => $p['x'].','.$p['y_done'])->implode(' ');
        $areaDone = 'M '.$firstX.' 145 L '.$lineDone.' L '.$lastX.' 145 Z';
        $lineScheduled = $chartPoints->map(fn ($p) => $p['x'].','.$p['y_scheduled'])->implode(' ');

        $feedbackCounts = $counts['feedback'] ?? ['positive' => 0, 'neutral' => 0, 'negative' => 0];
        $feedbackTotal = array_sum($feedbackCounts);
        $sentimentValues = [
            'positive' => (int) ($feedbackCounts['positive'] ?? 0),
            'neutral' => (int) ($feedbackCounts['neutral'] ?? 0),
            'negative' => (int) ($feedbackCounts['negative'] ?? 0),
        ];
        $feedbackMax = max($sentimentValues ?: [0]);
    @endphp

    {{-- Indicateurs du jour sélectionné (Exact Tenace Card Design) --}}
    <div class="row my-3">
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <a href="#" wire:click.prevent="setFollowUpScope('today')" class="text-decoration-none">
                <div class="card shadow {{ $statusFilter === 'to_follow_up' && $followUpScope === 'today' ? 'border border-primary' : '' }}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">À relancer ce jour</h5>
                                <span style="font-size: 130%" class="h1 font-weight-bold mb-0 text-primary">{{ $dailyStats['scheduledCount'] }}</span>
                            </div>
                            <div class="col-auto col">
                                <button type="button" class="btn btn-transparent-primary btn-lg btn-circle" tabindex="-1">
                                    <span class="material-icons">today</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="text-uppercase text-muted mb-0 card-title">Contacts réalisés</h5>
                            <span style="font-size: 130%" class="h1 font-weight-bold mb-0 text-success">{{ $dailyStats['doneCount'] }}</span>
                        </div>
                        <div class="col-auto col">
                            <button type="button" class="btn btn-transparent-primary btn-lg btn-circle" tabindex="-1">
                                <span class="material-icons text-success">check_circle</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <a href="#" wire:click.prevent="setFollowUpScope('overdue')" class="text-decoration-none">
                <div class="card shadow {{ $statusFilter === 'to_follow_up' && $followUpScope === 'overdue' ? 'border border-danger' : '' }}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="text-uppercase text-muted mb-0 card-title">Contacts en retard</h5>
                                <span style="font-size: 130%" class="h1 font-weight-bold mb-0 {{ $dailyStats['overdueCount'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $dailyStats['overdueCount'] }}</span>
                            </div>
                            <div class="col-auto col">
                                <button type="button" class="btn btn-transparent-primary btn-lg btn-circle" tabindex="-1">
                                    <span class="material-icons {{ $dailyStats['overdueCount'] > 0 ? 'text-danger' : '' }}">warning</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="text-uppercase text-muted mb-0 card-title">Avancement du jour</h5>
                            <span style="font-size: 130%" class="h1 font-weight-bold mb-0 text-dark">{{ $dailyStats['progressRate'] }}%</span>
                        </div>
                        <div class="col-auto col">
                            <button type="button" class="btn btn-transparent-primary btn-lg btn-circle" tabindex="-1">
                                <span class="material-icons text-info">trending_up</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Carte Principale : Navigation par date & Courbe d'activité --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center flex-wrap">
                <h5 class="text-uppercase text-muted mb-0 card-title">Activité des relances</h5>
                <span class="ml-2 font-weight-bold text-dark">— {{ $dailyStats['formattedDate'] }}</span>
                @if ($dailyStats['isToday'])
                    <span class="badge badge-success ml-2">Aujourd'hui</span>
                @endif
            </div>
            <div class="d-flex align-items-center">
                <div class="btn-group btn-group-sm mr-2" role="group">
                    <button type="button" class="btn btn-outline-secondary" wire:click="previousDay" title="Jour précédent">
                        <span class="material-icons" style="font-size: 16px; vertical-align: middle;">chevron_left</span>
                    </button>
                    <button type="button" class="btn {{ $dailyStats['isToday'] ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="goToToday">
                        Aujourd'hui
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="nextDay" title="Jour suivant">
                        <span class="material-icons" style="font-size: 16px; vertical-align: middle;">chevron_right</span>
                    </button>
                </div>
                <input type="date" class="form-control form-control-sm" style="width: 140px;" wire:model="selectedDate" title="Choisir une date">
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Courbe des 7 derniers jours --}}
                <div class="col-12 col-lg-8 mb-4 mb-lg-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase text-muted mb-0 font-weight-bold">Courbe des contacts réalisés (7 derniers jours)</h6>
                        <div class="small text-muted">
                            <span class="mr-3"><span style="color: #7e1615;">●</span> Contacts réalisés</span>
                            <span><span style="color: #f0ad4e;">- -</span> Contacts prévus</span>
                        </div>
                    </div>

                    <div style="width: 100%;">
                        <svg viewBox="0 0 520 180" role="img" aria-label="Courbe d'activité" style="display:block;width:100%;height:auto;min-height:170px">
                            {{-- Lignes de grille horizontales discrètes --}}
                            <line x1="35" y1="25" x2="500" y2="25" stroke="#f1f3f5" stroke-dasharray="3 3" />
                            <text x="28" y="29" text-anchor="end" font-size="10" fill="#adb5bd">{{ $scaleMax }}</text>

                            <line x1="35" y1="85" x2="500" y2="85" stroke="#f1f3f5" stroke-dasharray="3 3" />
                            <text x="28" y="89" text-anchor="end" font-size="10" fill="#adb5bd">{{ round($scaleMax / 2) }}</text>

                            <line x1="35" y1="145" x2="500" y2="145" stroke="#e9ecef" stroke-width="1" />
                            <text x="28" y="149" text-anchor="end" font-size="10" fill="#adb5bd">0</text>

                            {{-- Surface douce sous les réalisés --}}
                            <path d="{{ $areaDone }}" fill="rgba(126, 22, 21, 0.06)" />

                            {{-- Courbe des prévus (tiretée jaune/orange) --}}
                            <polyline points="{{ $lineScheduled }}" fill="none" stroke="#f0ad4e" stroke-width="2" stroke-dasharray="4 4" stroke-linecap="round" stroke-linejoin="round" />

                            {{-- Courbe des réalisés (continue Tenace brand #7e1615) --}}
                            <polyline points="{{ $lineDone }}" fill="none" stroke="#7e1615" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                            {{-- Points de chaque jour --}}
                            @foreach ($chartPoints as $point)
                                <g wire:click="selectDate('{{ $point['date'] }}')" style="cursor: pointer;">
                                    {{-- Zone de sélection --}}
                                    <rect x="{{ $point['x'] - 28 }}" y="10" width="56" height="165" rx="4"
                                          fill="{{ $point['is_selected'] ? 'rgba(126, 22, 21, 0.08)' : 'transparent' }}"
                                          stroke="{{ $point['is_selected'] ? '#7e1615' : 'transparent' }}"
                                          stroke-width="{{ $point['is_selected'] ? '1' : '0' }}">
                                        <title>{{ $point['full_date'] }} : {{ $point['done'] }} réalisé(s) / {{ $point['scheduled'] }} prévu(s)</title>
                                    </rect>

                                    {{-- Point prévus --}}
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y_scheduled'] }}" r="3.5" fill="#f0ad4e" stroke="#fff" stroke-width="1.5" />

                                    {{-- Point réalisés --}}
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y_done'] }}" r="{{ $point['is_selected'] ? '6' : '4.5' }}"
                                            fill="#7e1615"
                                            stroke="#fff" stroke-width="{{ $point['is_selected'] ? '2.5' : '1.5' }}" />

                                    {{-- Valeur réalisée au dessus du point --}}
                                    <text x="{{ $point['x'] }}" y="{{ max(18, $point['y_done'] - 8) }}" text-anchor="middle" font-size="11" font-weight="bold" fill="{{ $point['is_selected'] ? '#7e1615' : '#495057' }}">
                                        {{ $point['done'] }}
                                    </text>

                                    {{-- Label du jour sur l'axe X --}}
                                    <text x="{{ $point['x'] }}" y="162" text-anchor="middle" font-size="11"
                                          font-weight="{{ $point['is_selected'] ? 'bold' : 'normal' }}"
                                          fill="{{ $point['is_selected'] ? '#7e1615' : ($point['is_today'] ? '#212529' : '#6c757d') }}">
                                        {{ $point['label'] }}
                                    </text>
                                </g>
                            @endforeach
                        </svg>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">
                        <span><span class="material-icons align-middle mr-1" style="font-size: 14px;">touch_app</span>Cliquez sur un jour de la courbe pour consulter son activité.</span>
                        <span>Date active : <strong class="text-dark">{{ $dailyStats['formattedDate'] }}</strong></span>
                    </div>
                </div>

                {{-- Avis clients / retours --}}
                <div class="col-12 col-lg-4">
                    <h6 class="text-uppercase text-muted mb-3 font-weight-bold">Avis clients ({{ $feedbackTotal }})</h6>
                    @foreach (['positive' => ['Positifs', 'success'], 'neutral' => ['Neutres', 'secondary'], 'negative' => ['Négatifs', 'danger']] as $sentimentKey => [$sentimentLabel, $sentimentColor])
                        @php $sentimentCount = $feedbackCounts[$sentimentKey] ?? 0; @endphp
                        <div class="mb-3" style="cursor: pointer;" wire:click="openFeedbackDetails('{{ $sentimentKey }}')">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="font-weight-bold text-muted">{{ $sentimentLabel }}</span>
                                <span class="badge badge-{{ $sentimentColor }}">{{ $sentimentCount }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $sentimentColor }}" role="progressbar" style="width: {{ $feedbackMax ? round($sentimentCount / $feedbackMax * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                    @if (($feedbackCounts['unclassified'] ?? 0) > 0)
                        <small class="text-muted d-block mt-2">{{ $feedbackCounts['unclassified'] }} avis sans tonalité renseignée.</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="text-uppercase text-muted mb-0 card-title">Paramètres du suivi</h5>
            <div>
                <span class="text-muted mr-2">Relance par défaut : {{ $defaultFollowUpDays }} jours</span>
                <button class="btn btn-sm btn-primary" type="button" wire:click="$toggle('showSettings')">
                    {{ $showSettings ? 'Fermer' : 'Modifier' }}
                </button>
            </div>
        </div>
        @if ($showSettings)
          <div class="card-body">
            <div class="form-row align-items-start">
                <div class="form-group col-12 col-sm-6 col-lg">
                    <label style="min-height: 40px;">Délai avant relance (jours)</label>
                    <input type="number" min="1" max="365" class="form-control" wire:model="defaultFollowUpDays">
                    @error('defaultFollowUpDays') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-lg">
                    <label style="min-height: 40px;">Début des relances automatiques</label>
                    <input type="date" class="form-control" wire:model="followUpStartDate">
                    <small class="form-text text-muted">Les commandes antérieures à cette date ne génèrent pas de relance automatique.</small>
                    @error('followUpStartDate') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-lg">
                    <label style="min-height: 40px;">Indicatif par défaut</label>
                    <input type="text" class="form-control" placeholder="+228" wire:model="defaultCountryCallingCode">
                    @error('defaultCountryCallingCode') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-lg">
                    <label style="min-height: 40px;">Nombre maximal de relances</label>
                    <input type="number" min="1" max="20" class="form-control" wire:model="maxFollowUps">
                    @error('maxFollowUps') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-lg">
                    <label class="d-block" style="min-height: 40px;" aria-hidden="true">&nbsp;</label>
                    <button class="btn btn-primary btn-block" wire:click="saveSettings">Enregistrer les paramètres</button>
                </div>
            </div>

            <hr>
            <h6 class="text-uppercase text-muted">Modèles de contact</h6>
            <p class="small text-muted">Utilise <code>&#123;&#123;name&#125;&#125;</code> pour le nom du client et <code>&#123;&#123;product&#125;&#125;</code> pour le produit de sa dernière commande. La signature « TENACE COSMETIQUE » est ajoutée automatiquement.</p>
            <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label>Canal</label>
                    <select class="form-control" wire:model="newTemplateChannel">
                        <option value="whatsapp">WhatsApp</option>
                        <option value="sms">SMS</option>
                        <option value="call">Trame d’appel</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Nom du modèle</label>
                    <input class="form-control" wire:model="newTemplateName" placeholder="Ex. Demande d’avis">
                    @error('newTemplateName') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-md-12">
                    <label>Texte du modèle</label>
                    <textarea class="form-control" rows="6" style="min-height: 150px; resize: vertical;" wire:model="newTemplateBody" placeholder="Rédige le message avec &#123;&#123;name&#125;&#125; et &#123;&#123;product&#125;&#125;."></textarea>
                    @error('newTemplateBody') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-md-5">
                    <button class="btn btn-primary" wire:click="saveMessageTemplate">{{ $editingTemplateId ? 'Modifier' : 'Ajouter' }}</button>
                    @if ($editingTemplateId)
                        <button class="btn btn-secondary" wire:click="cancelMessageTemplateEdit">Annuler</button>
                    @endif
                </div>
            </div>
            @error('newTemplateChannel') <small class="text-danger d-block">{{ $message }}</small> @enderror
            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover">
                    <thead class="thead-light"><tr><th>Canal</th><th>Modèle</th><th>Texte</th><th>État</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach ($allMessageTemplates as $template)
                            <tr wire:key="template-{{ $template->id }}">
                                <td>{{ ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Appel'][$template->channel] }}</td>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->body }}</td>
                                <td>{{ $template->active ? 'Actif' : 'Inactif' }}</td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-warning" wire:click="editMessageTemplate({{ $template->id }})">Modifier</button>
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="toggleMessageTemplate({{ $template->id }})">{{ $template->active ? 'Désactiver' : 'Activer' }}</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="openDeleteTemplateModal({{ $template->id }})">Supprimer</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
          </div>
        @endif
    </div>

    @if ($showDeleteTemplateModal)
        <div class="modal fade show d-block follow-up-modal" id="delete-template-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered follow-up-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer le modèle</h5>
                        <button type="button" class="close" wire:click="beginClosingModal('delete-template-modal')" aria-label="Fermer"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">Confirme-tu la suppression de ce modèle ?</p>
                        <strong>{{ $deletingTemplateName }}</strong>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="beginClosingModal('delete-template-modal')">Annuler</button>
                        <button type="button" class="btn btn-danger" wire:click="confirmDeleteMessageTemplate">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showScheduledDateModal)
        <div class="modal fade show d-block follow-up-modal" id="scheduled-follow-up-date-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered follow-up-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la prochaine date de contact</h5>
                        <button type="button" class="close" wire:click="beginClosingModal('scheduled-follow-up-date-modal')" aria-label="Fermer"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Avance la date à aujourd’hui ou choisis une autre échéance pour rendre ce contact disponible plus tôt.</p>
                        <label for="scheduled-follow-up-date">Nouvelle date</label>
                        <input id="scheduled-follow-up-date" type="datetime-local" class="form-control" wire:model="scheduledFollowUpDate">
                        @error('scheduledFollowUpDate') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="beginClosingModal('scheduled-follow-up-date-modal')">Annuler</button>
                        <button type="button" class="btn btn-primary" wire:click="saveScheduledDate">Enregistrer la date</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showFeedbackModal)
        @php $feedbackLabel = ['positive' => 'positifs', 'neutral' => 'neutres', 'negative' => 'négatifs'][$feedbackSentiment] ?? 'clients'; @endphp
        <div class="modal fade show d-block follow-up-modal" id="follow-up-feedback-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable follow-up-modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Avis {{ $feedbackLabel }}</h5><button type="button" class="close" wire:click="beginClosingModal('follow-up-feedback-modal')" aria-label="Fermer"><span>&times;</span></button></div>
                <div class="modal-body">
                    @forelse ($feedbacks as $feedback)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between flex-wrap">
                                <strong>{{ $feedback->costumer->name ?? 'Client supprimé' }}</strong>
                                <span class="text-muted">{{ $feedback->responded_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if ($feedback->costumer)
                                <div class="small text-muted">{{ $feedback->costumer->phone ?: 'Téléphone absent' }} · {{ $feedback->costumer->email ?: 'E-mail absent' }}</div>
                            @endif
                            <p class="mb-2 mt-2 {{ $feedbackSentiment === 'negative' ? 'text-danger' : '' }}">{{ $feedback->response ?: 'Aucun commentaire saisi.' }}</p>
                            @if ($feedback->costumer)
                                <a class="btn btn-sm btn-outline-info" href="{{ route('view-costumers', $feedback->costumer->id) }}">Voir la fiche</a>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="openHistory({{ $feedback->costumer->id }})">Voir l’historique</button>
                                <a class="btn btn-sm btn-outline-danger" href="{{ route('service-cases.index', ['customer' => $feedback->costumer->id]) }}">Créer un dossier SAV</a>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun avis dans cette catégorie.</p>
                    @endforelse
                    @if ($feedbacks)
                        <div class="mt-3">{{ $feedbacks->links() }}</div>
                    @endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" wire:click="beginClosingModal('follow-up-feedback-modal')">Fermer</button></div>
            </div></div>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="form-row align-items-end">
                <div class="form-group col-md-5 mb-md-0">
                    <label for="customer-search">Rechercher un client</label>
                    <input id="customer-search" type="search" class="form-control" wire:model="search" placeholder="Nom, téléphone ou e-mail">
                </div>
                <div class="form-group col-md-4 mb-md-0">
                    <label for="customer-status">Statut</label>
                    <select id="customer-status" class="form-control" wire:model="statusFilter">
                        <option value="all">Tous les clients</option>
                        <option value="not_contacted">Non contactés</option>
                        <option value="contacted">Contactés / en attente</option>
                        <option value="to_follow_up">À relancer</option>
                        <option value="review_required">Décision requise</option>
                        <option value="responded">Ayant répondu</option>
                        <option value="closed">Suivi clôturé</option>
                        <option value="do_not_contact">Ne plus solliciter</option>
                    </select>
                </div>
            </div>

            @if ($statusFilter === 'to_follow_up')
                <div class="mt-3 pt-2 border-top d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <span class="small font-weight-bold text-muted mr-1">Périmètre :</span>
                    <button type="button" wire:click="setFollowUpScope('today')" class="btn btn-sm {{ $followUpScope === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <span class="material-icons mr-1" style="font-size: 15px; vertical-align: middle;">today</span>
                        Du jour ({{ $dailyStats['scheduledCount'] }})
                    </button>
                    <button type="button" wire:click="setFollowUpScope('overdue')" class="btn btn-sm {{ $followUpScope === 'overdue' ? 'btn-danger' : 'btn-outline-danger' }}">
                        <span class="material-icons mr-1" style="font-size: 15px; vertical-align: middle;">warning</span>
                        En retard ({{ $dailyStats['overdueCount'] }})
                    </button>
                    <button type="button" wire:click="setFollowUpScope('all')" class="btn btn-sm {{ $followUpScope === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        <span class="material-icons mr-1" style="font-size: 15px; vertical-align: middle;">all_inclusive</span>
                        Toutes les relances ({{ $counts['to_follow_up'] }})
                    </button>
                </div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Client</th>
                        <th>Coordonnées</th>
                        <th>Statut</th>
                        <th>Dernier contact</th>
                        <th>Prochaine relance</th>
                        <th>Dernier moyen</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($costumers as $costumer)
                        @php
                            $status = $statuses[$costumer->id];
                            $latest = $costumer->latestContactHistory;
                            $nextFollowUpAt = $costumer->contactPreference?->next_follow_up_at;
                            if (!$nextFollowUpAt && $latest) {
                                $nextFollowUpAt = $latest->follow_up_at;
                            } elseif (!$nextFollowUpAt && $costumer->latestOrder) {
                                $orderDate = $costumer->latestOrder->date_order ?: $costumer->latestOrder->created_at;
                                $nextFollowUpAt = \Illuminate\Support\Carbon::parse($orderDate)->addDays((int) $defaultFollowUpDays);
                            }
                            $hasPurchase = $costumer->orders_count > 0;
                            $badge = ['not_contacted' => 'success', 'contacted' => 'warning', 'responded' => 'info', 'to_follow_up' => 'warning', 'review_required' => 'danger', 'closed' => 'secondary', 'do_not_contact' => 'dark'][$status];
                            $statusLabel = ['not_contacted' => 'Non contacté', 'contacted' => 'Contacté / en attente', 'responded' => 'Répondu', 'to_follow_up' => 'À relancer', 'review_required' => 'Décision requise', 'closed' => 'Suivi clôturé', 'do_not_contact' => 'Ne plus solliciter'][$status];
                        @endphp
                        <tr wire:key="costumer-{{ $costumer->id }}">
                            <td><strong>{{ $costumer->name }}</strong></td>
                            <td>
                                <div>{{ $costumer->phone ?: 'Téléphone absent' }}</div>
                                <small class="text-muted">{{ $costumer->email ?: 'E-mail absent' }}</small>
                            </td>
                            <td><span class="badge badge-{{ $badge }}">{{ $statusLabel }}</span></td>
                            <td>{{ $latest ? $latest->contacted_at->format('d/m/Y H:i') : '—' }}</td>
                            <td>
                                @if ($status === 'review_required')
                                    <span class="text-danger font-weight-bold">Limite de relances atteinte</span>
                                @elseif ($nextFollowUpAt && $status === 'to_follow_up')
                                    @if ($nextFollowUpAt->isToday())
                                        <span class="text-primary font-weight-bold">
                                            <span class="material-icons align-middle mr-1" style="font-size: 16px;">today</span>Aujourd'hui{{ $nextFollowUpAt->format('H:i') !== '00:00' ? ' à ' . $nextFollowUpAt->format('H:i') : '' }}
                                        </span>
                                    @elseif ($nextFollowUpAt->isPast())
                                        <span class="text-warning font-weight-bold" title="Relance en retard">
                                            <span class="material-icons align-middle mr-1" style="font-size: 15px;">warning</span>Échue : {{ $nextFollowUpAt->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        {{ $nextFollowUpAt->format('d/m/Y H:i') }}
                                    @endif
                                @elseif ($nextFollowUpAt)
                                    @if ($nextFollowUpAt->isToday())
                                        <span class="text-primary font-weight-bold">
                                            <span class="material-icons align-middle mr-1" style="font-size: 16px;">today</span>Aujourd'hui{{ $nextFollowUpAt->format('H:i') !== '00:00' ? ' à ' . $nextFollowUpAt->format('H:i') : '' }}
                                        </span>
                                    @else
                                        {{ $nextFollowUpAt->format('d/m/Y H:i') }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $latest ? ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Appel', 'other' => 'Autre'][$latest->channel] : '—' }}</td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-info" href="{{ route('view-costumers', $costumer->id) }}">Fiche</a>
                                <a class="btn btn-sm btn-outline-danger" href="{{ route('service-cases.index', ['customer' => $costumer->id]) }}">Créer un dossier</a>
                                @if ($status === 'do_not_contact')
                                    <button class="btn btn-sm btn-success" wire:click="allowContactAgain({{ $costumer->id }})">Réactiver</button>
                                @elseif ($status === 'review_required')
                                    <button class="btn btn-sm btn-primary" wire:click="openFollowUpDecisionModal({{ $costumer->id }}, 'continue')">Continuer</button>
                                    <button class="btn btn-sm btn-danger" wire:click="openFollowUpDecisionModal({{ $costumer->id }}, 'stop')">Clôturer</button>
                                @elseif ($status === 'responded')
                                    <span class="badge badge-info">Avis reçu</span>
                                @elseif ($status === 'closed')
                                    <span class="badge badge-secondary">Suivi clôturé</span>
                                @elseif (!$hasPurchase)
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Aucune commande</button>
                                @elseif ($nextFollowUpAt && $nextFollowUpAt->isFuture() && !$nextFollowUpAt->isToday())
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Contact possible à partir de cette date">Contacter le {{ $nextFollowUpAt->format('d/m/Y') }}</button>
                                    @if ($hasPurchase && !in_array($status, ['responded', 'closed', 'do_not_contact', 'review_required']))
                                        <button class="btn btn-sm btn-outline-primary" wire:click="openScheduledDateModal({{ $costumer->id }})">Modifier la date</button>
                                    @endif
                                @else
                                    <button class="btn btn-sm btn-primary" wire:click="openContactModal({{ $costumer->id }}, '{{ $latest ? 'follow_up' : 'initial' }}')">
                                        {{ $latest ? 'Relancer' : 'Contacter' }}
                                    </button>
                                @endif
                                @if ($latest && $status !== 'responded')
                                    <button class="btn btn-sm btn-success" wire:click="openResponseModal({{ $latest->id }})">Enregistrer réponse</button>
                                @endif
                                @if ($status !== 'do_not_contact')
                                    <button class="btn btn-sm btn-outline-danger" wire:click="openDoNotContactModal({{ $costumer->id }})">Ne plus solliciter</button>
                                @endif
                                <button class="btn btn-sm btn-outline-secondary" wire:click="openHistory({{ $costumer->id }})">Historique</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun client ne correspond à ces critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body d-flex justify-content-center" style="max-width:100%; overflow-x:auto;">
            {{ $costumers->onEachSide(1)->links() }}
        </div>
    </div>

    @if ($showContactModal && $selectedCostumer)
        <div class="modal fade show d-block follow-up-modal" id="follow-up-contact-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-scrollable follow-up-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $contactType === 'follow_up' ? 'Enregistrer une relance' : 'Enregistrer un contact' }} — {{ $selectedCostumer->name }}</h5>
                        <button type="button" class="close" wire:click="beginClosingModal('follow-up-contact-modal')" aria-label="Fermer"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Ouvre le moyen choisi, effectue le contact, puis enregistre-le ici dans l’historique.</p>
                        @php
                            $customerPhone = trim((string) $selectedCostumer->phone);
                            $customerPhoneDigits = preg_replace('/\D+/', '', $customerPhone);
                            $callingCodeDigits = ltrim(preg_replace('/\D+/', '', (string) $individualCallingCode), '0');
                            $hasInternationalPrefix = str_starts_with($customerPhone, '+')
                                || str_starts_with($customerPhone, '00')
                                || ($callingCodeDigits !== '' && str_starts_with($customerPhoneDigits, $callingCodeDigits));
                        @endphp
                        <div class="alert alert-light border">
                            <strong>Client :</strong> {{ $selectedCostumer->name }}<br>
                            <strong>Numéro enregistré :</strong> {{ $customerPhone ?: 'Aucun numéro renseigné' }}
                        </div>
                        @if ($hasInternationalPrefix)
                            <div class="alert alert-info">
                                Ce numéro semble déjà contenir un indicatif international. Il sera conservé tel quel : l’indicatif par défaut ne sera pas ajouté une seconde fois.
                            </div>
                        @endif
                        @if ($selectedCostumer->latestOrder)
                            <div class="card border mb-3">
                                <div class="card-header py-2"><strong>Produits de la dernière commande</strong></div>
                                <ul class="list-group list-group-flush">
                                    @forelse ($selectedCostumer->latestOrder->orderItems as $orderItem)
                                        <li class="list-group-item py-2 d-flex justify-content-between">
                                            <span>{{ $orderItem->product->name ?? 'Produit supprimé' }}</span>
                                            <span class="text-muted">× {{ $orderItem->quantity }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">Aucun produit détaillé pour cette commande.</li>
                                    @endforelse
                                </ul>
                            </div>
                        @else
                            <div class="alert alert-warning">Aucune commande n’est enregistrée pour ce client.</div>
                        @endif
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Moyen utilisé</label>
                                <select class="form-control" wire:model="channel">
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="call">Appel direct</option>
                                    <option value="sms">SMS</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Date du contact</label>
                                <input type="datetime-local" class="form-control" wire:model.defer="contactedAt">
                            </div>
                        </div>
                        @if ($channel === 'other')
                            <div class="form-group">
                                <label for="channel-detail">Précise le moyen de contact</label>
                                <input id="channel-detail" type="text" class="form-control" wire:model.defer="channelDetail" maxlength="120" placeholder="Ex. e-mail, Facebook, visite en boutique…">
                                @error('channelDetail') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        @endif
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Modèle de {{ ['whatsapp' => 'message WhatsApp', 'sms' => 'SMS', 'call' => 'trame d’appel', 'other' => 'contact'][$channel] }}</label>
                                <select class="form-control" wire:model="selectedTemplateId">
                                    <option value="">Aucun modèle</option>
                                    @foreach ($messageTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Indicatif de ce client</label>
                                <input type="text" class="form-control" placeholder="{{ $defaultCountryCallingCode }}" wire:model="individualCallingCode">
                                <small class="form-text text-muted">Cette valeur sera mémorisée pour ce client.</small>
                            </div>
                        </div>
                        @if ($channel === 'call' && $selectedMessage)
                            <div class="alert alert-light border"><strong>Trame suggérée</strong><p class="mb-0 mt-2">{{ $selectedMessage }}</p></div>
                        @endif
                        @if ($contactUrl)
                            <a href="{{ $contactUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary mb-3">
                                {{ $channel === 'call' ? 'Appeler' : 'Ouvrir' }} {{ ['whatsapp' => 'WhatsApp', 'sms' => 'les SMS', 'call' => '', 'other' => ''][$channel] }} {{ $selectedCostumer->phone }}
                            </a>
                            @if ($channel === 'whatsapp')
                                <small class="d-block text-muted mb-3">WhatsApp s’ouvrira selon la configuration de votre appareil et de votre navigateur.</small>
                            @endif
                        @elseif ($channel !== 'other')
                            <div class="alert alert-warning">Ce client n’a pas de numéro utilisable pour ce moyen de contact.</div>
                        @endif
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="response-received-now" wire:model.defer="responseReceivedNow">
                            <label class="form-check-label" for="response-received-now">Le client répond maintenant</label>
                        </div>
                        <div id="immediate-response-fields" style="{{ $responseReceivedNow ? '' : 'display: none;' }}">
                            <div class="form-group">
                                <label>Réponse ou avis du client</label>
                                <textarea class="form-control" rows="4" wire:model.defer="immediateResponse" placeholder="Note la réponse du client"></textarea>
                                @error('immediateResponse') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="form-group">
                                <label>Tonalité de l’avis</label>
                                <select id="immediate-sentiment" class="form-control" wire:model.defer="immediateSentiment">
                                    <option value="positive">Positif</option>
                                    <option value="neutral">Neutre</option>
                                    <option value="negative">Négatif</option>
                                </select>
                            </div>
                            @if ($canCreateServiceCase)
                                <div id="quick-service-case-option" class="border rounded p-3 mb-3 bg-light" style="{{ $responseReceivedNow ? '' : 'display: none;' }}">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="create-service-case" wire:model.defer="createServiceCase">
                                        <label class="form-check-label font-weight-bold" for="create-service-case">Continuer vers la création d’un dossier SAV</label>
                                    </div>
                                    <small class="form-text text-muted mt-2">Le contact et la réponse seront d’abord enregistrés. Tu seras ensuite redirigé vers le formulaire complet, avec la cliente, sa dernière commande, le produit choisi et sa réponse préremplis.</small>
                                    @if ($serviceCaseOrders->isNotEmpty())
                                        <div class="form-row mt-3 mb-0">
                                            <div class="form-group col-md-6">
                                                <label for="quick-service-case-order">Commande concernée</label>
                                                <select id="quick-service-case-order" class="form-control" wire:model="quickServiceCaseOrderId">
                                                    @foreach ($serviceCaseOrders as $order)
                                                        <option value="{{ $order->id }}">{{ $order->code ?: '#'.$order->id }} — {{ date('d/m/Y', strtotime($order->date_order ?: $order->created_at)) }} — {{ number_format((float) $order->total, 0, ',', ' ') }} FCFA</option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">Les 20 commandes les plus récentes sont proposées, la dernière en premier.</small>
                                                @error('quickServiceCaseOrderId') <small class="text-danger d-block">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="form-group col-12">
                                                <label>Produits concernés par le dossier SAV</label>
                                                @if ($selectedServiceCaseOrder && $selectedServiceCaseOrder->orderItems->whereNotNull('product_id')->isNotEmpty())
                                                    <div class="quick-case-products">
                                                        @foreach ($selectedServiceCaseOrder->orderItems->whereNotNull('product_id')->unique('product_id') as $orderItem)
                                                            <label class="quick-case-product" wire:key="quick-case-product-{{ $orderItem->product_id }}">
                                                                <input type="checkbox" wire:model.defer="quickServiceCaseProductIds" value="{{ $orderItem->product_id }}">
                                                                <span>{{ $orderItem->product->name ?? 'Produit supprimé' }}</span>
                                                                <small>× {{ $orderItem->quantity }}</small>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <small class="form-text text-muted">Tous les produits de la commande sont sélectionnés. Décoche ceux qui ne sont pas concernés.</small>
                                                @else
                                                    <div class="form-control text-muted">Aucun produit détaillé pour cette commande.</div>
                                                @endif
                                                @error('quickServiceCaseProductIds') <small class="text-danger d-block">{{ $message }}</small> @enderror
                                            </div>
                                        </div>
                                    @elseif ($selectedCostumer->latestOrder)
                                        <small class="form-text text-muted mt-2">La dernière commande ne contient pas de produit détaillé.</small>
                                    @else
                                        <small class="form-text text-muted mt-2">Aucune commande enregistrée pour cette cliente.</small>
                                    @endif
                                </div>
                            @endif
                            <div class="alert alert-info">La réponse sera enregistrée avec ce contact et aucune relance ne sera planifiée.</div>
                        </div>
                        <div id="scheduled-follow-up-fields" style="{{ $responseReceivedNow ? 'display: none;' : '' }}">
                            <div class="form-group">
                                <label>Date de relance prévue (facultatif)</label>
                                <input type="datetime-local" class="form-control" wire:model.defer="followUpAt">
                                <small class="form-text text-muted">Par défaut : {{ $defaultFollowUpDays }} jours après le contact. Tu peux choisir une autre date.</small>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Note sur le contact</label>
                            <textarea class="form-control" rows="3" wire:model.defer="notes" placeholder="Contexte ou résultat du contact"></textarea>
                        </div>
                        @foreach (['channel', 'channelDetail', 'contactedAt', 'followUpAt', 'individualCallingCode', 'selectedTemplateId', 'notes', 'immediateSentiment'] as $field)
                            @error($field) <small class="text-danger d-block">{{ $message }}</small> @enderror
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="beginClosingModal('follow-up-contact-modal')">Annuler</button>
                        <button class="btn btn-primary" wire:click="saveContact">{{ $createServiceCase ? 'Enregistrer et ouvrir le dossier SAV' : 'Enregistrer dans l’historique' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showResponseModal)
        <div class="modal fade show d-block follow-up-modal" id="follow-up-response-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-scrollable follow-up-modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Réponse du client</h5><button type="button" class="close" wire:click="beginClosingModal('follow-up-response-modal')" aria-label="Fermer"><span>&times;</span></button></div>
                <div class="modal-body">
                    <label>Réponse ou avis</label>
                    <textarea id="follow-up-response-text" class="form-control" rows="5" wire:model="response"></textarea>
                    @error('response') <small class="text-danger">{{ $message }}</small> @enderror
                    <label class="mt-3">Tonalité de l’avis</label>
                    <select class="form-control" wire:model="sentiment">
                        <option value="positive">Positif</option>
                        <option value="neutral">Neutre</option>
                        <option value="negative">Négatif</option>
                    </select>
                    @if ($canCreateServiceCase)
                        <div class="border rounded p-3 mt-3 bg-light">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="response-create-service-case" wire:model.defer="createServiceCase">
                                <label class="form-check-label font-weight-bold" for="response-create-service-case">Créer aussi un dossier SAV</label>
                            </div>
                            <div id="response-quick-service-case-fields" class="mt-3" style="{{ $createServiceCase ? '' : 'display: none;' }}">
                                <div class="form-group mb-2">
                                    <label for="response-quick-service-case-type">Type de dossier</label>
                                    <select id="response-quick-service-case-type" class="form-control" wire:model.defer="quickServiceCaseType">
                                        <option value="complaint">Réclamation</option>
                                        <option value="dissatisfied">Cliente insatisfaite</option>
                                        <option value="personalized_support">Accompagnement personnalisé</option>
                                        <option value="information">Demande d’information</option>
                                    </select>
                                    @error('quickServiceCaseType') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                                <div class="form-group mb-2">
                                    <label for="response-quick-service-case-description">Description du dossier</label>
                                    <textarea id="response-quick-service-case-description" class="form-control" rows="3" wire:model.defer="quickServiceCaseDescription" placeholder="La réponse sera proposée comme description du dossier"></textarea>
                                    @error('quickServiceCaseDescription') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                                <small class="text-muted d-block">La réponse ci-dessus sera proposée automatiquement. Tu peux modifier la description avant l’enregistrement.</small>
                                @if ($selectedCostumer && $selectedCostumer->latestOrder)
                                    <small class="text-muted d-block mt-1">Dernière commande : {{ $selectedCostumer->latestOrder->code ?: '#'.$selectedCostumer->latestOrder->id }}</small>
                                    @if($selectedCostumer->latestOrder->orderItems->whereNotNull('product_id')->isNotEmpty())
                                        <label class="d-block mt-2 mb-1">Produits concernés</label>
                                        <div class="quick-case-products">
                                            @foreach($selectedCostumer->latestOrder->orderItems->whereNotNull('product_id')->unique('product_id') as $orderItem)
                                                <label class="quick-case-product" wire:key="response-case-product-{{ $orderItem->product_id }}"><input type="checkbox" wire:model.defer="quickServiceCaseProductIds" value="{{ $orderItem->product_id }}"><span>{{ $orderItem->product->name ?? 'Produit supprimé' }}</span><small>× {{ $orderItem->quantity }}</small></label>
                                            @endforeach
                                        </div>
                                        <small class="form-text text-muted">Tous les produits de la dernière commande sont sélectionnés par défaut.</small>
                                        @error('quickServiceCaseProductIds') <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    @endif
                                @elseif ($selectedCostumer)
                                    <small class="text-muted d-block mt-1">Aucune commande récente : le dossier sera lié à la cliente uniquement.</small>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" wire:click="beginClosingModal('follow-up-response-modal')">Annuler</button><button class="btn btn-success" wire:click="saveResponse">{{ $createServiceCase ? 'Enregistrer la réponse et créer le dossier' : 'Enregistrer la réponse' }}</button></div>
            </div></div>
        </div>
    @endif

    @if ($showHistoryModal && $selectedCostumer)
        <div class="modal fade show d-block follow-up-modal" id="follow-up-history-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-scrollable follow-up-modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Historique — {{ $selectedCostumer->name }}</h5><button type="button" class="close" wire:click="beginClosingModal('follow-up-history-modal')" aria-label="Fermer"><span>&times;</span></button></div>
                <div class="modal-body">
                    @forelse ($history as $item)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between flex-wrap">
                                <strong>{{ $item->contact_type === 'follow_up' ? 'Relance' : 'Contact initial' }} · {{ ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Appel', 'other' => ($item->channel_detail ?: 'Autre')][$item->channel] }}</strong>
                                <span>{{ $item->contacted_at->format('d/m/Y H:i') }} — {{ $item->user->name ?? 'Utilisateur supprimé' }}</span>
                            </div>
                            @if ($item->follow_up_at)<div class="small text-muted">Relance prévue : {{ $item->follow_up_at->format('d/m/Y H:i') }}</div>@endif
                            @if ($item->notes)<p class="mb-1 mt-2">{{ $item->notes }}</p>@endif
                            @if ($item->responded_at)
                                <div class="alert alert-info mb-0 mt-2">
                                    <strong>Réponse du {{ $item->responded_at->format('d/m/Y H:i') }} :</strong>
                                    @if ($item->sentiment)
                                        <span class="badge badge-{{ ['positive' => 'success', 'neutral' => 'secondary', 'negative' => 'danger'][$item->sentiment] }}">{{ ['positive' => 'Positif', 'neutral' => 'Neutre', 'negative' => 'Négatif'][$item->sentiment] }}</span>
                                    @endif
                                    <div>{{ $item->response }}</div>
                                </div>
                            @endif
                            @if ($item->messageTemplate)<div class="small text-muted mt-2">Modèle utilisé : {{ $item->messageTemplate->name }}</div>@endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun contact enregistré.</p>
                    @endforelse
                </div>
            </div></div>
        </div>
    @endif

    @if ($showFollowUpDecisionModal && $selectedCostumer)
        <div class="modal fade show d-block follow-up-modal" id="follow-up-decision-confirm-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-scrollable follow-up-modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">{{ $pendingFollowUpDecision === 'continue' ? 'Confirmer la reprise des relances' : 'Confirmer la clôture du suivi' }}</h5><button type="button" class="close" wire:click="beginClosingModal('follow-up-decision-confirm-modal')" aria-label="Fermer"><span>&times;</span></button></div>
                <div class="modal-body">
                    @if ($pendingFollowUpDecision === 'continue')
                        <p>Veux-tu autoriser une nouvelle série de relances pour <strong>{{ $selectedCostumer->name }}</strong> ?</p>
                        <p class="text-muted mb-0">Le client restera dans le suivi et pourra être relancé à la prochaine échéance.</p>
                    @else
                        <p>Veux-tu arrêter les relances pour <strong>{{ $selectedCostumer->name }}</strong> ?</p>
                        <p class="text-muted mb-0">Le suivi sera marqué comme clôturé. Une nouvelle commande pourra démarrer un nouveau suivi.</p>
                    @endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" wire:click="beginClosingModal('follow-up-decision-confirm-modal')">Annuler</button><button type="button" class="btn btn-{{ $pendingFollowUpDecision === 'continue' ? 'primary' : 'danger' }}" wire:click="confirmFollowUpDecision">{{ $pendingFollowUpDecision === 'continue' ? 'Confirmer et continuer' : 'Confirmer la clôture' }}</button></div>
            </div></div>
        </div>
    @endif

    @if ($showDoNotContactModal && $selectedCostumer)
        <div class="modal fade show d-block follow-up-modal" id="follow-up-do-not-contact-modal" style="background: rgba(0,0,0,.5)" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-scrollable follow-up-modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Ne plus solliciter — {{ $selectedCostumer->name }}</h5><button type="button" class="close" wire:click="beginClosingModal('follow-up-do-not-contact-modal')" aria-label="Fermer"><span>&times;</span></button></div>
                <div class="modal-body">
                    <p>Ce client sera exclu des listes de contact et de relance jusqu’à réactivation manuelle.</p>
                    <label>Motif</label>
                    <textarea class="form-control" rows="3" wire:model="doNotContactReason" placeholder="Demande du client, numéro erroné…"></textarea>
                    @error('doNotContactReason') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" wire:click="beginClosingModal('follow-up-do-not-contact-modal')">Annuler</button><button class="btn btn-danger" wire:click="markDoNotContact">Confirmer</button></div>
            </div></div>
        </div>
    @endif
    <style>
        .follow-up-modal .modal-dialog-scrollable {
            max-height: calc(100% - 1rem);
        }
        .follow-up-modal .modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 1rem);
        }
        .follow-up-modal .modal-dialog-scrollable .modal-body {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-height: 700px) {
            .follow-up-modal .modal-dialog {
                margin-top: .25rem;
                margin-bottom: .25rem;
            }
            .follow-up-modal .modal-dialog-scrollable {
                max-height: calc(100% - .5rem);
            }
            .follow-up-modal .modal-dialog-scrollable .modal-content {
                max-height: calc(100vh - .5rem);
            }
        }
        .follow-up-modal {
            animation: follow-up-modal-open .32s ease-out both;
            transition: opacity .32s ease-in;
        }
        .follow-up-modal .modal-dialog {
            animation: follow-up-dialog-open .32s ease-out both;
            transition: transform .32s ease-in;
        }
        .quick-case-products { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:8px; margin-top:7px; }
        .quick-case-product { display:flex; align-items:center; gap:9px; min-width:0; margin:0; padding:10px 11px; border:1px solid #e6e7ec; border-radius:8px; background:#fff; cursor:pointer; }
        .quick-case-product:has(input:checked) { border-color:#c99a97; background:#fcf5f4; }
        .quick-case-product input { flex:none; accent-color:#7e1615; }
        .quick-case-product span { flex:1; min-width:0; overflow-wrap:anywhere; color:#42434d; font-size:12px; }
        .quick-case-product small { flex:none; color:#81828c; font-size:11px; }
        .follow-up-modal.is-closing {
            opacity: 0;
        }
        .follow-up-modal.is-closing .modal-dialog {
            transform: translateY(-18px);
        }
        @keyframes follow-up-modal-open {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes follow-up-dialog-open {
            from { opacity: 0; transform: translateY(-18px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <script>
        (function () {
            if (!window.followUpResponseToggleBound) {
                window.followUpResponseToggleBound = true;
                document.addEventListener('change', function (event) {
                    if (!event.target) return;
                    if (event.target.id === 'response-received-now') {
                        var responseFields = document.getElementById('immediate-response-fields');
                        var followUpFields = document.getElementById('scheduled-follow-up-fields');
                        if (responseFields) responseFields.style.display = event.target.checked ? '' : 'none';
                        if (followUpFields) followUpFields.style.display = event.target.checked ? 'none' : '';
                    }

                    if (event.target.id === 'response-received-now' || event.target.id === 'immediate-sentiment' || event.target.id === 'create-service-case') {
                        var responded = document.getElementById('response-received-now');
                        var option = document.getElementById('quick-service-case-option');
                        var checkbox = document.getElementById('create-service-case');
                        var allowed = responded && responded.checked;
                        if (option) option.style.display = allowed ? '' : 'none';
                        if (!allowed && checkbox) checkbox.checked = false;
                    }

                    if (event.target.id === 'response-create-service-case') {
                        var responseCheckbox = document.getElementById('response-create-service-case');
                        var responseCaseFields = document.getElementById('response-quick-service-case-fields');
                        var responseDescription = document.getElementById('response-quick-service-case-description');
                        var responseText = document.getElementById('follow-up-response-text');
                        if (responseCaseFields && responseCheckbox) {
                            responseCaseFields.style.display = responseCheckbox.checked ? '' : 'none';
                        }
                        if (responseCheckbox && responseCheckbox.checked && responseDescription && !responseDescription.value.trim() && responseText) {
                            responseDescription.value = responseText.value;
                            responseDescription.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                });
            }

            if (window.followUpModalCloseBound) return;
            window.followUpModalCloseBound = true;
            window.addEventListener('animate-follow-up-modal-close', function (event) {
                var modal = document.getElementById(event.detail.modal);
                if (!modal || modal.classList.contains('is-closing')) return;
                var dialog = modal.querySelector('.modal-dialog');
                modal.style.animation = 'none';
                if (dialog) dialog.style.animation = 'none';
                void modal.offsetWidth;
                modal.classList.add('is-closing');
                var finished = false;
                var finish = function (transitionEvent) {
                    if (transitionEvent && transitionEvent.target !== modal) return;
                    if (finished) return;
                    finished = true;
                    modal.style.display = 'none';
                    var root = modal.closest('[wire\\:id]');
                    if (root && window.Livewire) {
                        Livewire.find(root.getAttribute('wire:id')).call('finishClosingModal', event.detail.type);
                    }
                };
                modal.addEventListener('transitionend', finish, { once: true });
                window.setTimeout(finish, 360);
            });
        })();
    </script>
</div>
