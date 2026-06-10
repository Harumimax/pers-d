<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Examples\Data\WordExampleData;
use App\Services\Examples\ExampleEnrichmentService;
use App\Services\Examples\ExampleProviderInterface;
use App\Services\Dictionaries\CopyWordToUserDictionaryService;
use App\Services\Dictionaries\SaveDictionaryWordService;
use App\Services\Translation\TextTranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordExamplesDomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_examples_can_be_attached_to_user_and_ready_dictionary_words(): void
    {
        $user = User::factory()->create();

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $userDictionary->words()->attach($word->id);

        $userExample = $word->examples()->create([
            'example_text' => 'I eat an apple every morning.',
            'example_translation' => 'Я ем яблоко каждое утро.',
            'sort_order' => 0,
            'source' => 'tatoeba',
            'source_external_id' => '101',
        ]);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        $readyExample = $readyWord->examples()->create([
            'example_text' => 'The airport is close to the city.',
            'example_translation' => 'Аэропорт находится рядом с городом.',
            'sort_order' => 0,
            'source' => 'tatoeba',
            'source_external_id' => '202',
        ]);

        $this->assertTrue($word->examples->contains($userExample));
        $this->assertTrue($readyWord->examples->contains($readyExample));
        $this->assertSame($word->id, $userExample->exampleable?->id);
        $this->assertSame($readyWord->id, $readyExample->exampleable?->id);
    }

    public function test_example_enrichment_service_replaces_existing_examples_for_word(): void
    {
        $provider = new class implements ExampleProviderInterface
        {
            public function fetchExamples(string $word, string $sourceLanguage, string $targetLanguage, int $limit = 3): array
            {
                return [
                    new WordExampleData(
                        'I eat an apple every morning.',
                        'Я ем яблоко каждое утро.',
                        'tatoeba',
                        '101',
                    ),
                    new WordExampleData(
                        'The apple is on the table.',
                        'Яблоко лежит на столе.',
                        'tatoeba',
                        '102',
                    ),
                ];
            }
        };

        $textTranslationService = new class implements TextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                return '';
            }
        };

        $service = new ExampleEnrichmentService($provider, $textTranslationService);
        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $word->examples()->create([
            'example_text' => 'Old example.',
            'example_translation' => 'Старый пример.',
            'sort_order' => 0,
            'source' => 'legacy',
            'source_external_id' => '1',
        ]);

        $stored = $service->fetchAndStoreForWord($word, 'en', 'ru');

        $this->assertCount(2, $stored);
        $this->assertSame(2, $word->fresh()->examples()->count());
        $this->assertSame(
            ['I eat an apple every morning.', 'The apple is on the table.'],
            $word->fresh()->examples()->pluck('example_text')->all(),
        );
    }

    public function test_example_enrichment_service_uses_text_translation_fallback_when_partner_translation_is_missing(): void
    {
        $provider = new class implements ExampleProviderInterface
        {
            public function fetchExamples(string $word, string $sourceLanguage, string $targetLanguage, int $limit = 3): array
            {
                return [
                    new WordExampleData(
                        'I eat an apple every morning.',
                        null,
                        'tatoeba',
                        '101',
                    ),
                ];
            }
        };

        $textTranslationService = new class implements TextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                return 'Я ем яблоко каждое утро.';
            }
        };

        $service = new ExampleEnrichmentService($provider, $textTranslationService);
        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $stored = $service->fetchAndStoreForWord($word, 'en', 'ru');

        $this->assertCount(1, $stored);
        $this->assertSame('Я ем яблоко каждое утро.', $stored[0]->example_translation);
        $this->assertSame('Я ем яблоко каждое утро.', $word->fresh()->examples()->value('example_translation'));
    }

    public function test_save_dictionary_word_service_persists_examples_for_new_word(): void
    {
        $this->app->instance(ExampleProviderInterface::class, new class implements ExampleProviderInterface
        {
            public function fetchExamples(string $word, string $sourceLanguage, string $targetLanguage, int $limit = 3): array
            {
                return [
                    new WordExampleData(
                        'I eat an apple every morning.',
                        null,
                        'tatoeba',
                        '101',
                    ),
                ];
            }
        });

        $this->app->instance(TextTranslationServiceInterface::class, new class implements TextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                return 'Я ем яблоко каждое утро.';
            }
        });

        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = $this->app->make(SaveDictionaryWordService::class)->save(
            $dictionary,
            'apple',
            'яблоко',
            'noun',
        );

        $this->assertTrue($dictionary->words()->whereKey($word->id)->exists());
        $this->assertSame('Я ем яблоко каждое утро.', $word->fresh()->examples()->value('example_translation'));
    }

    public function test_copy_word_to_user_dictionary_service_copies_existing_examples_without_new_api_fetch(): void
    {
        $this->app->instance(ExampleProviderInterface::class, new class implements ExampleProviderInterface
        {
            public function fetchExamples(string $word, string $sourceLanguage, string $targetLanguage, int $limit = 3): array
            {
                throw new \RuntimeException('Provider should not be called when source examples exist.');
            }
        });

        $this->app->instance(TextTranslationServiceInterface::class, new class implements TextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                throw new \RuntimeException('Translator should not be called when source examples exist.');
            }
        });

        $owner = User::factory()->create();
        $subscriber = User::factory()->create();

        $targetDictionary = UserDictionary::query()->create([
            'user_id' => $subscriber->id,
            'name' => 'Imported Words',
            'language' => 'English',
        ]);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        $readyWord->examples()->create([
            'example_text' => 'The airport is close to the city.',
            'example_translation' => 'Аэропорт находится рядом с городом.',
            'sort_order' => 0,
            'source' => 'tatoeba',
            'source_external_id' => '202',
        ]);

        $copiedWord = $this->app->make(CopyWordToUserDictionaryService::class)->copy(
            $targetDictionary,
            [
                'word' => 'airport',
                'translation' => 'аэропорт',
                'part_of_speech' => 'noun',
                'comment' => null,
                'source_language' => 'en',
            ],
            $readyWord,
        );

        $this->assertSame(
            'Аэропорт находится рядом с городом.',
            $copiedWord->fresh()->examples()->value('example_translation'),
        );
    }
}
