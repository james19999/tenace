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
            <p class="small text-muted">Utilise <code>&#123;&#123;name&#125;&#125;</code> pour le nom du client et <code>&#123;&#123;product&#125;&#125;</code> pour le produit de sa dernière commande. La signature « TENANCE COSMETIQUE » est ajoutée automatiquement.</p>
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
                                @elseif ($status === 'to_follow_up')
                                    <span class="text-warning font-weight-bold">Échue : {{ $latest->follow_up_at->format('d/m/Y H:i') }}</span>
                                @elseif ($latest && $latest->follow_up_at)
                                    {{ $latest->follow_up_at->format('d/m/Y H:i') }}
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
                                    <button class="btn btn-sm btn-primary" wire:click="decideFollowUp({{ $costumer->id }}, 'continue')">Continuer</button>
                                    <button class="btn btn-sm btn-danger" wire:click="decideFollowUp({{ $costumer->id }}, 'stop')">Clôturer</button>
                                @elseif (!in_array($status, ['responded', 'closed']))
                                    <button class="btn btn-sm btn-primary" wire:click="openContactModal({{ $costumer->id }}, '{{ $status === 'not_contacted' ? 'initial' : 'follow_up' }}')">
                                        {{ $status === 'not_contacted' ? 'Contacter' : 'Relancer' }}
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
                                <input type="datetime-local" class="form-control" wire:model="contactedAt">
                            </div>
                        </div>
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
                        @elseif ($channel !== 'other')
                            <div class="alert alert-warning">Ce client n’a pas de numéro utilisable pour ce moyen de contact.</div>
                        @endif
                        <div class="form-group">
                            <label>Date de relance prévue (facultatif)</label>
                            <input type="datetime-local" class="form-control" wire:model="followUpAt">
                            <small class="form-text text-muted">Par défaut : {{ $defaultFollowUpDays }} jours après le contact. Tu peux choisir une autre date.</small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Note sur le contact</label>
                            <textarea class="form-control" rows="3" wire:model="notes" placeholder="Contexte ou résultat du contact"></textarea>
                        </div>
                        @foreach (['channel', 'contactedAt', 'followUpAt', 'individualCallingCode', 'selectedTemplateId', 'notes'] as $field)
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
                                <strong>{{ $item->contact_type === 'follow_up' ? 'Relance' : 'Contact initial' }} · {{ ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Appel', 'other' => 'Autre'][$item->channel] }}</strong>
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
