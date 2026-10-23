<?php

namespace App\Http\Livewire\Products;

use Livewire\Component;
use App\Models\Product;
use App\Models\Costumer;
use App\Models\TransportCompany;
use App\Models\CostumerContactSetting;
use App\Models\ProductCountryPrice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\StockModuleSettingsService;
use Gloudemans\Shoppingcart\Facades\Cart;

class ProductCart extends Component
{
    public $remis = 0;
    public $totals = 0;
    public $defaultFollowUpDays = 14;
    public $defaultFollowUpAt;

    public function mount()
    {
        $this->calculateTotal();
        $settings = CostumerContactSetting::first();
        $this->defaultFollowUpDays = $settings ? (int) $settings->default_follow_up_days : 14;
        $deliveryDate = Carbon::parse(old('date_order', now()->toDateString()));
        $this->defaultFollowUpAt = old(
            'follow_up_at',
            $deliveryDate->addDays($this->defaultFollowUpDays)->setTime(9, 0)->format('Y-m-d\\TH:i')
        );
    }

    public function updatedRemis()
    {
        $this->calculateTotal();
    }

    private function calculateTotal()
    {
        $subtotal = (float) Cart::instance('cart')->subtotal(2, '.', '');
        $discount = min(100, max(0, (float) $this->remis));

        $this->totals = $subtotal - ($subtotal * $discount / 100);
    }


    public function destroy ($rowId){
        Cart::instance('cart')->remove($rowId);
        $this->calculateTotal();
        $this->cartUpdated();
    }

    public function increment($rowId){
        $produit=Cart::instance('cart')->get($rowId);
        if (!$produit || !$produit->model || (int) $produit->qty >= $this->availableShopQuantity($produit->model)) {
            return;
        }
        $qty=$produit->qty+1;
        Cart::instance('cart')->update($rowId,$qty);
        $this->calculateTotal();
        $this->cartUpdated();
         }

    private function availableShopQuantity(Product $product): int
    {
        if (auth()->user()?->user_type === \App\Models\User::ROLE_PARTNER) {
            $partner = auth()->user();
            $countryCode = strtoupper((string) $partner->country_code);
            $reserve = (int) \App\Models\PartnerProductStock::query()->where('partner_id', $partner->id)
                ->where('country_code', $countryCode)->where('product_id', $product->id)->value('quantity');
            $withCouriers = (int) DB::table('partner_courier_stock_issue_lines as lines')
                ->join('partner_courier_stock_issues as issues', 'issues.id', '=', 'lines.issue_id')
                ->where('issues.partner_id', $partner->id)->where('issues.country_code', $countryCode)
                ->where('lines.product_id', $product->id)->sum('lines.quantity_remaining');

            return $reserve + $withCouriers;
        }
        $locationId = app(StockModuleSettingsService::class)->rules()['default_sales_location_id'] ?? null;
        if (! $locationId) {
            return (int) $product->qt_initial;
        }

        return (int) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sml.location_id', $locationId)
            ->where('sml.product_id', $product->id)->sum('sml.quantity_delta');
    }


    public function decrement($rowId){

        $produit=Cart::instance('cart')->get($rowId);
        $qty=max(1, $produit->qty-1);
        Cart::instance('cart')->update($rowId,$qty);
        $this->calculateTotal();
        $this->cartUpdated();
      }

    private function cartUpdated(): void
    {
        $count = (int) Cart::instance('cart')->count();
        $this->emit('cartUpdated');
        $this->dispatchBrowserEvent('cart-count-updated', ['count' => $count]);
    }

       public function total() {
        session()->put('subtotal', Cart::instance('cart')->subtotal(2, '.', ''));
       }

    public function render()
    {
        $user = auth()->user();
        $isPartner = $user?->user_type === \App\Models\User::ROLE_PARTNER;
        $partnerCartItems = Cart::instance('cart')->content();
        $partnerDeliveryFeeTotal = 0;
        return view('livewire.products.product-cart', [
            'selectedCostumer' => old('costumer_id') && ! in_array(old('costumer_id'), ['default', 'anonymous'], true)
                ? Costumer::find(old('costumer_id'))
                : null,
            'hasNamedCustomers' => Costumer::query()
                ->where('name', 'not like', 'CL-%')
                ->where('name', 'not like', 'Client anonyme · CL-%')
                ->exists(),
            'defaultFollowUpDays' => $this->defaultFollowUpDays,
            'defaultFollowUpAt' => $this->defaultFollowUpAt,
            'transportCompanies' => TransportCompany::query()->orderBy('name')->pluck('name'),
            'isPartner' => $isPartner,
            'partnerCountryCode' => $isPartner ? $user->country_code : null,
            'partnerCountry' => $isPartner ? (\App\Support\WestAfricaShippingCatalog::countries()[$user->country_code]['label'] ?? $user->country_code) : null,
            'partnerCities' => $isPartner ? (\App\Support\WestAfricaShippingCatalog::countries()[$user->country_code]['cities'] ?? []) : [],
            'partnerDeliveryFeeTotal' => $partnerDeliveryFeeTotal,
        ])
        ->extends('layouts.admin')
        ->section('content');

    }
}
