<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\StockControlNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'movements');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:32'],
            'location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'status' => ['nullable', Rule::in(['all', 'posted', 'submitted', 'draft', 'reversed'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'posted';

        $movements = StockMovement::query()
            ->with(['creator:id,name', 'approver:id,name', 'lines.product:id,name', 'lines.location:id,name,code'])
            ->withCount('lines')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('occurred_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('occurred_at', '<=', $filters['date_to']))
            ->when($search !== '' || ! empty($filters['location_id']), function ($query) use ($search, $filters): void {
                $query->whereHas('lines', function ($lineQuery) use ($search, $filters): void {
                    $lineQuery->when($search !== '', fn ($q) => $q->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%'.$search.'%')))
                        ->when(! empty($filters['location_id']), fn ($q) => $q->where('location_id', $filters['location_id']));
                });
            })
            ->latest('occurred_at')->latest('id')->paginate(20)->withQueryString();

        $locations = StockLocation::query()->orderBy('name')->get(['id', 'name', 'code']);
        $types = StockMovement::query()->select('type')->distinct()->orderBy('type')->pluck('type');

        return view('stocks.movements.index', compact('movements', 'locations', 'types', 'search', 'status', 'filters'));
    }

    public function show(StockMovement $movement): View
    {
        app(StockControlNotificationService::class)->markTabAsRead(auth()->user(), 'movements');
        $movement->load(['creator:id,name', 'approver:id,name', 'lines.product:id,name,price_market', 'lines.location:id,name,code', 'lines.lot:id,lot_code,expires_at', 'transfer', 'order']);

        return view('stocks.movements.show', compact('movement'));
    }
}
