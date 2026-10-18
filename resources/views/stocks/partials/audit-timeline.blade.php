@php
    $auditActionLabels = [
        'inventory.count_created' => 'Inventaire créé et attribué',
        'inventory.count_submitted' => 'Quantités physiques saisies',
        'inventory.count_completed' => 'Inventaire appliqué au stock',
        'stock.adjustment_applied' => 'Ajustement appliqué au stock',
        'stock.adjustment_approved' => 'Ancien ajustement appliqué',
        'stock.adjustment_rejected' => 'Ancien ajustement refusé',
    ];
    $auditValueLabels = [
        'status' => 'État', 'count_number' => 'Référence inventaire', 'adjustment_number' => 'Référence ajustement',
        'location_name' => 'Emplacement', 'counter_name' => 'Responsable du comptage',
        'scheduled_at' => 'Date prévue', 'scope' => 'Périmètre', 'opening_balance' => 'Reprise initiale',
        'line_count' => 'Produits concernés', 'quantity_before' => 'Quantité avant',
        'quantity_counted' => 'Quantité comptée', 'quantity_delta' => 'Variation', 'quantity_after' => 'Quantité après',
        'reason_code' => 'Type de correction', 'movement_number' => 'Mouvement de stock',
        'rejection_reason' => 'Motif du refus',
    ];
@endphp
<section class="stock-control-card stock-audit-timeline">
    <div class="stock-control-section-head"><div><span class="stock-control-kicker">TRAÇABILITÉ</span><h2>Historique des actions</h2></div><span class="stock-control-count">{{ $auditLogs->count() }} action(s)</span></div>
    @forelse($auditLogs as $auditLog)
        @php($details = $auditLog->after_values ?? [])
        <article class="stock-audit-event">
            <div class="stock-audit-event-heading"><div><strong>{{ $auditActionLabels[$auditLog->action] ?? ucfirst(str_replace(['.', '_'], ' ', $auditLog->action)) }}</strong><small>{{ $auditLog->user?->name ?? 'Utilisateur supprimé' }}@if($auditLog->actor_role) · {{ $auditLog->actor_role }}@endif</small></div><time datetime="{{ $auditLog->created_at?->toIso8601String() }}">{{ $auditLog->created_at?->format('d/m/Y à H:i:s') }}</time></div>
            @if($auditLog->reason)<p class="stock-audit-event-reason">{{ $auditLog->reason }}</p>@endif
            @if(!empty($auditLog->before_values['status']) || !empty($details['status']))
                @php($statusLabels = ['in_progress' => 'À compter', 'submitted' => 'À appliquer', 'completed' => 'Terminé', 'created' => 'Créé', 'posted' => 'Appliqué'])
                <p class="stock-audit-event-state">{{ $statusLabels[$auditLog->before_values['status'] ?? ''] ?? ($auditLog->before_values['status'] ?? '—') }} → {{ $statusLabels[$details['status'] ?? ''] ?? ($details['status'] ?? '—') }}</p>
            @endif
            @foreach($details as $key => $value)
                @if($key === 'lines' && is_array($value) && count($value) && !in_array($auditLog->action, ['inventory.count_submitted', 'stock.adjustment_applied'], true))
                    <div class="stock-audit-lines"><strong>Détail des produits</strong><div class="stock-audit-lines-scroll"><table><thead><tr><th>Produit</th><th>Attendu</th><th>Compté</th><th>Écart</th></tr></thead><tbody>
                        @foreach($value as $line)
                            <tr><td>{{ $line['product_name'] ?? 'Produit #'.($line['product_id'] ?? '—') }}</td><td>{{ number_format((float)($line['expected_quantity'] ?? $line['quantity_before'] ?? 0), 0, ',', ' ') }}</td><td>{{ number_format((float)($line['counted_quantity'] ?? $line['quantity_after'] ?? 0), 0, ',', ' ') }}</td><td>{{ sprintf('%+d', (int)($line['difference'] ?? $line['quantity_delta'] ?? 0)) }}</td></tr>
                        @endforeach
                    </tbody></table></div></div>
                @elseif(isset($auditValueLabels[$key]) && !is_array($value))
                    <p class="stock-audit-event-value"><span>{{ $auditValueLabels[$key] }}</span><strong>{{ is_bool($value) ? ($value ? 'Oui' : 'Non') : ($value === null || $value === '' ? '—' : $value) }}</strong></p>
                @endif
            @endforeach
            @if(!empty($details['adjustment_id']))
                <a class="stock-control-button" href="{{ route('stock-control.adjustments.show', $details['adjustment_id']) }}">Ouvrir l’ajustement {{ $details['adjustment_number'] ?? '' }}</a>
            @endif
        </article>
    @empty
        <p class="stock-audit-empty">Aucune action n’a encore été enregistrée pour ce dossier.</p>
    @endforelse
</section>
