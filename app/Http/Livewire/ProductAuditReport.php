<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

class ProductAuditReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $filterType = 'day';

    public $selectedDay;
    public $selectedWeek;
    public $selectedMonth;
    public $selectedYear;

    public $totaldif;
    public $total = 0;
    public $totalQty = 0;
    public $totalAmount = 0;
    public function mount()
    {
        $this->selectedDay = now()->format('Y-m-d');
        $this->selectedWeek = now()->format('Y-\WW');
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = now()->year;
        $this->loadReport();
    }

    public function updated($propertyName)
    {
        if (
            in_array($propertyName, [
                'filterType',
                'selectedDay',
                'selectedWeek',
                'selectedMonth',
                'selectedYear'
            ])
        ) {
            $this->resetPage();
            $this->loadReport();
        }
    }

    protected function productsQuery()
    {
        $query = DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered');

        switch ($this->filterType) {

            case 'day':

                $query->whereDate(
                    'orders.delivered_at',
                    $this->selectedDay
                );

                break;

            case 'week':

                if ($this->selectedWeek) {

                    [$year, $week] = explode('-W', $this->selectedWeek);

                    $yearWeek = $year . str_pad($week, 2, '0', STR_PAD_LEFT);

                    $query->whereRaw(
                        'YEARWEEK(orders.delivered_at,1) = ?',
                        [$yearWeek]
                    );

                }

                break;

            case 'month':

                if ($this->selectedMonth) {

                    [$year, $month] = explode('-', $this->selectedMonth);

                    $query->whereYear('orders.delivered_at', $year)
                        ->whereMonth('orders.delivered_at', $month);

                }

                break;

            case 'year':

                $query->whereYear(
                    'orders.delivered_at',
                    $this->selectedYear
                );

                break;
        }

        return $query;
    }

    protected function deliveredOrdersQuery()
    {
        $query = Order::query()->where('status', 'delivered');

        switch ($this->filterType) {
            case 'day':
                $query->whereDate('delivered_at', $this->selectedDay);
                break;
            case 'week':
                if ($this->selectedWeek) {
                    [$year, $week] = explode('-W', $this->selectedWeek);
                    $query->whereRaw('YEARWEEK(delivered_at,1) = ?', [$year . str_pad($week, 2, '0', STR_PAD_LEFT)]);
                }
                break;
            case 'month':
                if ($this->selectedMonth) {
                    [$year, $month] = explode('-', $this->selectedMonth);
                    $query->whereYear('delivered_at', $year)->whereMonth('delivered_at', $month);
                }
                break;
            case 'year':
                $query->whereYear('delivered_at', $this->selectedYear);
                break;
        }

        return $query;
    }

    public function loadReport()
    {
        $aggregates = (clone $this->productsQuery())
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as qty')
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.price), 0) as amount')
            ->first();

        $this->totalQty = (int) ($aggregates->qty ?? 0);
        $this->totalAmount = (float) ($aggregates->amount ?? 0);
        $this->total = $this->deliveredOrdersQuery()->sum('total');
    }

    public function getTaxProperty()
{
    return $this->totalAmount - $this->total;
}
    public function getNetAmountProperty()
    {
        return $this->totalAmount - $this->tax;
    }
    public function render()
    {
        $reportsQuery = $this->productsQuery()
            ->select('products.id', 'products.name')
            ->selectRaw('SUM(order_items.quantity) as qty')
            ->selectRaw('SUM(order_items.quantity * order_items.price) as amount')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty');

        $reports = (clone $reportsQuery)->paginate(10);
        $allReports = (clone $reportsQuery)->get();

        return view('livewire.product-audit-report', [
            'paginatedReports' => $reports,
            'allReports' => $allReports,
        ])
            ->extends('layouts.admin')
            ->section('content');
    }
}
