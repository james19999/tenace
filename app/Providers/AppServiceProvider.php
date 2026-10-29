<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use App\Models\AppThemeSetting;
use App\Models\OrderPayment;
use App\Observers\OrderPaymentObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        OrderPayment::observe(OrderPaymentObserver::class);
        Paginator::useBootstrap();

        if (app()->environment('production') || request()->isSecure() || strtolower((string) request()->header('x-forwarded-proto')) === 'https') {
            URL::forceScheme('https');
        }

        view()->composer(['layouts.admin', 'auth.login', 'layouts.app', 'welcome', 'customer-portal.index'], function ($view): void {
            $view->with('appTheme', AppThemeSetting::currentTheme());
        });
    }
}
