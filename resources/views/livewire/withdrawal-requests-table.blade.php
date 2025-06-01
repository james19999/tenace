<div class="table-responsive">
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Partenaire</th>
                <th>Montant</th>
                <th>Moyen</th>
                <th>Détails</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($requests as $request)
                <tr>
                    <td>{{ $request->user->name ?? '' }}
                        -
                        {{ $request->user->phone ?? '' }}
                        -
                        {{ $request->user->adresse ?? '' }}
                        {{ $request->user->email ?? '' }}

                    </td>
                    <td>{{ number_format($request->amount, 0, ',', ' ') }} FCFA</td>
                    <td>{{ strtoupper($request->method) }}</td>
                    <td>{{ $request->details }}</td>
                    <td>
                        <span
                            class="badge bg-{{ $request->status === 'accepté' ? 'success' : ($request->status === 'rejeté' ? 'danger' : 'warning') }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </td>
                    <td>
                        @if ($request->status === 'en_attente')
                            <button wire:click="accept({{ $request->id }})"
                                class="btn btn-success btn-sm">Accepter</button>
                            <button wire:click="reject({{ $request->id }})"
                                class="btn btn-danger btn-sm">Rejeter</button>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
