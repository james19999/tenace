<div class="table-responsive">
    <table class="table table-hover w-100">
        <thead class="thead-light">
            <tr>
                <th>N°</th>
                <th>Code</th>
                <th>Cliente</th>
                <th>Livreur</th>
                <th>Livraison prévue</th>
                <th>Attribution</th>
                <th>Sous-total</th>
                <th>Remise</th>
                <th>Total</th>
                <th>État</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ ($orders->firstItem() ?? 1) + $loop->index }}</td>
                    <td>{{ $order->code }}</td>
                    <td>{{ $order->costumer->name ?? '—' }}<small class="d-block text-muted">{{ $order->costumer->phone ?? '—' }}</small></td>
                    <td>{{ $order->user->name ?? 'Non attribué' }}<small class="d-block text-muted">{{ $order->user->phone ?? '' }}</small></td>
                    <td>{{ $order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $order->assigned_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $order->subtotal }} F</td>
                    <td>{{ $order->remis }} %</td>
                    <td>{{ $order->total }} F</td>
                    <td>
                        @if ($order->status === 'ordered')
                            <span class="badge badge-warning">En cours</span>
                        @elseif ($order->status === 'delivered')
                            <span class="badge badge-success">Livrée</span>
                        @elseif ($order->status === 'canceled')
                            <span class="badge badge-danger">Annulée</span>
                        @endif
                    </td>
                    <td>
                        <div class="order-actions">
                            @if (Auth::user()->user_type === 'ADMINUSER')
                                <a href="{{ route('ordershow', $order) }}" class="btn btn-sm btn-success"><i class="material-icons f-16">visibility</i> Détails</a>
                                <a href="{{ route('check-type', $order) }}" class="btn btn-sm {{ $order->type === 'PR' ? 'btn-dark' : 'btn-warning' }}"><i class="material-icons f-16">edit</i> {{ $order->type }}</a>
                                <form method="POST" action="{{ route('deleteorder', $order) }}" onsubmit="return confirm('Supprimer cette commande ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="material-icons">delete</i> Supprimer</button>
                                </form>
                            @elseif (in_array(Auth::user()->user_type, ['MNG', 'SCR', 'CSA'], true))
                                <a href="{{ route('ordershow', $order) }}" class="btn btn-sm btn-success"><i class="material-icons f-16">visibility</i> Détails</a>
                                <a href="{{ route('check-type', $order) }}" class="btn btn-sm {{ $order->type === 'PR' ? 'btn-dark' : 'btn-warning' }}"><i class="material-icons f-16">edit</i> {{ $order->type }}</a>
                            @endif
                            <a href="{{ route('edit-order', $order) }}" class="btn btn-sm btn-info"><i class="fa fa-edit"></i> Modifier</a>
                            <a href="{{ route('orders.invoice', $order) }}" class="btn btn-sm btn-success"><i class="fa fa-print"></i> Facture</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted py-4">Aucune commande dans cet onglet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
