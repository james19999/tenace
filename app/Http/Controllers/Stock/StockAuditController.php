<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockAuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockAuditController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->user_type === 'ADMINUSER', 403);

        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);
        $logs = (clone $query)->with('user:id,name,user_type')->latest('id')->paginate(30)->withQueryString();
        $actionLabels = StockAuditLog::query()->distinct()->orderBy('action')->pluck('action')->mapWithKeys(fn ($action) => [$action => $this->actionLabel($action)]);
        $todayCount = StockAuditLog::query()->whereDate('created_at', today())->count();
        $validationCount = StockAuditLog::query()->whereIn('action', [
            'transfer.authorized', 'receipt.delivery_approved', 'stock.adjustment_approved',
            'stock.adjustment_rejected', 'inventory.count_completed',
        ])->count();

        return view('stocks.audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actionLabels,
            'actionLabels' => $actionLabels,
            'users' => User::query()->whereIn('id', StockAuditLog::query()->whereNotNull('user_id')->distinct()->select('user_id'))->orderBy('name')->get(['id', 'name']),
            'filteredCount' => (clone $query)->count(),
            'todayCount' => $todayCount,
            'validationCount' => $validationCount,
        ]);
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->user_type === 'ADMINUSER', 403);
        $filters = $this->validatedFilters($request);
        $logs = $this->filteredQuery($filters)->with('user:id,name,user_type')->orderByDesc('id')->get();
        $rows = $logs->map(function (StockAuditLog $log): array {
            $after = $log->after_values ?? [];
            $reference = collect($after)->first(fn ($value, $key) => str_contains((string) $key, 'number') || str_contains((string) $key, 'reference'));
            $subjectLabels = ['StockTransfer' => 'Transfert', 'StockReceipt' => 'Réception', 'StockReceiptEvent' => 'Réception', 'StockCount' => 'Inventaire', 'StockAdjustment' => 'Correction', 'StockLocation' => 'Emplacement', 'StockMovement' => 'Mouvement', 'StockAlert' => 'Alerte', 'Product' => 'Produit'];

            return [
                'date' => $log->created_at,
                'action' => $this->actionLabel($log->action),
                'reference' => $reference ?: (($subjectLabels[class_basename($log->subject_type)] ?? 'Opération').' n°'.$log->subject_id),
                'actor' => $log->user?->name ?? 'Compte supprimé',
                'actor_label' => in_array($log->action, ['transfer.authorized', 'receipt.delivery_approved', 'stock.adjustment_approved', 'stock.adjustment_rejected', 'inventory.count_completed'], true) ? 'Validé ou traité par' : 'Enregistré par',
                'result' => ['success' => 'Effectué', 'failure' => 'Échec', 'rejected' => 'Refusé'][$log->result] ?? 'Enregistré',
                'reason' => $log->reason,
            ];
        });

        return Pdf::loadView('stocks.audit.report', [
            'rows' => $rows,
            'filters' => $filters,
            'filterActionLabel' => ! empty($filters['action']) ? $this->actionLabel($filters['action']) : null,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download('rapport-audit-stocks-'.now()->format('Y-m-d_His').'.pdf');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        return StockAuditLog::query()
            ->when($filters['search'] ?? null, function ($query, $term) {
                $query->where(function ($where) use ($term) {
                    $where->where('action', 'like', "%{$term}%")
                        ->orWhere('reason', 'like', "%{$term}%")
                        ->orWhere('subject_type', 'like', "%{$term}%")
                        ->orWhere('subject_id', 'like', "%{$term}%")
                        ->orWhere('after_values', 'like', "%{$term}%")
                        ->orWhere('before_values', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
    }

    public function actionLabel(string $action): string
    {
        return [
            'transfer.authorized' => 'Transfert validé', 'receipt.delivery_approved' => 'Réception validée',
            'stock.adjustment_approved' => 'Ajustement validé', 'stock.adjustment_rejected' => 'Ajustement refusé',
            'inventory.count_completed' => 'Inventaire appliqué', 'receipt.delivery_submitted' => 'Quantités de réception saisies',
            'transfer.received' => 'Transfert réceptionné', 'transfer.shipped' => 'Transfert expédié',
            'transfer.created' => 'Transfert créé', 'transfer.submitted' => 'Transfert soumis pour validation',
            'transfer.auto_authorized' => 'Transfert autorisé automatiquement', 'transfer.admin_created_and_shipped' => 'Transfert créé et expédié par l’administrateur',
            'receipt.created' => 'Réception créée', 'receipt.rescheduled' => 'Réception replanifiée',
            'inventory.count_created' => 'Inventaire créé', 'inventory.count_submitted' => 'Comptage soumis',
            'order.stock_decremented' => 'Stock déduit après livraison de commande',
            'legacy.stock_received' => 'Ancienne entrée de stock', 'legacy.stock_issued' => 'Ancienne sortie de stock',
            'legacy.stock_issue_rejected' => 'Ancienne sortie refusée', 'product.wholesale_price_updated' => 'Prix grossiste modifié',
            'stock.alerts_refreshed' => 'Alertes actualisées', 'stock.alert_resolved' => 'Alerte traitée',
            'location.created' => 'Emplacement créé', 'location.updated' => 'Emplacement modifié',
            'location.deleted' => 'Emplacement supprimé', 'stock.rules_updated' => 'Règles de stock modifiées',
        ][$action] ?? Str::headline(str_replace('.', ' ', $action));
    }
}
