<?php

namespace Tests\Unit;

use App\Services\Translation\FailoverTextTranslationService;
use App\Services\Translation\LibreTranslateTextTranslationService;
use App\Services\Translation\MyMemoryTextTranslationService;
use App\Services\Translation\TextTranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class FailoverTextTranslationServiceTest extends TestCase
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

    public function test_binding_resolves_text_translation_interface_to_failover_service(): void
    {
        $this->assertInstanceOf(FailoverTextTranslationService::class, $this->app->make(TextTranslationServiceInterface::class));
    }

    public function test_primary_provider_is_used_for_text_translation_when_available(): void
    {
        $primary = Mockery::mock(LibreTranslateTextTranslationService::class);
        $fallback = Mockery::mock(MyMemoryTextTranslationService::class);

        $primary->shouldReceive('translateText')
            ->once()
            ->with('Hello world', 'en', 'es')
            ->andReturn('Hola mundo');

        $fallback->shouldNotReceive('translateText');

        $service = new FailoverTextTranslationService($primary, $fallback, Cache::store());

        $this->assertSame('Hola mundo', $service->translateText('Hello world', 'en', 'es'));
    }

    public function test_fallback_provider_is_used_for_text_translation_when_primary_fails(): void
    {
        $primary = Mockery::mock(LibreTranslateTextTranslationService::class);
        $fallback = Mockery::mock(MyMemoryTextTranslationService::class);

        $primary->shouldReceive('translateText')
            ->once()
            ->andThrow(new ConnectionException('LibreTranslate is unavailable'));

        $fallback->shouldReceive('translateText')
            ->once()
            ->with('Hello world', 'en', 'es')
            ->andReturn('Hola mundo');

        $service = new FailoverTextTranslationService($primary, $fallback, Cache::store());

        $this->assertSame('Hola mundo', $service->translateText('Hello world', 'en', 'es'));
        $this->assertTrue(Cache::get('translation:libretranslate:unhealthy', false));
    }
}
