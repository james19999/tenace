<?php

namespace App\Services;

use App\Models\Orders\Order;
use App\Models\StockLocation;
use App\Models\User;

class ShopSalesLocationResolver
{
    public function forUser(?User $user, ?bool $wholesale = null): ?StockLocation
    {
        $rules = app(StockModuleSettingsService::class)->rules();
        $useWholesale = $wholesale ?? $user?->user_type === User::ROLE_COMMERCIAL_MANAGER;
        $locationId = $useWholesale
            ? ($rules['wholesale_sales_location_id'] ?? null)
            : null;

        if (! $useWholesale && $user) {
            if ($user->isManager()) {
                $managedShops = $user->responsibleStockLocations()
                    ->where('type', 'shop')->where('is_active', true)->get();
                $managedShop = $managedShops->firstWhere('id', (int) $user->primary_stock_location_id)
                    ?? $managedShops->first();
                $locationId = $managedShop?->id;
            }

            if (! $locationId && $user->primary_stock_location_id) {
                $locationId = $user->primary_stock_location_id;
            }
        }

        if (! $locationId && ! $useWholesale) {
            $locationId = $rules['default_sales_location_id'] ?? null;
        }

        return $locationId
            ? StockLocation::query()->whereKey($locationId)->where('type', 'shop')->where('is_active', true)->first()
            : null;
    }

    public function forOrder(Order $order, ?bool $wholesale = null): ?StockLocation
    {
        $ownerId = $order->created_user ?: $order->order_by;
        $owner = $ownerId ? User::query()->find($ownerId) : null;

        return $this->forUser($owner, $wholesale ?? (bool) $order->is_wholesale);
    }
}
