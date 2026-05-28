<?php

namespace Tests\Unit;

use App\Services\Translation\LibreTranslateTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LibreTranslateTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.libretranslate.base_url' => 'http://localhost:5000',
            'services.libretranslate.timeout' => 10,
            'services.libretranslate.alternatives' => 3,
            'services.libretranslate.api_key' => null,
        ]);
    }

    public function test_service_sends_expected_payload_and_normalizes_primary_and_alternative_translations(): void
    {
        Http::fake([
            'http://localhost:5000/translate' => Http::response([
                'translatedText' => 'привет',
                'alternatives' => ['здравствуйте', 'добрый день'],
            ]),
        ]);

        $service = $this->app->make(LibreTranslateTranslationService::class);
        $result = $service->translate('hello', 'en', 'ru');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://localhost:5000/translate'
                && $request['q'] === 'hello'
                && $request['source'] === 'en'
                && $request['target'] === 'ru'
                && $request['format'] === 'text'
                && $request['alternatives'] === 3;
        });

        $this->assertSame([
            ['text' => 'привет', 'label' => 'top result'],
            ['text' => 'здравствуйте', 'label' => 'alternative'],
            ['text' => 'добрый день', 'label' => 'alternative'],
        ], $result->toArray());
    }

    public function test_service_filters_empty_values_and_duplicate_alternatives(): void
    {
        Http::fake([
            'http://localhost:5000/translate' => Http::response([
                'translatedText' => 'окно',
                'alternatives' => ['окно', '  ', 'форточка', null, 'форточка'],
            ]),
        ]);

        $service = $this->app->make(LibreTranslateTranslationService::class);
        $result = $service->translate('window', 'en', 'ru');

        $this->assertSame([
            ['text' => 'окно', 'label' => 'top result'],
            ['text' => 'форточка', 'label' => 'alternative'],
        ], $result->toArray());
    }

    public function test_service_can_return_only_alternatives_when_primary_translation_is_missing(): void
    {
        Http::fake([
            'http://localhost:5000/translate' => Http::response([
                'translatedText' => '',
                'alternatives' => ['гавань', 'порт'],
            ]),
        ]);

        $service = $this->app->make(LibreTranslateTranslationService::class);
        $result = $service->translate('harbor', 'en', 'ru');

        $this->assertSame([
            ['text' => 'гавань', 'label' => 'alternative'],
            ['text' => 'порт', 'label' => 'alternative'],
        ], $result->toArray());
    }

    public function test_service_includes_api_key_when_configured(): void
    {
        config([
            'services.libretranslate.api_key' => 'local-key',
            'services.libretranslate.alternatives' => 2,
        ]);

        Http::fake([
            'http://localhost:5000/translate' => Http::response([
                'translatedText' => 'дом',
            ]),
        ]);

        $service = $this->app->make(LibreTranslateTranslationService::class);
        $service->translate('house', 'en', 'ru');

        Http::assertSent(fn (Request $request): bool => $request['api_key'] === 'local-key'
            && $request['alternatives'] === 2);
    }
}
