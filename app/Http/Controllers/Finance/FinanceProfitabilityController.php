<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAuditEvent;
use App\Models\FinanceProfitabilityAdjustment;
use App\Models\FinanceProfitabilityPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FinanceProfitabilityController extends Controller
{
    private const KINDS = ['sales_discount', 'direct_supply_cost', 'other_income', 'other_expense', 'income_tax'];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isAccountant(), 403);

        $filters = $this->filters($request);
        $monthlyPeriod = $filters['mode'] === 'month'
            ? FinanceProfitabilityPeriod::query()->firstOrCreate(
                ['period_start' => $filters['from']->toDateString()],
                ['period_end' => $filters['to']->toDateString()]
            )
            : null;
        $computed = $monthlyPeriod?->status === 'closed' && $monthlyPeriod->snapshot
            ? $monthlyPeriod->snapshot
            : $this->calculate($filters['from'], $filters['to']);
        $comparison = $this->calculate($filters['compare_from'], $filters['compare_to']);
        $adjustments = FinanceProfitabilityAdjustment::query()
            ->with('creator:id,name', 'voider:id,name')
            ->whereBetween('period_start', [$filters['from']->toDateString(), $filters['to']->toDateString()])
            ->latest('period_start')->latest('id')->limit(100)->get();
        $closedPeriodStarts = FinanceProfitabilityPeriod::query()->where('status', 'closed')
            ->whereBetween('period_start', [$filters['from']->toDateString(), $filters['to']->toDateString()])
            ->pluck('period_start')->map(fn ($date) => Carbon::parse($date)->toDateString());
        $closedPeriods = FinanceProfitabilityPeriod::query()->with('closer:id,name')
            ->where('status', 'closed')->latest('period_start')->limit(12)->get();

        return view('finance.profitability.index', [
            'filters' => $filters,
            'metrics' => $computed,
            'comparison' => $comparison,
            'monthlyPeriod' => $monthlyPeriod,
            'adjustments' => $adjustments,
            'closedPeriodStarts' => $closedPeriodStarts,
            'closedPeriods' => $closedPeriods,
            'warnings' => $this->warnings($computed),
            'canManage' => $user->isAdmin(),
        ]);
    }

    public function storeAdjustment(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'kind' => ['required', Rule::in(self::KINDS)],
            'nature' => ['required', Rule::in(['fixed', 'variable'])],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'description' => ['required', 'string', 'max:500'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $periodStart = Carbon::createFromFormat('!Y-m', $data['period'])->startOfMonth();
        $period = FinanceProfitabilityPeriod::query()->whereDate('period_start', $periodStart)->first();
        abort_if($period?->status === 'closed', 409, 'Cette période est clôturée.');

        $adjustment = FinanceProfitabilityAdjustment::query()->create([
            'period_start' => $periodStart->toDateString(),
            'kind' => $data['kind'],
            'nature' => $data['nature'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'created_by' => $user->id,
        ]);
        FinanceAuditEvent::query()->create([
            'subject_type' => 'FinanceProfitabilityAdjustment', 'subject_id' => $adjustment->id,
            'action' => 'profitability_adjustment_created',
            'after_values' => $adjustment->only(['period_start', 'kind', 'nature', 'amount', 'description']),
            'reason' => $data['reason'], 'user_id' => $user->id, 'created_at' => now(),
        ]);

        return redirect()->route('finance.profitability.index', $request->query())->with('success', 'L’ajustement a été ajouté et tracé dans le journal d’audit.');
    }

    public function voidAdjustment(Request $request, FinanceProfitabilityAdjustment $adjustment): Response
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $period = FinanceProfitabilityPeriod::query()->whereDate('period_start', $adjustment->period_start)->first();
        abort_if($period?->status === 'closed', 409, 'Cette période est clôturée.');
        abort_if($adjustment->status !== 'active', 409, 'Cet ajustement est déjà annulé.');

        $before = $adjustment->only(['period_start', 'kind', 'nature', 'amount', 'description', 'status']);
        $adjustment->update([
            'status' => 'voided', 'voided_by' => $request->user()->id,
            'voided_at' => now(), 'void_reason' => $data['reason'], 'updated_by' => $request->user()->id,
        ]);
        FinanceAuditEvent::query()->create([
            'subject_type' => 'FinanceProfitabilityAdjustment', 'subject_id' => $adjustment->id,
            'action' => 'profitability_adjustment_voided', 'before_values' => $before,
            'after_values' => $adjustment->fresh()->only(['period_start', 'kind', 'nature', 'amount', 'description', 'status']),
            'reason' => $data['reason'], 'user_id' => $request->user()->id, 'created_at' => now(),
        ]);

        return redirect()->route('finance.profitability.index', $request->query())->with('success', 'L’ajustement a été annulé. Son ancienne valeur et le motif restent dans l’audit.');
    }

    public function closePeriod(Request $request): Response
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'confirmation' => ['accepted'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $start = Carbon::createFromFormat('!Y-m', $data['period'])->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $period = DB::transaction(function () use ($request, $start, $end, $data): FinanceProfitabilityPeriod {
            $period = FinanceProfitabilityPeriod::query()->whereDate('period_start', $start)->lockForUpdate()->first();
            if (! $period) {
                $period = FinanceProfitabilityPeriod::query()->create([
                    'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
                ]);
            }
            abort_if($period->status === 'closed', 409, 'Cette période est déjà clôturée.');

            $snapshot = $this->calculate($start, $end);
            abort_unless($snapshot['cost_reliable'], 422, 'La période ne peut pas être clôturée : rapproche d’abord les sorties de stock et les coûts d’achat des commandes livrées.');
            $period->update([
                'status' => 'closed', 'snapshot' => $snapshot, 'closed_by' => $request->user()->id,
                'closed_at' => now(), 'period_end' => $end->toDateString(),
            ]);
            FinanceAuditEvent::query()->create([
                'subject_type' => 'FinanceProfitabilityPeriod', 'subject_id' => $period->id,
                'action' => 'profitability_period_closed', 'after_values' => $snapshot,
                'reason' => $data['reason'], 'user_id' => $request->user()->id, 'created_at' => now(),
            ]);

            return $period;
        });

        return redirect()->route('finance.profitability.index', $request->query())->with('success', 'La période a été clôturée. Les indicateurs sont conservés dans son historique.');
    }

    public function export(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isAccountant(), 403);
        $filters = $this->filters($request);
        $closedPeriod = $filters['mode'] === 'month'
            ? FinanceProfitabilityPeriod::query()->whereDate('period_start', $filters['from'])->where('status', 'closed')->first()
            : null;
        $metrics = $closedPeriod?->snapshot ?: $this->calculate($filters['from'], $filters['to']);
        $filename = 'tenacos-rentabilite-'.$filters['from']->format('Y-m-d').'-'.$filters['to']->format('Y-m-d');
        $rows = $this->exportRows($metrics);

        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('finance.profitability.pdf', compact('metrics', 'filters', 'rows'))
                ->setPaper('a4', 'portrait')->download($filename.'.pdf');
        }
        if ($request->query('format') === 'xlsx') {
            return $this->downloadXlsx($filename.'.xlsx', $rows);
        }

        return response()->streamDownload(function () use ($rows, $filters): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['TENACOS · Rentabilité', $filters['from']->toDateString().' au '.$filters['to']->toDateString()], ';');
            fputcsv($output, ['Indicateur', 'Montant (FCFA)'], ';');
            foreach ($rows as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['month', 'quarter', 'year'])],
            'period' => ['nullable', 'string', 'regex:/^\d{4}(-\d{2})?$/'],
            'compare' => ['nullable', 'date_format:Y-m'],
        ]);
        $mode = $data['mode'] ?? 'month';
        $period = $data['period'] ?? now()->format($mode === 'year' ? 'Y' : 'Y-m');
        if ($mode === 'year') {
            abort_unless(preg_match('/^\d{4}$/', $period) === 1, 422);
            $from = Carbon::createFromDate((int) $period, 1, 1)->startOfYear();
            $to = $from->copy()->endOfYear();
            $compareFrom = $from->copy()->subYear();
            $compareTo = $compareFrom->copy()->endOfYear();
        } else {
            abort_unless(preg_match('/^\d{4}-\d{2}$/', $period) === 1, 422);
            abort_unless((int) substr($period, 5, 2) >= 1 && (int) substr($period, 5, 2) <= 12, 422);
            $selectedMonth = Carbon::createFromFormat('!Y-m', $period);
            $from = $mode === 'quarter' ? $selectedMonth->copy()->startOfQuarter() : $selectedMonth->copy()->startOfMonth();
            $to = $mode === 'quarter' ? $from->copy()->endOfQuarter() : $from->copy()->endOfMonth();
            $compareFrom = isset($data['compare'])
                ? Carbon::createFromFormat('!Y-m', $data['compare'])->startOfMonth()
                : ($mode === 'quarter' ? $from->copy()->subQuarter()->startOfQuarter() : $from->copy()->subMonth()->startOfMonth());
            if ($mode === 'quarter') {
                $compareFrom->startOfQuarter();
            }
            $compareTo = $mode === 'quarter' ? $compareFrom->copy()->endOfQuarter() : $compareFrom->copy()->endOfMonth();
        }

        return [
            'mode' => $mode, 'period' => $period, 'compare' => $compareFrom->format('Y-m'),
            'from' => $from, 'to' => $to, 'compare_from' => $compareFrom, 'compare_to' => $compareTo,
        ];
    }

    private function calculate(Carbon $from, Carbon $to): array
    {
        // Revenue follows delivered orders, like the main dashboard. A missing stock
        // movement must raise a stock warning, not make a real sale disappear.
        $salesOrders = DB::table('orders as o')->whereIn('o.status', ['delivered', 'canceled'])
            ->whereBetween('o.delivered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $deliveredOrders = DB::table('orders')->where('status', 'delivered')
            ->whereBetween('delivered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $grossSales = (int) round((float) (clone $salesOrders)->sum(DB::raw('CAST(COALESCE(o.subtotal, o.total, 0) AS DECIMAL(18,2))')));
        $recordedDiscounts = (int) round((float) (clone $salesOrders)->sum(DB::raw('GREATEST(CAST(COALESCE(o.subtotal, o.total, 0) AS DECIMAL(18,2)) - CAST(COALESCE(o.total, 0) AS DECIMAL(18,2)), 0)')));
        $salesCount = (int) (clone $deliveredOrders)->count();
        $confirmedPayments = DB::table('order_payments')->whereIn('status', ['collected', 'confirmed'])
            ->select('order_id')->selectRaw('SUM(amount) as total_paid')->groupBy('order_id');
        $receivables = (int) round((float) DB::table('orders as o')->leftJoinSub($confirmedPayments, 'payments', fn ($join) => $join->on('payments.order_id', '=', 'o.id'))
            ->where('o.status', 'delivered')->whereBetween('o.delivered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->sum(DB::raw('GREATEST(CAST(COALESCE(o.montant, o.total + COALESCE(o.tax, 0), o.total, 0) AS DECIMAL(18,2)) - GREATEST(COALESCE(o.paid_amount, 0), COALESCE(payments.total_paid, 0)), 0)')));
        $cancelledSalesQuery = DB::table('orders as o')->where('o.status', 'canceled')
            ->whereBetween('o.canceled_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $cancelledSales = (int) round((float) (clone $cancelledSalesQuery)->sum(DB::raw('CAST(COALESCE(o.total, o.subtotal, 0) AS DECIMAL(18,2))')));
        $estimatedCancellationDates = (int) (clone $cancelledSalesQuery)->where('o.canceled_at_is_estimated', true)->count();

        $adjustmentQuery = FinanceProfitabilityAdjustment::query()->where('status', 'active')
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]);
        $sumAdjustment = fn (string $kind): int => (int) (clone $adjustmentQuery)->where('kind', $kind)->sum('amount');
        $manualDiscounts = $sumAdjustment('sales_discount');
        $discounts = $recordedDiscounts + $manualDiscounts;
        $directSupplyCosts = $sumAdjustment('direct_supply_cost');
        $otherIncome = $sumAdjustment('other_income');
        $otherExpenses = $sumAdjustment('other_expense');
        $incomeTax = $sumAdjustment('income_tax');
        $netSales = $grossSales - $cancelledSales - $discounts;

        $openingStock = $this->stockValueBefore($from);
        $closingStock = $this->stockValueThrough($to);
        $purchases = (int) round((float) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sm.type', 'receipt')
            ->whereBetween('sm.occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('sml.quantity_delta', '>', 0)->whereNotNull('sml.unit_cost')
            ->selectRaw('COALESCE(SUM(sml.quantity_delta * sml.unit_cost), 0) as total')->value('total'));
        $rollforwardCost = $openingStock['value'] + $purchases + $directSupplyCosts - $closingStock['value'];
        $negativeCostRollforward = $rollforwardCost < 0;
        $saleCostLines = DB::table('stock_movement_lines as sml')->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.type', 'sale')->where('sm.status', 'posted')
            ->whereBetween('sm.occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('sml.quantity_delta', '<', 0);
        $saleCostLineCount = (int) (clone $saleCostLines)->count();
        $ordersWithoutSaleMovement = (int) (clone $deliveredOrders)->whereNotExists(fn ($query) => $query->selectRaw('1')
            ->from('stock_movements as sm')->whereColumn('sm.order_id', 'orders.id')->where('sm.type', 'sale')->where('sm.status', 'posted'))->count();
        $saleMovementCost = (int) round((float) (clone $saleCostLines)->whereNotNull('sml.unit_cost')
            ->selectRaw('COALESCE(SUM(ABS(sml.quantity_delta) * sml.unit_cost), 0) as total')->value('total'));
        $saleCostCoverage = (int) (clone $saleCostLines)->whereNull('sml.unit_cost')->count();
        $costSource = $saleCostLineCount > 0 && $saleCostCoverage === 0 && $ordersWithoutSaleMovement === 0
            ? 'sale_movements'
            : 'stock_rollforward';
        $costReliable = $costSource === 'sale_movements'
            || (! $negativeCostRollforward && $saleCostCoverage === 0 && $ordersWithoutSaleMovement === 0
                && ($salesCount === 0 || $saleCostLineCount > 0));
        $costOfGoodsSold = $costReliable
            ? ($costSource === 'sale_movements' ? $saleMovementCost : max(0, $rollforwardCost))
            : null;

        $payrollCosts = (int) round((float) DB::table('employee_payroll_payments')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount'));
        $commissionCosts = (int) DB::table('finance_commission_settlements')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount');
        $operatingExpenseQuery = DB::table('finance_expense_payments as p')
            ->join('finance_expense_requests as e', 'e.id', '=', 'p.expense_request_id')
            ->whereBetween('p.paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotExists(fn ($voided) => $voided->selectRaw('1')->from('finance_staff_advances as a')
                ->whereColumn('a.finance_expense_payment_id', 'p.id')->whereNotNull('a.voided_at'))
            ->whereNull('e.commission_user_id')
            ->where(fn ($query) => $query->whereNull('e.staff_advance_type')->orWhere('e.staff_advance_type', '<>', 'loan'))
            ->whereRaw("LOWER(e.category_name) NOT LIKE '%achat de stock%' AND LOWER(e.category_name) NOT LIKE '%marchandise%' AND LOWER(e.category_name) NOT LIKE '%approvisionnement%' AND LOWER(e.category_name) NOT LIKE '%investissement%' AND LOWER(e.category_name) NOT LIKE '%immobilisation%' AND LOWER(e.category_name) NOT LIKE '%achat de matériel%' AND LOWER(e.category_name) NOT LIKE '%retrait%' AND LOWER(e.category_name) NOT LIKE '%prélèvement%' AND LOWER(e.category_name) NOT LIKE '%emprunt%' AND LOWER(e.category_name) NOT LIKE '%tontine%' AND LOWER(e.category_name) NOT LIKE '%impôt sur le bénéfice%'");
        $expenseLines = (clone $operatingExpenseQuery)->select('e.category_name')->selectRaw('SUM(p.amount) as amount')->groupBy('e.category_name')->orderByDesc('amount')->get()
            ->map(fn ($row) => ['label' => $row->category_name, 'amount' => (int) $row->amount])->all();
        if ($payrollCosts > 0) {
            $expenseLines[] = ['label' => 'Salaires et avances au personnel', 'amount' => $payrollCosts];
        }
        if ($commissionCosts > 0) {
            $expenseLines[] = ['label' => 'Commissions réglées', 'amount' => $commissionCosts];
        }
        $operatingExpenses = $payrollCosts + $commissionCosts + (int) $operatingExpenseQuery->sum('p.amount');

        $cashReceipts = (int) DB::table('order_payments')->whereIn('status', ['collected', 'confirmed'])
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('collected_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->orWhere(function ($confirmed) use ($from, $to): void {
                        $confirmed->whereNull('collected_at')
                            ->whereBetween('confirmed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
                    });
            })->sum('amount');
        $cashAvailable = (int) DB::table('finance_cash_movements')->selectRaw(
            "COALESCE(SUM(CASE WHEN to_custody IN ('general_cash','shop','transit','bank') THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN from_custody IN ('general_cash','shop','transit','bank') THEN amount ELSE 0 END),0) as balance"
        )->value('balance');

        $grossMargin = $costReliable ? $netSales - $costOfGoodsSold : null;
        $operatingResult = $grossMargin === null ? null : $grossMargin - $operatingExpenses;
        $preTaxResult = $operatingResult === null ? null : $operatingResult - $otherExpenses + $otherIncome;
        $netResult = $preTaxResult === null ? null : $preTaxResult - $incomeTax;
        return [
            'gross_sales' => $grossSales, 'cancelled_sales' => $cancelledSales, 'discounts' => $discounts,
            'recorded_discounts' => $recordedDiscounts, 'manual_discounts' => $manualDiscounts,
            'net_sales' => $netSales, 'sales_count' => $salesCount, 'cash_receipts' => $cashReceipts,
            'receivables' => $receivables, 'opening_stock' => $openingStock['value'], 'purchases' => $purchases,
            'direct_supply_costs' => $directSupplyCosts, 'closing_stock' => $closingStock['value'],
            'cost_of_goods_sold' => $costOfGoodsSold, 'gross_margin' => $grossMargin,
            'operating_expenses' => $operatingExpenses, 'operating_expense_lines' => $expenseLines, 'other_expenses' => $otherExpenses,
            'other_income' => $otherIncome, 'pre_tax_result' => $preTaxResult, 'income_tax' => $incomeTax,
            'net_result' => $netResult, 'gross_margin_rate' => $grossMargin !== null && $netSales !== 0 ? round($grossMargin / $netSales * 100, 2) : null,
            'net_margin_rate' => $netResult !== null && $netSales !== 0 ? round($netResult / $netSales * 100, 2) : null,
            'cash_available' => $cashAvailable, 'missing_sale_cost_lines' => $saleCostCoverage,
            'missing_opening_unit_costs' => $openingStock['missing'], 'missing_closing_unit_costs' => $closingStock['missing'],
            'negative_opening_stock_balances' => $openingStock['negative'], 'negative_closing_stock_balances' => $closingStock['negative'],
            'estimated_cancellation_dates' => $estimatedCancellationDates,
            'negative_cost_rollforward' => $negativeCostRollforward,
            'cost_source' => $costSource,
            'sale_cost_line_count' => $saleCostLineCount,
            'orders_without_sale_movement' => $ordersWithoutSaleMovement,
            'cost_reliable' => $costReliable,
        ];
    }

    private function stockValueBefore(Carbon $date): array
    {
        return $this->stockValue('<', $date->copy()->startOfDay());
    }

    private function stockValueThrough(Carbon $date): array
    {
        return $this->stockValue('<=', $date->copy()->endOfDay());
    }

    private function stockValue(string $operator, Carbon $date): array
    {
        $balances = DB::table('stock_movement_lines as sml')->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sm.occurred_at', $operator, $date)
            ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as quantity')->groupBy('sml.product_id');
        $query = DB::query()->fromSub($balances, 'b')->join('products as p', 'p.id', '=', 'b.product_id');

        return [
            'value' => (int) round((float) (clone $query)->sum(DB::raw('b.quantity * COALESCE(p.price_market, 0)'))),
            'missing' => (int) (clone $query)->where('b.quantity', '>', 0)->whereNull('p.price_market')->count(),
            'negative' => (int) (clone $query)->where('b.quantity', '<', 0)->count(),
        ];
    }

    private function warnings(array $metrics): array
    {
        $warnings = [];
        if ($metrics['missing_sale_cost_lines'] > 0) {
            $warnings[] = $metrics['missing_sale_cost_lines'].' ligne(s) de sortie vendue(s) sans coût unitaire; vérifie le coût d’achat des produits.';
        }
        if ($metrics['missing_opening_unit_costs'] > 0 || $metrics['missing_closing_unit_costs'] > 0) {
            $warnings[] = 'Certains produits en stock n’ont pas de coût d’achat. Leur valeur de stock est incomplète.';
        }
        if ($metrics['negative_opening_stock_balances'] > 0 || $metrics['negative_closing_stock_balances'] > 0) {
            $warnings[] = 'Des produits ont un solde de stock négatif. Le stock initial ou final et le coût des ventes peuvent être incohérents.';
        }
        if ($metrics['negative_cost_rollforward'] && $metrics['cost_source'] !== 'sale_movements') {
            $warnings[] = 'La formule du coût des ventes donne un montant négatif avant correction à zéro. Le résultat affiché n’est pas fiable tant que le stock et les réceptions ne sont pas rapprochés.';
        }
        if ($metrics['sales_count'] > 0 && $metrics['sale_cost_line_count'] === 0) {
            $warnings[] = 'Aucune sortie de stock vendue ne correspond à la période. Le coût des marchandises et la marge ne peuvent pas être confirmés à partir des stocks.';
        }
        if ($metrics['orders_without_sale_movement'] > 0) {
            $warnings[] = $metrics['orders_without_sale_movement'].' commande(s) livrée(s) n’ont pas de sortie de stock enregistrée. Les ventes sont incluses au chiffre d’affaires, mais le coût des marchandises doit être rapproché.';
        }
        if (! $metrics['cost_reliable']) {
            $warnings[] = 'La marge et le résultat sont masqués, car les coûts de stock disponibles ne permettent pas un calcul fiable. Rapproche les sorties vendues et les coûts d’achat.';
        }
        if ($metrics['estimated_cancellation_dates'] > 0) {
            $warnings[] = $metrics['estimated_cancellation_dates'].' annulation(s) historique(s) utilisent la dernière date de modification comme approximation; les nouvelles annulations ont une date dédiée.';
        }
        $warnings[] = 'Les remises commerciales et frais directs d’approvisionnement ne sont pas structurés dans les ventes/réceptions : saisis-les dans les ajustements pour cette période.';
        $warnings[] = 'Le coût des stocks historiques utilise le coût d’achat actuellement enregistré sur chaque produit; les anciens coûts ne sont pas archivés dans les valorisations. Le résultat peut donc être estimatif si le coût a changé.';
        $warnings[] = 'Le détail des dépenses reprend les paiements effectués pendant la période; les dépenses engagées mais encore impayées ne sont pas incluses faute de date de charge fiable.';
        $warnings[] = 'La trésorerie affichée reprend les mouvements des caisses et comptes présents dans le journal financier. Les paiements Mobile Money ou FedaPay non rapprochés dans ce journal peuvent manquer.';
        $warnings[] = 'Les dépenses existantes ne sont pas encore classées automatiquement en charges fixes ou variables. Les ajustements ajoutés ici gardent cette distinction.';
        $warnings[] = 'Les impôts sur le bénéfice ne sont pas séparés automatiquement des autres taxes; saisis uniquement l’impôt sur le bénéfice dans les ajustements.';

        return $warnings;
    }

    private function exportRows(array $metrics): array
    {
        $labels = [
            'gross_sales' => 'Chiffre d’affaires brut', 'cancelled_sales' => 'Annulations / retours',
            'discounts' => 'Remises commerciales', 'net_sales' => 'Chiffre d’affaires net',
            'cash_receipts' => 'Encaissements clients', 'receivables' => 'Créances clients',
            'opening_stock' => 'Stock initial au coût d’achat', 'purchases' => 'Achats de marchandises',
            'direct_supply_costs' => 'Frais directs d’approvisionnement', 'closing_stock' => 'Stock final au coût d’achat',
            'cost_of_goods_sold' => 'Coût des marchandises vendues', 'gross_margin' => 'Marge brute',
            'operating_expenses' => 'Charges d’exploitation', 'other_expenses' => 'Autres charges',
            'other_income' => 'Autres produits', 'pre_tax_result' => 'Résultat avant impôts',
            'income_tax' => 'Impôts sur le bénéfice', 'net_result' => 'Résultat net',
            'cash_available' => 'Trésorerie disponible', 'gross_margin_rate' => 'Taux de marge brute (%)',
            'net_margin_rate' => 'Taux de rentabilité nette (%)',
        ];

        $rows = collect($labels)->map(fn ($label, $key) => [$label, $metrics[$key] ?? 'À rapprocher'])->values()->all();
        if (! empty($metrics['operating_expense_lines'])) {
            $rows[] = ['Détail des charges d’exploitation', ''];
            foreach ($metrics['operating_expense_lines'] as $line) {
                $rows[] = [$line['label'], $line['amount']];
            }
        }

        return $rows;
    }

    private function downloadXlsx(string $filename, array $rows): Response
    {
        abort_unless(class_exists(\ZipArchive::class), 500, 'L’extension PHP ZipArchive est requise pour exporter vers Excel.');
        $path = tempnam(sys_get_temp_dir(), 'tenacos-profit-');
        abort_if($path === false, 500, 'Impossible de préparer le fichier Excel.');
        $zip = new \ZipArchive();
        abort_unless($zip->open($path, \ZipArchive::OVERWRITE) === true, 500, 'Impossible de créer le fichier Excel.');
        $data = array_merge([['Indicateur', 'Montant (FCFA)']], $rows);
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($data as $ri => $row) {
            $rowNo = $ri + 1;
            $sheet .= '<row r="'.$rowNo.'">';
            foreach (array_values($row) as $ci => $value) {
                $cell = ($ci === 0 ? 'A' : 'B').$rowNo;
                if (is_numeric($value)) {
                    $sheet .= '<c r="'.$cell.'"><v>'.(float) $value.'</v></c>';
                } else {
                    $sheet .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Rentabilité" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }
}
