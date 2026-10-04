<?php

namespace App\Http\Livewire\Asides;

use Livewire\Component;
use App\Models\Orders\Order;
use Illuminate\Support\Str;

class Aside extends Component
{
    public static function isActive($routes = [], $paths = []): string
    {
        $routes = (array) $routes;
        $paths = (array) $paths;

        // 1. Check current route name (standard request)
        if (request()->route()) {
            $routeName = request()->route()->getName();
            if ($routeName && Str::is($routes, $routeName)) {
                return 'active';
            }
        }

        // 2. Check current request path
        $currentPath = trim(request()->path(), '/');
        foreach ($paths as $path) {
            $cleanPattern = trim($path, '/');
            if (Str::is($cleanPattern, $currentPath)) {
                return 'active';
            }
        }

        // 3. ONLY during Livewire background AJAX requests (e.g. wire:poll), check the Referer header
        // NEVER check Referer on standard GET requests to avoid keeping the old page's tab active
        if (request()->is('livewire/*')) {
            $referer = request()->header('referer');
            if ($referer) {
                $refererPath = trim(parse_url($referer, PHP_URL_PATH) ?? '', '/');
                if ($refererPath !== '') {
                    foreach ($paths as $path) {
                        $cleanPattern = trim($path, '/');
                        if (Str::is($cleanPattern, $refererPath)) {
                            return 'active';
                        }
                    }
                } elseif (in_array('Admin', $routes) || in_array('Admin', $paths)) {
                    return 'active';
                }
            }
        }

        return '';
    }

    public static function isDashboardActive(): string
    {
        return self::isActive(['Admin'], ['Admin', 'Admin/*']);
    }

    public static function isComptaActive(): string
    {
        return self::isActive(
            ['type-expensives*', 'expensives*', 'repport-expensives*', 'list-percent*', 'virementverify*', 'destroy-expensives*', 'typeexpensives*'],
            ['type/expensives*', 'expensives*', 'list/percent*', 'virement/auth/user/list*', 'erport/expensive/*']
        );
    }

    public static function isHistoryActive(): string
    {
        return self::isActive(
            ['history*', 'ordered-list-orderer*', 'order-report-list*', 'order-cancel-list*', 'trie-order-parther*', 'repport-order*', 'achat*'],
            ['history*', 'ordered/list/ordered*', 'order/report/list*', 'order/cancel/list*', 'trie/order/parther*', 'repport/order*', 'costumer/achat/order*']
        );
    }

    public static function isProductActive(): string
    {
        return self::isActive(
            ['product', 'productform', 'editproduct', 'updateproduct', 'delteproduct', 'show-product', 'rupture'],
            ['product/list', 'product/form', 'edit/product/*', 'show/*/product', 'rupture/products/stock']
        );
    }

    public static function isOrderManageActive(): string
    {
        return self::isActive(['order-liste-order', 'edit-order'], ['order/liste', 'edit/order/*']);
    }

    public static function isCartActive(): string
    {
        return self::isActive('productcart', ['product/cart']);
    }

    public static function isOrderActive(): string
    {
        return self::isActive(
            ['order', 'ordershow', 'check-type', 'orders.invoice'],
            ['order/list', 'order/show/*', 'orders/*/invoice']
        );
    }

    public static function isCustomerActive(): string
    {
        if (self::isActive(['costumer.follow-up'], ['costumer/follow-up*']) === 'active') {
            return '';
        }
        return self::isActive(
            ['costumer.index', 'costumer.show', 'costumer.edit', 'costumer.create', 'top-costumers', 'costumer.data', 'view-costumers', 'costumer.search'],
            ['costumer', 'costumer/create', 'costumer/*/edit', 'costumer/top', 'costumer/data', 'view/costumer/*']
        );
    }

    public static function isSavActive(): string
    {
        return self::isActive('costumer.follow-up', ['costumer/follow-up']);
    }

    public static function isRankingActive(): string
    {
        return self::isActive(['show-deliveries', 'ranking'], ['show/deliveries', 'ranking/ranking']);
    }

    public static function isDeliveryActive(): string
    {
        return self::isActive(['livrable', 'livrableshow'], ['livrable/list', 'livrable/show/*']);
    }

    public static function isCollapseActive($routes = [], $paths = []): bool
    {
        return self::isActive($routes, $paths) === 'active';
    }

    public function render()
    {
        return view('livewire.asides.aside', [
            'counts' => Order::where('brouillon', 0)->count('brouillon')
        ]);
    }
}
