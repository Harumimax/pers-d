<?php

namespace App\Providers;

use App\Services\Translation\MyMemoryTranslationService;
use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TranslationServiceInterface::class, MyMemoryTranslationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
