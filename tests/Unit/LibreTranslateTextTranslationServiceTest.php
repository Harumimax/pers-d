<?php

namespace Tests\Unit;

use App\Services\Translation\LibreTranslateTextTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LibreTranslateTextTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.libretranslate.base_url' => 'http://localhost:5000',
            'services.libretranslate.timeout' => 10,
            'services.libretranslate.api_key' => null,
        ]);
    }

    public function test_service_sends_expected_payload_and_returns_translated_text(): void
    {
        Http::fake([
            'http://localhost:5000/translate' => Http::response([
                'translatedText' => 'Hola mundo',
            ]),
        ]);

        $service = $this->app->make(LibreTranslateTextTranslationService::class);
        $result = $service->translateText('Hello world', 'en', 'es');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://localhost:5000/translate'
                && $request['q'] === 'Hello world'
                && $request['source'] === 'en'
                && $request['target'] === 'es'
                && $request['format'] === 'text'
                && ! isset($request['alternatives']);
        });

        $this->assertSame('Hola mundo', $result);
    }
}
