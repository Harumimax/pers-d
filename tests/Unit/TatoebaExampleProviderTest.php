<?php

namespace Tests\Unit;

use App\Services\Examples\TatoebaExampleProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TatoebaExampleProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tatoeba.base_url' => 'https://api.tatoeba.org',
            'services.tatoeba.timeout' => 10,
            'services.tatoeba.examples_per_word' => 3,
            'services.tatoeba.requests_per_second' => 1,
        ]);
    }

    public function test_provider_requests_tatoeba_and_normalizes_examples_with_translations(): void
    {
        Http::fake([
            'https://api.tatoeba.org/v1/sentences*' => Http::response([
                'data' => [
                    [
                        'id' => 101,
                        'text' => '  I eat an apple every morning.  ',
                        'translations' => [
                            [
                                ['text' => ' Я ем яблоко каждое утро. '],
                            ],
                        ],
                    ],
                    [
                        'id' => 102,
                        'text' => 'The apple is on the table.',
                        'translations' => [
                            [
                                ['text' => 'Яблоко лежит на столе.'],
                            ],
                        ],
                    ],
                    [
                        'id' => 102,
                        'text' => 'The apple is on the table.',
                        'translations' => [
                            [
                                ['text' => 'Яблоко лежит на столе.'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = $this->app->make(TatoebaExampleProvider::class);
        $examples = $provider->fetchExamples('apple', 'en', 'ru', 2);

        Http::assertSent(function (Request $request): bool {
            return str_starts_with($request->url(), 'https://api.tatoeba.org/v1/sentences')
                && $request['lang'] === 'eng'
                && $request['q'] === 'apple'
                && $request['trans:lang'] === 'rus'
                && $request['showtrans:lang'] === 'rus'
                && $request['showtrans:is_direct'] === 'yes';
        });

        $this->assertCount(2, $examples);
        $this->assertSame('I eat an apple every morning.', $examples[0]->exampleText);
        $this->assertSame('Я ем яблоко каждое утро.', $examples[0]->exampleTranslation);
        $this->assertSame('101', $examples[0]->sourceExternalId);
        $this->assertSame('The apple is on the table.', $examples[1]->exampleText);
    }

    public function test_provider_returns_empty_array_for_unsupported_language(): void
    {
        Http::fake();

        $provider = $this->app->make(TatoebaExampleProvider::class);
        $examples = $provider->fetchExamples('bonjour', 'fr', 'ru', 3);

        Http::assertNothingSent();
        $this->assertSame([], $examples);
    }

    public function test_provider_keeps_example_when_partner_translation_is_missing(): void
    {
        Http::fake([
            'https://api.tatoeba.org/v1/sentences*' => Http::response([
                'data' => [
                    [
                        'id' => 301,
                        'text' => 'I drink water.',
                        'translations' => [],
                    ],
                ],
            ]),
        ]);

        $provider = $this->app->make(TatoebaExampleProvider::class);
        $examples = $provider->fetchExamples('water', 'en', 'ru', 3);

        $this->assertCount(1, $examples);
        $this->assertSame('I drink water.', $examples[0]->exampleText);
        $this->assertNull($examples[0]->exampleTranslation);
    }
}
