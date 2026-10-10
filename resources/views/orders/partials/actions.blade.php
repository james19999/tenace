<div class="order-actions">
    @if (Auth::user()->user_type === 'ADMINUSER')
        <a href="{{ route('ordershow', $order) }}" class="btn btn-sm btn-success"><i class="material-icons f-16">visibility</i> Détails</a>
        <a href="{{ route('check-type', $order) }}" class="btn btn-sm {{ $order->type === 'PR' ? 'btn-dark' : 'btn-warning' }}"><i class="material-icons f-16">edit</i> {{ $order->type }}</a>
        <form method="POST" action="{{ route('deleteorder', $order) }}" onsubmit="return confirm('Supprimer cette commande ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger"><i class="material-icons">delete</i> Supprimer</button>
        </form>
    @elseif (in_array(Auth::user()->user_type, ['MNG', 'SCR', 'CSA'], true))
        <a href="{{ route('ordershow', $order) }}" class="btn btn-sm btn-success"><i class="material-icons f-16">visibility</i> Détails</a>
        <a href="{{ route('check-type', $order) }}" class="btn btn-sm {{ $order->type === 'PR' ? 'btn-dark' : 'btn-warning' }}"><i class="material-icons f-16">edit</i> {{ $order->type }}</a>
    @endif
    <a href="{{ route('edit-order', $order) }}" class="btn btn-sm btn-info"><i class="fa fa-edit"></i> Modifier</a>
    <a href="{{ route('orders.invoice', $order) }}" class="btn btn-sm btn-success"><i class="fa fa-print"></i> Facture</a>
</div>
