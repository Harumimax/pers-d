<?php

namespace Tests\Unit;

use App\Services\Translation\MyMemoryTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MyMemoryTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_normalizes_mymemory_response_into_unique_suggestions(): void
    {
        Http::fake([
            'https://api.mymemory.translated.net/get*' => Http::response([
                'responseData' => [
                    'translatedText' => 'РґРѕР±СЂРѕРµ СѓС‚СЂРѕ',
                ],
                'matches' => [
                    [
                        'translation' => 'РґРѕР±СЂРѕРµ СѓС‚СЂРѕ',
                        'match' => 1,
                    ],
                    [
                        'translation' => 'Р·РґСЂР°РІСЃС‚РІСѓР№С‚Рµ',
                        'created-by' => 'tm',
                        'match' => 0.82,
                    ],
                    [
                        'translation' => 'СѓС‚СЂРµРЅРЅРµРµ РїСЂРёРІРµС‚СЃС‚РІРёРµ',
                    ],
                ],
            ]),
        ]);

        $service = $this->app->make(MyMemoryTranslationService::class);
        $result = $service->translate('good morning', 'en', 'ru');

        $this->assertSame([
            ['text' => 'РґРѕР±СЂРѕРµ СѓС‚СЂРѕ', 'label' => 'top result'],
            ['text' => 'Р·РґСЂР°РІСЃС‚РІСѓР№С‚Рµ', 'label' => 'memory match'],
            ['text' => 'СѓС‚СЂРµРЅРЅРµРµ РїСЂРёРІРµС‚СЃС‚РІРёРµ', 'label' => 'suggested'],
        ], $result->toArray());
    }

    public function test_service_filters_out_non_russian_suggestions(): void
    {
        Http::fake([
            'https://api.mymemory.translated.net/get*' => Http::response([
                'responseData' => [
                    'translatedText' => 'consumer',
                ],
                'matches' => [
                    [
                        'translation' => 'РїРѕС‚СЂРµР±РёС‚РµР»СЊ',
                        'match' => 1,
                    ],
                    [
                        'translation' => 'Consumer Protection Law (2005).',
                        'created-by' => 'tm',
                        'match' => 0.99,
                    ],
                    [
                        'translation' => 'consumidor',
                        'created-by' => 'tm',
                        'match' => 0.95,
                    ],
                    [
                        'translation' => 'РћС‚РґРµР» РёРЅРґРµРєСЃР° РїРѕС‚СЂРµР±РёС‚РµР»СЊСЃРєРёС… С†РµРЅ',
                        'created-by' => 'tm',
                        'match' => 0.87,
                    ],
                ],
            ]),
        ]);

        $service = $this->app->make(MyMemoryTranslationService::class);
        $result = $service->translate('consumer', 'en', 'ru');

        $this->assertSame([
            ['text' => 'РїРѕС‚СЂРµР±РёС‚РµР»СЊ', 'label' => 'best match'],
            ['text' => 'РћС‚РґРµР» РёРЅРґРµРєСЃР° РїРѕС‚СЂРµР±РёС‚РµР»СЊСЃРєРёС… С†РµРЅ', 'label' => 'memory match'],
        ], $result->toArray());
    }
    public function test_service_filters_out_mixed_latin_and_cyrillic_suggestions(): void
    {
        Http::fake([
            'https://api.mymemory.translated.net/get*' => Http::response([
                'responseData' => [
                    'translatedText' => 'точный',
                ],
                'matches' => [
                    [
                        'translation' => 'точный',
                        'match' => 1,
                    ],
                    [
                        'translation' => 'точный (accurate)',
                        'created-by' => 'tm',
                        'match' => 0.95,
                    ],
                    [
                        'translation' => 'подходящий',
                    ],
                ],
            ]),
        ]);

        $service = $this->app->make(MyMemoryTranslationService::class);
        $result = $service->translate('accurate', 'en', 'ru');

        $this->assertSame([
            ['text' => 'точный', 'label' => 'top result'],
            ['text' => 'подходящий', 'label' => 'suggested'],
        ], $result->toArray());
    }
}

