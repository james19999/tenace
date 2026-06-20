<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

class ProductAuditReport extends Component
{
    public $reports = [];

    public $filterType = 'day';

    public $selectedDay;
    public $selectedWeek;
    public $selectedMonth;
    public $selectedYear;

    public $totaldif;
    public $total = 0;
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
            $this->loadReport();
        }
    }

    public function loadReport()
    {
        $query = DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered');

        $totalQuery = Order::query()
            ->where('status', 'delivered');

        switch ($this->filterType) {

            case 'day':

                $query->whereDate(
                    'orders.created_at',
                    $this->selectedDay
                );

                $totalQuery->whereDate(
                    'created_at',
                    $this->selectedDay
                );

                break;

            case 'week':

                if ($this->selectedWeek) {

                    [$year, $week] = explode('-W', $this->selectedWeek);

                    $yearWeek = $year . str_pad($week, 2, '0', STR_PAD_LEFT);

                    $query->whereRaw(
                        'YEARWEEK(orders.created_at,1) = ?',
                        [$yearWeek]
                    );

                    $totalQuery->whereRaw(
                        'YEARWEEK(created_at,1) = ?',
                        [$yearWeek]
                    );
                }

                break;

            case 'month':

                if ($this->selectedMonth) {

                    [$year, $month] = explode('-', $this->selectedMonth);

                    $query->whereYear('orders.created_at', $year)
                        ->whereMonth('orders.created_at', $month);

                    $totalQuery->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month);
                }

                break;

            case 'year':

                $query->whereYear(
                    'orders.created_at',
                    $this->selectedYear
                );

                $totalQuery->whereYear(
                    'created_at',
                    $this->selectedYear
                );

                break;
        }

        $this->total = $totalQuery->sum('total');
        $this->reports = $query
            ->select(
                'products.id',
                'products.name',

                DB::raw('SUM(order_items.quantity) as qty'),

                DB::raw('SUM(order_items.quantity * order_items.price) as amount')
            )

            ->groupBy(
                'products.id',
                'products.name'
            )

            ->orderByDesc('qty')

            ->get();
    }

    public function getTotalQtyProperty()
    {
        return collect($this->reports)->sum('qty');
    }

    public function getTotalAmountProperty()
    {
        return collect($this->reports)->sum('amount');
    }

    public function getNetAmountProperty()
    {
        $this->totaldif = $this->totalAmount - $this->total;
        return $this->totalAmount - $this->totaldif;
    }
    public function render()
    {
        $totalYesterday = Order::where('status', 'delivered')
            ->whereDate('created_at', Carbon::yesterday())
            ->sum('total');
        return view('livewire.product-audit-report', ['totalYesterday' => $totalYesterday])
            ->extends('layouts.admin')
            ->section('content');
    }
}
