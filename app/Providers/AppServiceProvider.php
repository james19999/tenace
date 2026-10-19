<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use App\Models\AppThemeSetting;
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
        Paginator::useBootstrap();
        view()->composer('layouts.admin', function ($view): void {
            $view->with('appTheme', AppThemeSetting::currentTheme());
        });
    }
}
