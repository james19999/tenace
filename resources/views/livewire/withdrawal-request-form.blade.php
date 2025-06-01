<div class="mt-5">
    <h4 class="mb-3">Demande de virement</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Solde disponible -->
            <div class="mb-4">
                <h5>Solde disponible</h5>
                <p class="display-6 text-success fw-bold">{{ number_format($wallet->balance ?? 0, 0, ',', ' ') }} FCFA
                </p>
            </div>

            <!-- Formulaire de demande de virement -->
            <form wire:submit.prevent="submitWithdrawalRequest">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="amount" class="form-label">Montant à retirer</label>
                        <input type="number" wire:model="amount" id="amount" class="form-control"
                            placeholder="Ex : 25000">
                        @error('amount')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{--  <div class="col-md-6">
                        <label for="method" class="form-label">Moyen de retrait</label>
                        <select wire:model="method" id="method" class="form-select">
                            <option value="">-- Choisir --</option>
                            <option value="flooz">FLOOZ</option>
                            <option value="tmoney">TMONEY</option>
                            <option value="bank">Compte bancaire</option>
                        </select>
                        @error('method')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>  --}}
                </div>

                {{--  <div class="mb-3">
                    <label for="details" class="form-label">Numéro ou IBAN</label>
                    <input type="text" wire:model="details" id="details" class="form-control"
                        placeholder="Ex : 99657879 ou IBAN bancaire">
                    @error('details')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>  --}}

                <button type="submit" class="btn btn-primary">
                    Envoyer la demande de virement
                </button>
            </form>
        </div>
    </div>
    <hr class="my-4">

    <h5 class="mb-3">Historique des demandes</h5>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Détails</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myRequests as $request)
                    <tr>
                        <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($request->amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ strtoupper($request->method) }}</td>
                        <td>{{ $request->details }}</td>
                        <td>
                            <span
                                class="badge bg-{{ $request->status === 'accepté' ? 'success' : ($request->status === 'rejeté' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucune demande enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
