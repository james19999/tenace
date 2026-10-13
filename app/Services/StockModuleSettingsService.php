<?php

namespace App\Services;

use App\Models\StockModuleSetting;
use Illuminate\Support\Facades\Auth;

class StockModuleSettingsService
{
    public function defaults(): array
    {
        return [
            'prevent_negative_stock' => true,
            'require_reason_for_outbound' => true,
            'adjustments_require_approval' => true,
            'allow_self_approval' => false,
            'second_count_on_difference' => true,
            'approval_threshold_quantity' => 0,
            'approval_threshold_value' => 0,
            'transfer_overdue_days' => 2,
            'expiry_alert_days' => 30,
            'inventory_interval_days' => 30,
            'loss_requires_attachment' => true,
            'default_sales_location_id' => null,
        ];
    }

    public function rules(): array
    {
        $stored = StockModuleSetting::query()->latest('id')->first()?->rules;

        return array_replace($this->defaults(), is_array($stored) ? $stored : []);
    }

    public function save(array $rules): StockModuleSetting
    {
        $setting = StockModuleSetting::query()->latest('id')->first() ?? new StockModuleSetting();
        $setting->rules = array_replace($this->defaults(), $rules);
        $setting->updated_by = Auth::id();
        $setting->save();

        return $setting;
    }
}
