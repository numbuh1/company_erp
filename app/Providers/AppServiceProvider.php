<?php

namespace App\Providers;

use App\Translation\DatabaseLoader;
use Carbon\Carbon;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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
        $this->app->extend('translation.loader', function ($loader) {
            return new DatabaseLoader($loader);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('vi');

        if (str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        URL::forceRootUrl(config('app.url'));

        Event::listen(MessageSending::class, function (MessageSending $event) {
            $prefix = config('app.email_name');
            if ($prefix) {
                $subject = $event->message->getSubject() ?? '';
                $event->message->subject($prefix . ' | ' . $subject);
            }
        });

        View::share([
            'calWeekendBg'       => 'bg-gray-200 dark:bg-gray-900/50',
            'calHolidayBg'        => 'bg-red-50 dark:bg-red-900/20',
            'calOutsideBg'       => 'bg-gray-50 dark:bg-gray-900/40',
            'calWeekendHeaderCls'=> 'bg-gray-200 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500',
            'calHolidayHeaderCls' => 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-500',
        ]);
    }
}
