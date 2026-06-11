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
            'translation' => 'apple translation',
            'part_of_speech' => 'noun',
        ]);

        $existingWord = Word::query()->create([
            'word' => 'book',
            'translation' => 'book translation',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach([$backfillWord->id, $existingWord->id]);
        $existingWord->examples()->create([
            'example_text' => 'This is a book.',
            'example_translation' => 'Book example translation.',
            'sort_order' => 0,
            'source' => 'manual',
            'source_external_id' => '1',
        ]);

        $this->artisan('words:backfill-examples', ['--source' => 'user'])
            ->expectsOutputToContain('[1] user: apple')
            ->expectsOutputToContain('Backfill completed.')
            ->expectsOutputToContain('Processed: 1')
            ->expectsOutputToContain('Enriched: 1')
            ->expectsOutputToContain('Cleared: 0')
            ->expectsOutputToContain('Skipped existing: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('word_examples', [
            'exampleable_type' => Word::class,
            'exampleable_id' => $backfillWord->id,
            'example_text' => 'I eat an apple every morning.',
            'example_translation' => 'I eat an apple every morning in Russian.',
        ]);
    }

    public function test_command_backfills_examples_for_italian_user_dictionary_words(): void
    {
        $this->bindFakeExampleServices();

        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Italian Core',
            'language' => 'Italian',
        ]);

        $word = Word::query()->create([
            'word' => 'ciao',
            'translation' => 'hello',
            'part_of_speech' => 'interjection',
        ]);

        $dictionary->words()->attach([$word->id]);

        $this->artisan('words:backfill-examples', ['--source' => 'user'])
            ->expectsOutputToContain('[1] user: ciao')
            ->expectsOutputToContain('Processed: 1')
            ->expectsOutputToContain('Enriched: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('word_examples', [
            'exampleable_type' => Word::class,
            'exampleable_id' => $word->id,
            'example_text' => 'Ciao, come stai?',
            'example_translation' => 'Hello, how are you in Russian.',
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
            'translation' => 'airport translation',
            'part_of_speech' => 'noun',
        ]);

        $this->artisan('words:backfill-examples', ['--source' => 'ready'])
            ->expectsOutputToContain('[1] ready:')
            ->expectsOutputToContain('Backfill completed.')
            ->expectsOutputToContain('Cleared: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('word_examples', [
            'exampleable_type' => ReadyDictionaryWord::class,
            'exampleable_id' => $readyWord->id,
            'example_text' => 'The airport is close to the city.',
            'example_translation' => 'The airport is close to the city in Russian.',
        ]);
    }

    public function test_command_can_backfill_only_selected_ready_dictionary_by_id(): void
    {
        $this->bindFakeExampleServices();

        $targetDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $otherDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Other',
            'language' => 'English',
        ]);

        $targetWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $targetDictionary->id,
            'word' => 'airport',
            'translation' => 'airport translation',
            'part_of_speech' => 'noun',
        ]);

        $otherWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $otherDictionary->id,
            'word' => 'station',
            'translation' => 'station translation',
            'part_of_speech' => 'noun',
        ]);

        $this->artisan('words:backfill-examples', ['--source' => 'ready', '--id' => $targetDictionary->id])
            ->expectsOutputToContain('Starting word example backfill for source [ready]...')
            ->expectsOutputToContain('Dictionary id: '.$targetDictionary->id)
            ->expectsOutputToContain('[1] ready:')
            ->expectsOutputToContain('Processed: 1')
            ->expectsOutputToContain('Enriched: 1')
            ->assertExitCode(0);

        $this->assertSame(1, $targetWord->fresh()->examples()->count());
        $this->assertSame(0, $otherWord->fresh()->examples()->count());
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
            'translation' => 'apple translation',
            'part_of_speech' => 'noun',
        ]);

        $secondWord = Word::query()->create([
            'word' => 'book',
            'translation' => 'book translation',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach([$firstWord->id, $secondWord->id]);

        $this->artisan('words:backfill-examples', ['--source' => 'user', '--limit' => 1])
            ->expectsOutputToContain('[1] user:')
            ->expectsOutputToContain('Processed: 1')
            ->assertExitCode(0);

        $this->assertSame(1, $firstWord->fresh()->examples()->count() + $secondWord->fresh()->examples()->count());
    }

    public function test_command_can_clear_examples_for_selected_ready_dictionary_without_refill(): void
    {
        $this->bindFakeExampleServices();

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'English idioms',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'airport',
            'translation' => 'airport translation',
            'part_of_speech' => 'noun',
        ]);

        $readyWord->examples()->create([
            'example_text' => 'Old invalid example.',
            'example_translation' => 'Old invalid translation.',
            'sort_order' => 0,
            'source' => 'manual',
            'source_external_id' => 'legacy-1',
        ]);

        $this->artisan('words:backfill-examples', ['--source' => 'ready', '--id' => $readyDictionary->id, '--clear' => true])
            ->expectsOutputToContain('Clear mode: enabled')
            ->expectsOutputToContain('Clear completed.')
            ->expectsOutputToContain('Cleared: 1')
            ->expectsOutputToContain('Enriched: 0')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('word_examples', [
            'exampleable_type' => ReadyDictionaryWord::class,
            'exampleable_id' => $readyWord->id,
            'example_text' => 'Old invalid example.',
        ]);

        $this->assertSame(0, $readyWord->fresh()->examples()->count());
    }

    public function test_command_fails_for_invalid_source(): void
    {
        $this->artisan('words:backfill-examples', ['--source' => 'all'])
            ->expectsOutputToContain('Option --source must be either "user" or "ready".')
            ->assertExitCode(1);
    }

    public function test_command_fails_for_invalid_dictionary_id(): void
    {
        $this->artisan('words:backfill-examples', ['--id' => 0])
            ->expectsOutputToContain('Option --source is required when using --id or --clear.')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_id_is_used_without_source(): void
    {
        $this->artisan('words:backfill-examples', ['--id' => 7])
            ->expectsOutputToContain('Option --source is required when using --id or --clear.')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_clear_is_used_without_source_and_id(): void
    {
        $this->artisan('words:backfill-examples', ['--clear' => true])
            ->expectsOutputToContain('Option --source is required when using --id or --clear.')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_clear_is_used_without_id(): void
    {
        $this->artisan('words:backfill-examples', ['--source' => 'ready', '--clear' => true])
            ->expectsOutputToContain('Option --clear requires both --source and --id.')
            ->assertExitCode(1);
    }

    public function test_command_fails_for_non_positive_dictionary_id_with_source(): void
    {
        $this->artisan('words:backfill-examples', ['--source' => 'ready', '--id' => 0])
            ->expectsOutputToContain('Option --id must be a positive integer.')
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
                            'I eat an apple every morning in Russian.',
                            'tatoeba',
                            '101',
                        ),
                    ],
                    'airport' => [
                        new WordExampleData(
                            'The airport is close to the city.',
                            'The airport is close to the city in Russian.',
                            'tatoeba',
                            '202',
                        ),
                    ],
                    'station' => [
                        new WordExampleData(
                            'The station is busy today.',
                            'The station is busy today in Russian.',
                            'tatoeba',
                            '404',
                        ),
                    ],
                    'ciao' => [
                        new WordExampleData(
                            'Ciao, come stai?',
                            'Hello, how are you in Russian.',
                            'tatoeba',
                            '505',
                        ),
                    ],
                    'book' => [
                        new WordExampleData(
                            'This book is interesting.',
                            'This book is interesting in Russian.',
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
