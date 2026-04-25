<?php

namespace App\Providers;

use App\Services\AboutContact\AboutContactDeliveryServiceInterface;
use App\Services\AboutContact\NotiSendAboutContactDeliveryService;
use App\Services\Translation\MyMemoryTranslationService;
use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AboutContactDeliveryServiceInterface::class, NotiSendAboutContactDeliveryService::class);
        $this->app->bind(TranslationServiceInterface::class, MyMemoryTranslationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('about-contact', function (Request $request): Limit {
            $userKey = $request->user()?->id !== null
                ? 'user:'.$request->user()->id
                : 'guest:'.$request->ip();

            return Limit::perMinute(3)->by($userKey);
        });
    }
}
