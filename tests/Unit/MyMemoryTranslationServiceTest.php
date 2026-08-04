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
                    'translatedText' => 'доброе утро',
                ],
                'matches' => [
                    [
                        'translation' => 'доброе утро',
                        'match' => 1,
                    ],
                    [
                        'translation' => 'здравствуйте',
                        'created-by' => 'tm',
                        'match' => 0.82,
                    ],
                    [
                        'translation' => 'утреннее приветствие',
                    ],
                ],
            ]),
        ]);

        $service = $this->app->make(MyMemoryTranslationService::class);
        $result = $service->translate('good morning', 'en', 'ru');

        $this->assertSame([
            ['text' => 'доброе утро', 'label' => 'top result'],
            ['text' => 'здравствуйте', 'label' => 'memory match'],
            ['text' => 'утреннее приветствие', 'label' => 'suggested'],
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
                        'translation' => 'потребитель',
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
                        'translation' => 'Отдел индекса потребительских цен',
                        'created-by' => 'tm',
                        'match' => 0.87,
                    ],
                ],
            ]),
        ]);

        $service = $this->app->make(MyMemoryTranslationService::class);
        $result = $service->translate('consumer', 'en', 'ru');

        $this->assertSame([
            ['text' => 'потребитель', 'label' => 'best match'],
            ['text' => 'Отдел индекса потребительских цен', 'label' => 'memory match'],
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
