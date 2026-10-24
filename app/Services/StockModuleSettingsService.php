<?php

namespace App\Services;

use App\Models\StockModuleSetting;
use App\Models\StockLocation;
use Illuminate\Support\Facades\Auth;

class StockModuleSettingsService
{
    public function defaults(): array
    {
        return [
            'prevent_negative_stock' => true,
            'require_reason_for_outbound' => true,
            'adjustments_require_approval' => false,
            'allow_self_approval' => false,
            'second_count_on_difference' => false,
            'approval_threshold_quantity' => 0,
            'approval_threshold_value' => 0,
            'transfer_overdue_days' => 2,
            'expiry_alert_days' => 30,
            'inventory_interval_days' => 30,
            'loss_requires_attachment' => true,
            'default_sales_location_id' => null,
            'wholesale_sales_location_id' => null,
        ];
    }

    public function rules(): array
    {
        $stored = StockModuleSetting::query()->latest('id')->first()?->rules;
        $rules = array_replace($this->defaults(), is_array($stored) ? $stored : []);
        $rules['adjustments_require_approval'] = false;
        $rules['second_count_on_difference'] = false;

        $configuredShop = ! empty($rules['default_sales_location_id'])
            ? StockLocation::query()->whereKey($rules['default_sales_location_id'])
                ->where('type', 'shop')->where('is_active', true)->first()
            : null;

        if ($configuredShop) {
            $rules['default_sales_location_id'] = (int) $configuredShop->id;
        } else {
            $activeShops = StockLocation::query()->where('type', 'shop')->where('is_active', true)->limit(2)->get(['id']);
            $rules['default_sales_location_id'] = $activeShops->count() === 1 ? (int) $activeShops->first()->id : null;
        }

        $wholesaleShop = ! empty($rules['wholesale_sales_location_id'])
            ? StockLocation::query()->whereKey($rules['wholesale_sales_location_id'])
                ->where('type', 'shop')->where('is_active', true)->first()
            : null;
        $rules['wholesale_sales_location_id'] = $wholesaleShop
            && (int) $wholesaleShop->id !== (int) ($rules['default_sales_location_id'] ?? 0)
                ? (int) $wholesaleShop->id
                : null;

        return $rules;
    }

    public function save(array $rules): StockModuleSetting
    {
        $setting = StockModuleSetting::query()->latest('id')->first() ?? new StockModuleSetting();
        $storedRules = is_array($setting->rules) ? $setting->rules : [];
        $setting->rules = array_replace($this->defaults(), $storedRules, $rules, [
            'adjustments_require_approval' => false,
            'second_count_on_difference' => false,
        ]);
        $setting->updated_by = Auth::id();
        $setting->save();

        return $setting;
    }
}
