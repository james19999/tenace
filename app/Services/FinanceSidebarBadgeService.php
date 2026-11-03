<?php

namespace App\Services;

use App\Models\FinanceCashClosure;
use App\Models\FinanceCashRemittance;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceEquipmentPurchaseRequest;
use App\Models\FinanceGeneralCashSetting;
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
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        $isCashier = $cashierId && (int) $cashierId === (int) $user->id;
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
        if ($isCashier && ! $isAdmin) {
            $expenses->where(function ($query) use ($user, $isAccountant, $isManager, $shopIds): void {
                $query->whereIn('status', ['authorized_to_pay', 'partially_paid'])
                    ->orWhere(fn ($requested) => $requested->where('requested_by', $user->id)->whereIn('status', ['awaiting_finance', 'awaiting_director']));
                if ($isAccountant) {
                    $query->orWhere(fn ($review) => $review->where('status', 'awaiting_finance')->where('requested_by', '<>', $user->id));
                }
                if ($isManager) {
                    $query->orWhere(fn ($shopPayable) => $shopPayable->whereIn('stock_location_id', $shopIds)->whereIn('status', ['authorized_to_pay', 'partially_paid']));
                }
            });
        } elseif ($isAccountant) {
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
        } elseif ($isCashier) {
            $invoices->where(function ($query) use ($isAccountant, $user): void {
                $query->where(fn ($payment) => $payment->where('status', 'approved')->whereIn('payment_status', ['unpaid', 'partial']));
                if ($isAccountant) {
                    $query->orWhere(fn ($review) => $review->where('status', 'awaiting_review')->where('created_by', '<>', $user->id));
                }
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
            'treasury' => $isAdmin ? $closures->count() : 0,
            'remittances' => $remittances->count(),
            'expenses' => $expenses->count() + ($isCashier ? FinanceEquipmentPurchaseRequest::query()->where('status', 'authorized_to_pay')->count() : 0),
            'suppliers' => $invoices->count(),
        ];
        $counts['total'] = array_sum($counts);
        $counts['general_cashier'] = (bool) $isCashier;

        return $counts;
    }
}
