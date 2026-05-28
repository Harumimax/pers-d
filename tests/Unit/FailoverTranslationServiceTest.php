<?php

namespace Tests\Unit;

use App\Services\Translation\Data\TranslationResult;
use App\Services\Translation\Data\TranslationSuggestion;
use App\Services\Translation\FailoverTranslationService;
use App\Services\Translation\LibreTranslateTranslationService;
use App\Services\Translation\MyMemoryTranslationService;
use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class FailoverTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.translation.primary_provider' => 'libretranslate',
            'services.translation.fallback_provider' => 'mymemory',
            'services.translation.libretranslate_unhealthy_ttl_minutes' => 60,
        ]);

        Cache::forget('translation:libretranslate:unhealthy');
    }

    public function test_binding_resolves_translation_interface_to_failover_service(): void
    {
        $this->assertInstanceOf(FailoverTranslationService::class, $this->app->make(TranslationServiceInterface::class));
    }

    public function test_primary_provider_is_used_when_available(): void
    {
        $primary = Mockery::mock(LibreTranslateTranslationService::class);
        $fallback = Mockery::mock(MyMemoryTranslationService::class);

        $primary->shouldReceive('translate')
            ->once()
            ->with('hello', 'en', 'ru')
            ->andReturn(new TranslationResult([
                new TranslationSuggestion('привет', 'top result'),
            ]));

        $fallback->shouldNotReceive('translate');

        $service = new FailoverTranslationService($primary, $fallback, Cache::store());
        $result = $service->translate('hello', 'en', 'ru');

        $this->assertSame([
            ['text' => 'привет', 'label' => 'top result'],
        ], $result->toArray());

        $this->assertFalse(Cache::get('translation:libretranslate:unhealthy', false));
    }

    public function test_fallback_provider_is_used_and_primary_is_marked_unhealthy_when_primary_fails(): void
    {
        $primary = Mockery::mock(LibreTranslateTranslationService::class);
        $fallback = Mockery::mock(MyMemoryTranslationService::class);

        $primary->shouldReceive('translate')
            ->once()
            ->andThrow(new ConnectionException('LibreTranslate is unavailable'));

        $fallback->shouldReceive('translate')
            ->once()
            ->with('house', 'en', 'ru')
            ->andReturn(new TranslationResult([
                new TranslationSuggestion('дом', 'top result'),
            ]));

        $service = new FailoverTranslationService($primary, $fallback, Cache::store());
        $result = $service->translate('house', 'en', 'ru');

        $this->assertSame([
            ['text' => 'дом', 'label' => 'top result'],
        ], $result->toArray());

        $this->assertTrue(Cache::get('translation:libretranslate:unhealthy', false));
    }

    public function test_cached_unhealthy_primary_is_skipped_for_sixty_minutes_window(): void
    {
        Cache::put('translation:libretranslate:unhealthy', true, now()->addMinutes(60));

        $primary = Mockery::mock(LibreTranslateTranslationService::class);
        $fallback = Mockery::mock(MyMemoryTranslationService::class);

        $primary->shouldNotReceive('translate');

        $fallback->shouldReceive('translate')
            ->once()
            ->with('window', 'en', 'ru')
            ->andReturn(new TranslationResult([
                new TranslationSuggestion('окно', 'top result'),
            ]));

        $service = new FailoverTranslationService($primary, $fallback, Cache::store());
        $result = $service->translate('window', 'en', 'ru');

        $this->assertSame([
            ['text' => 'окно', 'label' => 'top result'],
        ], $result->toArray());
    }

    public function test_successful_but_empty_primary_result_does_not_trigger_fallback(): void
    {
        $primary = Mockery::mock(LibreTranslateTranslationService::class);
        $fallback = Mockery::mock(MyMemoryTranslationService::class);

        $primary->shouldReceive('translate')
            ->once()
            ->with('rare-term', 'en', 'ru')
            ->andReturn(new TranslationResult([]));

        $fallback->shouldNotReceive('translate');

        $service = new FailoverTranslationService($primary, $fallback, Cache::store());
        $result = $service->translate('rare-term', 'en', 'ru');

        $this->assertSame([], $result->toArray());
    }
}
