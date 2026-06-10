<?php

namespace App\Providers;

use App\Services\AboutContact\AboutContactDeliveryServiceInterface;
use App\Services\Examples\ExampleProviderInterface;
use App\Services\Examples\TatoebaExampleProvider;
use App\Services\AboutContact\NotiSendAboutContactDeliveryService;
use App\Services\Translation\FailoverTextTranslationService;
use App\Services\Translation\FailoverTranslationService;
use App\Services\Translation\TextTranslationServiceInterface;
use App\Services\Translation\TranslatorTextTranslationService;
use App\Services\Translation\TranslatorTextTranslationServiceInterface;
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
        $this->app->bind(ExampleProviderInterface::class, TatoebaExampleProvider::class);
        $this->app->bind(TranslationServiceInterface::class, FailoverTranslationService::class);
        $this->app->bind(TextTranslationServiceInterface::class, FailoverTextTranslationService::class);
        $this->app->bind(TranslatorTextTranslationServiceInterface::class, TranslatorTextTranslationService::class);
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
