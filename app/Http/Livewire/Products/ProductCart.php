<?php

namespace App\Http\Livewire\Products;

use Livewire\Component;
use App\Models\Product;
use App\Models\Costumer;
use App\Models\TransportCompany;
use App\Models\CostumerContactSetting;
use App\Models\ProductCountryPrice;
use App\Models\QuantityPromotionSetting;
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
    public $isWholesaleOrder = false;
    public $stockNotice = '';
    public $promotionSummary = '';
    public $promotionDiscountAmount = 0;
    public $cartRegularUnitPrices = [];
    public $cartPromotionLabels = [];

    public function mount()
    {
        $this->removeUnavailableWholesaleCartItems();
        $this->refreshOrderTypeAndPrices();
        $this->calculateTotal();
        $settings = CostumerContactSetting::first();
        $this->defaultFollowUpDays = $settings ? (int) $settings->default_follow_up_days : 14;
        $deliveryDate = Carbon::parse(old('date_order', now()->toDateString()));
        $this->defaultFollowUpAt = old(
            'follow_up_at',
            $deliveryDate->addDays($this->defaultFollowUpDays)->setTime(9, 0)->format('Y-m-d\\TH:i')
        );
    }

    private function removeUnavailableWholesaleCartItems(): void
    {
        if (auth()->user()?->user_type !== \App\Models\User::ROLE_COMMERCIAL_MANAGER) return;

        $locationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;
        foreach (Cart::instance('cart')->content() as $item) {
            $available = $locationId ? (int) DB::table('stock_movement_lines as sml')
                ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                ->where('sm.status', 'posted')->where('sml.location_id', $locationId)
                ->where('sml.product_id', $item->id)->sum('sml.quantity_delta') : 0;
            if ($available <= 0) Cart::instance('cart')->remove($item->rowId);
        }
    }

    public function updatedRemis()
    {
        $this->calculateTotal();
    }

    public function refreshPromotionState(): void
    {
        $this->refreshOrderTypeAndPrices();
        $this->calculateTotal();
    }

    private function refreshOrderTypeAndPrices(): void
    {
        if (auth()->user()?->user_type === \App\Models\User::ROLE_PARTNER) {
            $this->isWholesaleOrder = false;
            $this->promotionSummary = '';
            $this->promotionDiscountAmount = 0;
            $this->cartRegularUnitPrices = [];
            $this->cartPromotionLabels = [];
            return;
        }
        $cart = Cart::instance('cart');
        $groups = $cart->content()->groupBy(fn ($item) => (int) $item->id);
        $promotion = QuantityPromotionSetting::current();
        $this->promotionDiscountAmount = 0;
        $this->cartRegularUnitPrices = [];
        $this->cartPromotionLabels = [];
        $this->isWholesaleOrder = false;
        foreach ($groups as $productId => $items) {
            $quantity = (int) $items->sum('qty');
            $product = Product::query()->with('quantityPriceTiers')->find($productId);
            if ($product && $product->quantityPriceTiers->contains(fn ($tier) => $quantity >= (int) $tier->min_quantity)) {
                $this->isWholesaleOrder = true;
                break;
            }
        }

        foreach ($groups as $productId => $items) {
            $quantity = (int) $items->sum('qty');
            $product = Product::query()->with('quantityPriceTiers')->find($productId);
            if (! $product) continue;
            $hasWholesalePrice = $product->quantityPriceTiers->contains(
                fn ($tier) => $quantity >= (int) $tier->min_quantity
            );
            $basePrice = $hasWholesalePrice
                ? $product->unitPriceForQuantity($quantity)
                : (int) $product->price;
            // Le tarif grossiste atteint pour ce produit remplace la promotion.
            $promotionPricing = $hasWholesalePrice
                ? null
                : $promotion->priceAfterPromotionForProductQuantity((int) $productId, $quantity, $basePrice);
            $price = $basePrice;
            if ($promotionPricing) {
                $price = $promotionPricing['unit_price'];
                $this->cartRegularUnitPrices[(int) $productId] = $basePrice;
                $this->cartPromotionLabels[(int) $productId] = $promotionPricing['discount_type'] === 'percent'
                    ? '−'.$promotionPricing['discount_value'].' %'
                    : 'Prix fixe · '.number_format($price, 0, ',', ' ').' F / unité';
                $this->promotionDiscountAmount += max(0, $basePrice - $price) * $quantity;
            }
            foreach ($items as $item) {
                if ((float) $item->price === (float) $price) continue;
                $cart->update($item->rowId, ['price' => $price]);
            }
        }

        $this->promotionSummary = $this->promotionDiscountAmount > 0
            ? 'Prix promotionnels appliqués selon les quantités des produits concernés.'
            : '';
    }

    private function calculateTotal()
    {
        $subtotal = (float) Cart::instance('cart')->subtotal(2, '.', '');
        $discount = min(100, max(0, (float) $this->remis));

        $this->totals = $subtotal - ($subtotal * $discount / 100);
    }


    public function destroy ($rowId){
        Cart::instance('cart')->remove($rowId);
        $this->refreshOrderTypeAndPrices();
        $this->calculateTotal();
        $this->cartUpdated();
    }

    public function increment($rowId){
        if (auth()->user()?->user_type === \App\Models\User::ROLE_COMMERCIAL_MANAGER) return;
        $produit=Cart::instance('cart')->get($rowId);
        if (!$produit || !$produit->model) return;
        $qty=$produit->qty+1;
        $targetWholesale = $this->isWholesaleForCartWithQuantity($rowId, (int) $qty);
        if (! $targetWholesale && $qty > $this->availableShopQuantity($produit->model, false)) {
            $cartQuantity = (int) Cart::instance('cart')->content()
                ->where('id', $produit->id)->sum('qty');
            $nextTier = $produit->model->quantityPriceTiers()
                ->where('min_quantity', '>', $cartQuantity)
                ->orderBy('min_quantity')
                ->first();

            if ($nextTier) {
                $qty = (int) $produit->qty + (int) $nextTier->min_quantity - $cartQuantity;
                $targetWholesale = $this->isWholesaleForCartWithQuantity($rowId, (int) $qty);
            }
        }
        $available = $this->availableShopQuantity($produit->model, $targetWholesale);
        if ((int) $qty > $available) {
            $this->stockNotice = $this->stockLimitMessage($produit->model, $available, $targetWholesale);
            return;
        }
        $this->stockNotice = '';
        Cart::instance('cart')->update($rowId,$qty);
        $this->refreshOrderTypeAndPrices();
        $this->calculateTotal();
        $this->cartUpdated();
         }

    public function setQuantity(string $rowId, $quantity): void
    {
        $item = Cart::instance('cart')->get($rowId);
        if (! $item || ! $item->model) return;

        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
        if ($quantity === false) return;
        $quantity = max(1, (int) $quantity);
        if (auth()->user()?->user_type === \App\Models\User::ROLE_COMMERCIAL_MANAGER) {
            $minimum = (int) $item->model->quantityPriceTiers()->min('min_quantity');
            if ($minimum < 1 || $quantity < $minimum) {
                $this->stockNotice = $minimum > 0
                    ? 'La quantité minimale pour « '.$item->model->name.' » est de '.$minimum.' unités.'
                    : 'Aucun palier grossiste n’est configuré pour « '.$item->model->name.' ». Retire ce produit du panier.';
                return;
            }
        }
        $targetWholesale = $this->isWholesaleForCartWithQuantity($rowId, $quantity);
        $available = $this->availableShopQuantity($item->model, $targetWholesale);
        if ($quantity > $available) {
            $this->stockNotice = $this->stockLimitMessage($item->model, $available, $targetWholesale);
            if ($available < 1 || (auth()->user()?->user_type === \App\Models\User::ROLE_COMMERCIAL_MANAGER && $available < $minimum)) return;
            $quantity = $available;
        } else {
            $this->stockNotice = '';
        }
        if ($quantity < 1) return;

        Cart::instance('cart')->update($rowId, $quantity);
        $this->refreshOrderTypeAndPrices();
        $this->calculateTotal();
        $this->cartUpdated();
    }

    private function availableShopQuantity(Product $product, ?bool $wholesale = null): int
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
        $rules = app(StockModuleSettingsService::class)->rules();
        $isCommercialManager = auth()->user()?->user_type === \App\Models\User::ROLE_COMMERCIAL_MANAGER;
        $useWholesaleStock = $isCommercialManager || ($wholesale ?? $this->isWholesaleOrder);
        $locationId = $useWholesaleStock
            ? ($rules['wholesale_sales_location_id'] ?? null)
            : ($rules['default_sales_location_id'] ?? null);
        if (! $locationId) {
            return $useWholesaleStock ? 0 : (int) $product->qt_initial;
        }

        return (int) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sml.location_id', $locationId)
            ->where('sml.product_id', $product->id)->sum('sml.quantity_delta');
    }


    public function decrement($rowId){
        if (auth()->user()?->user_type === \App\Models\User::ROLE_COMMERCIAL_MANAGER) return;
        $produit=Cart::instance('cart')->get($rowId);
        if (!$produit || !$produit->model) return;
        $this->stockNotice = '';
        $qty=max(1, $produit->qty-1);

        $targetWholesale = $this->isWholesaleForCartWithQuantity($rowId, (int) $qty);
        if (! $targetWholesale && auth()->user()?->user_type !== \App\Models\User::ROLE_COMMERCIAL_MANAGER) {
            $mainShopQuantity = $this->availableShopQuantity($produit->model, false);
            if ($qty > $mainShopQuantity) {
                if ($mainShopQuantity < 1) {
                    $this->stockNotice = 'La quantité ne remplit plus le palier grossiste et la boutique principale n’a plus de stock disponible.';
                    return;
                }

                $qty = $mainShopQuantity;
                $this->stockNotice = 'Le palier grossiste n’est plus atteint. La quantité est revenue au stock disponible de la boutique principale ('.$mainShopQuantity.' unité(s)).';
            }
        }

        Cart::instance('cart')->update($rowId,$qty);
        $this->refreshOrderTypeAndPrices();
        $this->calculateTotal();
        $this->cartUpdated();
      }

    private function isWholesaleForCartWithQuantity(string $rowId, int $quantity): bool
    {
        if (auth()->user()?->user_type === \App\Models\User::ROLE_PARTNER) return false;
        foreach (Cart::instance('cart')->content()->groupBy(fn ($item) => (int) $item->id) as $productId => $items) {
            $total = (int) $items->sum(fn ($item) => $item->rowId === $rowId ? $quantity : (int) $item->qty);
            $product = Product::query()->with('quantityPriceTiers')->find($productId);
            if ($product && $product->quantityPriceTiers->contains(fn ($tier) => $total >= (int) $tier->min_quantity)) return true;
        }
        return false;
    }

    private function stockLimitMessage(Product $product, int $available, bool $wholesale): string
    {
        $store = $wholesale ? 'la boutique grossiste' : 'la boutique principale';
        return $available > 0
            ? 'Il ne reste que '.$available.' unité(s) de « '.$product->name.' » dans '.$store.'. La quantité a été limitée au stock disponible.'
            : 'Il n’y a plus de quantité disponible pour « '.$product->name.' » dans '.$store.'.';
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
        $isCommercialManager = $user?->user_type === \App\Models\User::ROLE_COMMERCIAL_MANAGER;
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
            'isCommercialManager' => $isCommercialManager,
            'partnerCountryCode' => $isPartner ? $user->country_code : null,
            'partnerCountry' => $isPartner ? (\App\Support\WestAfricaShippingCatalog::countries()[$user->country_code]['label'] ?? $user->country_code) : null,
            'partnerCities' => $isPartner ? (\App\Support\WestAfricaShippingCatalog::countries()[$user->country_code]['cities'] ?? []) : [],
            'partnerDeliveryFeeTotal' => $partnerDeliveryFeeTotal,
        ])
        ->extends('layouts.admin')
        ->section('content');

    }
}
