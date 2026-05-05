<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        if (str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        View::share([
            'calWeekendBg'       => 'bg-gray-200 dark:bg-gray-900/50',
            'calHolidayBg'        => 'bg-red-50 dark:bg-red-900/20',
            'calOutsideBg'       => 'bg-gray-50 dark:bg-gray-900/40',
            'calWeekendHeaderCls'=> 'bg-gray-200 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500',
            'calHolidayHeaderCls' => 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-500',
        ]);
    }
}
