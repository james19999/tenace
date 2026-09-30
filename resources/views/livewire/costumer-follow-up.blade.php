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

    @php
        $feedbackCounts = $counts['feedback'] ?? ['positive' => 0, 'neutral' => 0, 'negative' => 0];
        $feedbackTotal = array_sum($feedbackCounts);
        $feedbackMax = max($feedbackCounts ?: [0]);
    @endphp
    <div class="card shadow my-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="text-uppercase text-muted mb-0 card-title">Avis des clients</h5>
            <span class="text-muted">{{ $feedbackTotal }} réponse(s) enregistrée(s)</span>
        </div>
        <div class="card-body">
            @foreach (['positive' => ['Positifs', 'success'], 'neutral' => ['Neutres', 'secondary'], 'negative' => ['Négatifs', 'danger']] as $sentimentKey => [$sentimentLabel, $sentimentColor])
                @php $sentimentCount = $feedbackCounts[$sentimentKey] ?? 0; @endphp
                <button type="button" class="btn btn-link text-left text-decoration-none p-0 d-block w-100 mb-3" wire:click="openFeedbackDetails('{{ $sentimentKey }}')" aria-label="Voir les avis {{ strtolower($sentimentLabel) }}">
                    <span class="d-flex justify-content-between mb-1"><span>{{ $sentimentLabel }}</span><strong>{{ $sentimentCount }}</strong></span>
                    <span class="progress" style="height: 14px"><span class="progress-bar bg-{{ $sentimentColor }}" role="progressbar" style="width: {{ $feedbackMax ? round($sentimentCount / $feedbackMax * 100) : 0 }}%" aria-valuenow="{{ $sentimentCount }}" aria-valuemin="0" aria-valuemax="{{ $feedbackMax }}"></span></span>
                </button>
            @endforeach
            @if (($feedbackCounts['unclassified'] ?? 0) > 0)
                <small class="text-muted">{{ $feedbackCounts['unclassified'] }} réponse(s) sans tonalité renseignée.</small>
            @endif
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
            <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label>Délai avant relance (jours)</label>
                    <input type="number" min="1" max="365" class="form-control" wire:model="defaultFollowUpDays">
                    @error('defaultFollowUpDays') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Indicatif par défaut</label>
                    <input type="text" class="form-control" placeholder="+228" wire:model="defaultCountryCallingCode">
                    @error('defaultCountryCallingCode') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Nombre maximal de relances</label>
                    <input type="number" min="1" max="20" class="form-control" wire:model="maxFollowUps">
                    @error('maxFollowUps') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-primary" wire:click="saveSettings">Enregistrer les paramètres</button>
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
                                    <span class="text-warning font-weight-bold">Échue : {{ $nextFollowUpAt->format('d/m/Y H:i') }}</span>
                                @elseif ($nextFollowUpAt)
                                    {{ $nextFollowUpAt->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $latest ? ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Appel', 'other' => 'Autre'][$latest->channel] : '—' }}</td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-info" href="{{ route('view-costumers', $costumer->id) }}">Fiche</a>
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
                                @elseif ($nextFollowUpAt && $nextFollowUpAt->isFuture())
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
        <div class="card-body">{{ $costumers->links() }}</div>
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
                                <select class="form-control" wire:model.defer="immediateSentiment">
                                    <option value="positive">Positif</option>
                                    <option value="neutral">Neutre</option>
                                    <option value="negative">Négatif</option>
                                </select>
                            </div>
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
                        <button class="btn btn-primary" wire:click="saveContact">Enregistrer dans l’historique</button>
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
                    <textarea class="form-control" rows="5" wire:model="response"></textarea>
                    @error('response') <small class="text-danger">{{ $message }}</small> @enderror
                    <label class="mt-3">Tonalité de l’avis</label>
                    <select class="form-control" wire:model="sentiment">
                        <option value="positive">Positif</option>
                        <option value="neutral">Neutre</option>
                        <option value="negative">Négatif</option>
                    </select>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" wire:click="beginClosingModal('follow-up-response-modal')">Annuler</button><button class="btn btn-success" wire:click="saveResponse">Enregistrer la réponse</button></div>
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
                    if (!event.target || event.target.id !== 'response-received-now') return;
                    var responseFields = document.getElementById('immediate-response-fields');
                    var followUpFields = document.getElementById('scheduled-follow-up-fields');
                    if (responseFields) responseFields.style.display = event.target.checked ? '' : 'none';
                    if (followUpFields) followUpFields.style.display = event.target.checked ? 'none' : '';
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
