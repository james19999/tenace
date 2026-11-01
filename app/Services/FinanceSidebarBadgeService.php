<?php

namespace App\Services;

use App\Models\FinanceCashClosure;
use App\Models\FinanceCashRemittance;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceSupplierInvoice;
use App\Models\StockLocation;
use App\Models\User;

class FinanceSidebarBadgeService
{
    public function counts(User $user): array
    {
        $isAdmin = $user->isAdmin();
        $isAccountant = $user->isAccountant();
        $isManager = $user->isManager();
        $isWholesaleManager = $user->isWholesaleSalesRole();
        $isCashier = $user->hasRole(User::ROLE_CASHIER);
        $shopIds = StockLocation::query()->where('type', 'shop')
            ->where('responsible_user_id', $user->id)->select('id');
        if ($isWholesaleManager) {
            $wholesaleLocationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;
            if ($wholesaleLocationId) {
                $shopIds->union(StockLocation::query()->whereKey($wholesaleLocationId)->select('id'));
            }
        }

        $remittances = FinanceCashRemittance::query();
        if ($isAdmin) {
            $remittances->where(function ($query) use ($user): void {
                $query->whereIn('status', ['in_transit', 'discrepancy_open'])
                    ->orWhere(fn ($assigned) => $assigned->where('collector_id', $user->id)->where('status', 'awaiting_collector'));
            });
        } elseif ($isManager || $isWholesaleManager) {
            $remittances->where(function ($query) use ($user): void {
                $query->where(fn ($created) => $created->where('shop_manager_id', $user->id)
                    ->whereIn('status', ['awaiting_collector', 'in_transit', 'discrepancy_open']))
                    ->orWhere(fn ($assigned) => $assigned->where('collector_id', $user->id)
                        ->where('status', 'awaiting_collector'));
            });
        } else {
            $remittances->where('collector_id', $user->id)->where('status', 'awaiting_collector');
        }

        $expenses = FinanceExpenseRequest::query();
        if ($isAccountant) {
            $expenses->where('status', 'awaiting_finance')->where('requested_by', '<>', $user->id);
        } elseif ($isAdmin) {
            $expenses->where(function ($query) use ($user): void {
                $query->where(fn ($pending) => $pending->where('status', 'awaiting_director')->where('requested_by', '<>', $user->id))
                    ->orWhereIn('status', ['authorized_to_pay', 'partially_paid']);
            });
        } elseif ($isManager) {
            $expenses->where(function ($query) use ($shopIds, $user): void {
                $query->where(fn ($payable) => $payable->whereIn('stock_location_id', $shopIds)->whereIn('status', ['authorized_to_pay', 'partially_paid']))
                    ->orWhere(fn ($requested) => $requested->where('requested_by', $user->id)->whereIn('status', ['awaiting_finance', 'awaiting_director']));
            });
        } elseif ($isCashier) {
            $expenses->where(function ($query) use ($user): void {
                $query->where(fn ($payable) => $payable->whereNull('stock_location_id')->whereIn('status', ['authorized_to_pay', 'partially_paid']))
                    ->orWhere(fn ($requested) => $requested->where('requested_by', $user->id)->whereIn('status', ['awaiting_finance', 'awaiting_director']));
            });
        } else {
            $expenses->where('requested_by', $user->id)
                ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid']);
        }

        $invoices = FinanceSupplierInvoice::query();
        if ($isAdmin) {
            $invoices->where(function ($query) use ($user): void {
                $query->where(fn ($review) => $review->where('status', 'awaiting_review')->where('created_by', '<>', $user->id))
                    ->orWhere(fn ($payment) => $payment->where('status', 'approved')->whereIn('payment_status', ['unpaid', 'partial']));
            });
        } else {
            $invoices->whereRaw('1 = 0');
        }

        $closures = FinanceCashClosure::query()->where('status', 'awaiting_review')
            ->where('counted_by', '<>', $user->id);
        if (! $isAdmin && ! $isAccountant) {
            $closures->whereRaw('1 = 0');
        }

        $counts = [
            'treasury' => $closures->count(),
            'remittances' => $remittances->count(),
            'expenses' => $expenses->count(),
            'suppliers' => $invoices->count(),
        ];
        $counts['total'] = array_sum($counts);

        return $counts;
    }
}
