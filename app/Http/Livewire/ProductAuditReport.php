<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
class ProductAuditReport extends Component
{
        public $reports = [];

    public function mount()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        $this->reports = DB::table('products')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')->
             where('orders.status', 'deliverd')
            ->select(
                'products.id',
                'products.name',

                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN DATE(orders.created_at) = CURDATE()
                            THEN order_items.quantity
                            ELSE 0
                        END
                    ),0) as daily_qty
                "),

                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN YEARWEEK(orders.created_at,1)
                            = YEARWEEK(CURDATE(),1)
                            THEN order_items.quantity
                            ELSE 0
                        END
                    ),0) as weekly_qty
                "),

                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN MONTH(orders.created_at)=MONTH(CURDATE())
                            AND YEAR(orders.created_at)=YEAR(CURDATE())
                            THEN order_items.quantity
                            ELSE 0
                        END
                    ),0) as monthly_qty
                "),

                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN YEAR(orders.created_at)=YEAR(CURDATE())
                            THEN order_items.quantity
                            ELSE 0
                        END
                    ),0) as yearly_qty
                "),

                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN YEAR(orders.created_at)=YEAR(CURDATE())
                            THEN (order_items.quantity * order_items.price)
                            ELSE 0
                        END
                    ),0) as yearly_amount
                ")
            )

            ->groupBy(
                'products.id',
                'products.name'
            )

            ->orderBy('products.name')
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
        return view('livewire.product-audit-report');
    }
}
