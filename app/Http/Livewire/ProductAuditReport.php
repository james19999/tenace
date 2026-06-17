<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
class ProductAuditReport extends Component
{
    public $reports = [];

 public $filterType = 'day';

public $selectedDay;
public $selectedWeek;
public $selectedMonth;
public $selectedYear;

public function mount()
{
    $this->selectedDay = now()->format('Y-m-d');
    $this->selectedWeek = now()->format('Y-\WW');
    $this->selectedMonth = now()->format('Y-m');
    $this->selectedYear = now()->year;

    $this->loadReport();
}

public function updated()
{
    $this->loadReport();
}
public function loadReport()
{
    $query = DB::table('products')
        ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
        ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
        ->where('orders.status', 'delivered');

    switch ($this->filterType) {

        case 'day':

            $query->whereDate(
                'orders.created_at',
                $this->selectedDay
            );

            break;

        case 'week':

            [$year, $week] = explode('-W', $this->selectedWeek);

            $query->whereRaw(
                'YEARWEEK(orders.created_at,1)=?',
                [$year . str_pad($week, 2, '0', STR_PAD_LEFT)]
            );

            break;

        case 'month':

            [$year, $month] = explode('-', $this->selectedMonth);

            $query->whereYear('orders.created_at', $year)
                  ->whereMonth('orders.created_at', $month);

            break;

        case 'year':

            $query->whereYear(
                'orders.created_at',
                $this->selectedYear
            );

            break;
    }

    $this->reports = $query
        ->select(
            'products.id',
            'products.name',

            DB::raw('SUM(order_items.quantity) as qty'),

            DB::raw('SUM(
                order_items.quantity * order_items.price
            ) as amount')
        )

        ->groupBy(
            'products.id',
            'products.name'
        )

        ->orderByDesc('qty')

        ->get();
}

    public function getTotalDailyProperty()
    {
        return collect($this->reports)->sum('daily_qty');
    }

    public function getTotalWeeklyProperty()
    {
        return collect($this->reports)->sum('weekly_qty');
    }

    public function getTotalMonthlyProperty()
    {
        return collect($this->reports)->sum('monthly_qty');
    }

    public function getTotalYearlyProperty()
    {
        return collect($this->reports)->sum('yearly_qty');
    }

    public function getTotalAmountProperty()
    {
        return collect($this->reports)->sum('yearly_amount');
    }
    public function render()
    {
        return view('livewire.product-audit-report')
                ->extends('layouts.admin')
           ->section('content')
        ;
    }
}
