<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h2 class="mb-1">Suivi des contacts clients</h2>
            <p class="text-muted mb-0">Contacts, réponses et relances réunis dans un seul suivi.</p>
        </div>
    </div>

    @if (session()->has('messages'))
        <div class="alert alert-success">{{ session('messages') }}</div>
    @endif

    <div class="row">
        @foreach ([
            ['all', 'Tous les clients', 'primary'],
            ['not_contacted', 'Non contactés', 'success'],
            ['contacted', 'En attente', 'warning'],
            ['to_follow_up', 'À relancer', 'warning'],
            ['responded', 'Répondu', 'info'],
        ] as [$key, $label, $color])
            <div class="col-xl col-md-4 col-sm-6 mb-3">
                <button type="button" wire:click="$set('statusFilter', '{{ $key }}')" class="card shadow-sm w-100 text-left border-{{ $color }}">
                    <div class="card-body py-3">
                        <div class="text-muted">{{ $label }}</div>
                        <div class="h3 mb-0">{{ $counts[$key] }}</div>
                    </div>
                </button>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Paramètres du suivi</h5>
            <span class="text-muted">Délai global par défaut : {{ $defaultFollowUpDays }} jours</span>
        </div>
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
                    <button class="btn btn-primary" wire:click="saveSettings">Enregistrer les paramètres</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">
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
                        <option value="responded">Ayant répondu</option>
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
                            $latest = $costumer->contactHistories->first();
                            $badge = ['not_contacted' => 'success', 'contacted' => 'warning', 'responded' => 'info', 'to_follow_up' => 'warning'][$status];
                            $statusLabel = ['not_contacted' => 'Non contacté', 'contacted' => 'Contacté / en attente', 'responded' => 'Répondu', 'to_follow_up' => 'À relancer'][$status];
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
                                @if ($status === 'to_follow_up')
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
                                @if ($status !== 'responded')
                                    <button class="btn btn-sm btn-primary" wire:click="openContactModal({{ $costumer->id }}, '{{ $status === 'not_contacted' ? 'initial' : 'follow_up' }}')">
                                        {{ $status === 'not_contacted' ? 'Contacter' : 'Relancer' }}
                                    </button>
                                    @if ($latest)
                                        <button class="btn btn-sm btn-success" wire:click="openResponseModal({{ $latest->id }})">Enregistrer réponse</button>
                                    @endif
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
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5)" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $contactType === 'follow_up' ? 'Enregistrer une relance' : 'Enregistrer un contact' }} — {{ $selectedCostumer->name }}</h5>
                        <button type="button" class="close" wire:click="$set('showContactModal', false)"><span>&times;</span></button>
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
                        @if ($contactUrl)
                            <a href="{{ $contactUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary mb-3">
                                Ouvrir {{ ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'l’appel'][$channel] ?? 'le contact' }} pour {{ $selectedCostumer->phone }}
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
                        @foreach (['channel', 'contactedAt', 'followUpAt', 'notes'] as $field)
                            @error($field) <small class="text-danger d-block">{{ $message }}</small> @enderror
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showContactModal', false)">Annuler</button>
                        <button class="btn btn-primary" wire:click="saveContact">Enregistrer dans l’historique</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showResponseModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5)" role="dialog">
            <div class="modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Réponse du client</h5><button type="button" class="close" wire:click="$set('showResponseModal', false)"><span>&times;</span></button></div>
                <div class="modal-body">
                    <label>Réponse ou avis</label>
                    <textarea class="form-control" rows="5" wire:model="response"></textarea>
                    @error('response') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" wire:click="$set('showResponseModal', false)">Annuler</button><button class="btn btn-success" wire:click="saveResponse">Enregistrer la réponse</button></div>
            </div></div>
        </div>
    @endif

    @if ($showHistoryModal && $selectedCostumer)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5)" role="dialog">
            <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Historique — {{ $selectedCostumer->name }}</h5><button type="button" class="close" wire:click="$set('showHistoryModal', false)"><span>&times;</span></button></div>
                <div class="modal-body">
                    @forelse ($history as $item)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between flex-wrap">
                                <strong>{{ $item->contact_type === 'follow_up' ? 'Relance' : 'Contact initial' }} · {{ ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Appel', 'other' => 'Autre'][$item->channel] }}</strong>
                                <span>{{ $item->contacted_at->format('d/m/Y H:i') }} — {{ $item->user->name ?? 'Utilisateur supprimé' }}</span>
                            </div>
                            @if ($item->follow_up_at)<div class="small text-muted">Relance prévue : {{ $item->follow_up_at->format('d/m/Y H:i') }}</div>@endif
                            @if ($item->notes)<p class="mb-1 mt-2">{{ $item->notes }}</p>@endif
                            @if ($item->responded_at)<div class="alert alert-info mb-0 mt-2"><strong>Réponse du {{ $item->responded_at->format('d/m/Y H:i') }} :</strong> {{ $item->response }}</div>@endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun contact enregistré.</p>
                    @endforelse
                </div>
            </div></div>
        </div>
    @endif
</div>
