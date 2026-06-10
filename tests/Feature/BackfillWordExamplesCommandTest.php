<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Examples\Data\WordExampleData;
use App\Services\Examples\ExampleProviderInterface;
use App\Services\Translation\TextTranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillWordExamplesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_backfills_examples_for_user_words_only(): void
    {
        $this->bindFakeExampleServices();

        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $backfillWord = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $existingWord = Word::query()->create([
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach([$backfillWord->id, $existingWord->id]);
        $existingWord->examples()->create([
            'example_text' => 'This is a book.',
            'example_translation' => 'Это книга.',
            'sort_order' => 0,
            'source' => 'manual',
            'source_external_id' => '1',
        ]);

        $this->artisan('words:backfill-examples', ['--source' => 'user'])
            ->expectsOutputToContain('[1] user: apple')
            ->expectsOutputToContain('Backfill completed.')
            ->expectsOutputToContain('Processed: 1')
            ->expectsOutputToContain('Enriched: 1')
            ->expectsOutputToContain('Skipped existing: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('word_examples', [
            'exampleable_type' => Word::class,
            'exampleable_id' => $backfillWord->id,
            'example_text' => 'I eat an apple every morning.',
            'example_translation' => 'Я ем яблоко каждое утро.',
        ]);
    }

    public function test_command_backfills_examples_for_ready_words_only(): void
    {
        $this->bindFakeExampleServices();

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

        $this->artisan('words:backfill-examples', ['--source' => 'ready'])
            ->expectsOutputToContain('[1] ready:')
            ->expectsOutputToContain('Backfill completed.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('word_examples', [
            'exampleable_type' => ReadyDictionaryWord::class,
            'exampleable_id' => $readyWord->id,
            'example_text' => 'The airport is close to the city.',
            'example_translation' => 'Аэропорт находится рядом с городом.',
        ]);
    }

    public function test_command_respects_limit_option(): void
    {
        $this->bindFakeExampleServices();

        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $firstWord = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $secondWord = Word::query()->create([
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach([$firstWord->id, $secondWord->id]);

        $this->artisan('words:backfill-examples', ['--source' => 'user', '--limit' => 1])
            ->expectsOutputToContain('[1] user:')
            ->expectsOutputToContain('Processed: 1')
            ->assertExitCode(0);

        $this->assertSame(1, $firstWord->fresh()->examples()->count() + $secondWord->fresh()->examples()->count());
    }

    public function test_command_fails_for_invalid_source(): void
    {
        $this->artisan('words:backfill-examples', ['--source' => 'all'])
            ->expectsOutputToContain('Option --source must be either "user" or "ready".')
            ->assertExitCode(1);
    }

    private function bindFakeExampleServices(): void
    {
        $this->app->instance(ExampleProviderInterface::class, new class implements ExampleProviderInterface
        {
            public function fetchExamples(string $word, string $sourceLanguage, string $targetLanguage, int $limit = 3): array
            {
                return match ($word) {
                    'apple' => [
                        new WordExampleData(
                            'I eat an apple every morning.',
                            'Я ем яблоко каждое утро.',
                            'tatoeba',
                            '101',
                        ),
                    ],
                    'airport' => [
                        new WordExampleData(
                            'The airport is close to the city.',
                            'Аэропорт находится рядом с городом.',
                            'tatoeba',
                            '202',
                        ),
                    ],
                    'book' => [
                        new WordExampleData(
                            'This book is interesting.',
                            'Эта книга интересная.',
                            'tatoeba',
                            '303',
                        ),
                    ],
                    default => [],
                };
            }
        });

        $this->app->instance(TextTranslationServiceInterface::class, new class implements TextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                return '';
            }
        });
    }
}
