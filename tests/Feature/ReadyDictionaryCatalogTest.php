<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\ReadyDictionaries\ReadyDictionaryCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReadyDictionaryCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_100_english_words_dictionary_exists_with_words(): void
    {
        $dictionary = ReadyDictionary::query()
            ->where('name', '100 English words')
            ->where('language', 'English')
            ->firstOrFail();

        $this->assertNull($dictionary->level);
        $this->assertNull($dictionary->part_of_speech);
        $this->assertSame(100, $dictionary->words()->count());

        $this->assertDatabaseHas('ready_dictionary_words', [
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'Abandon',
            'translation' => 'Покидать, оставлять',
            'part_of_speech' => 'verb',
        ]);

        $this->assertDatabaseHas('ready_dictionary_words', [
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'Abundant',
            'translation' => 'Обильный, избыточный',
            'part_of_speech' => 'adjective',
        ]);

        $this->assertDatabaseHas('ready_dictionary_words', [
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'Bias',
            'translation' => 'Предвзятость',
            'part_of_speech' => 'noun',
        ]);
    }

    public function test_ready_dictionary_can_be_created_without_user_and_has_words(): void
    {
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Travel basics',
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'noun',
            'comment' => 'Starter travel vocabulary.',
        ]);

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'ticket',
            'translation' => 'билет',
            'part_of_speech' => 'noun',
            'comment' => 'Common travel word.',
        ]);

        $this->assertDatabaseHas('ready_dictionaries', [
            'name' => 'Travel basics',
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'noun',
        ]);

        $this->assertTrue($dictionary->words()->where('word', 'ticket')->exists());
    }

    public function test_ready_dictionary_delete_cascades_to_words(): void
    {
        $dictionary = ReadyDictionary::factory()
            ->has(ReadyDictionaryWord::factory()->count(2), 'words')
            ->create();

        $wordIds = $dictionary->words()->pluck('id')->all();

        $dictionary->delete();

        $this->assertDatabaseMissing('ready_dictionaries', [
            'id' => $dictionary->id,
        ]);

        foreach ($wordIds as $wordId) {
            $this->assertDatabaseMissing('ready_dictionary_words', [
                'id' => $wordId,
            ]);
        }
    }

    public function test_catalog_service_returns_all_ready_dictionaries_without_filters(): void
    {
        ReadyDictionary::factory()->create([
            'name' => 'English nouns',
            'language' => 'English',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'Spanish verbs',
            'language' => 'Spanish',
        ]);

        $catalog = app(ReadyDictionaryCatalogService::class)->catalog();

        $this->assertCount(3, $catalog['dictionaries']);
        $this->assertSame(['English', 'Spanish'], $catalog['filterOptions']['languages']);
        $this->assertSame('A0 Beginner', $catalog['filterOptions']['levels']['A0']);
        $this->assertSame('C2 Proficiency', $catalog['filterOptions']['levels']['C2']);
        $this->assertSame([
            'language' => null,
            'level' => null,
            'part_of_speech' => null,
        ], $catalog['selectedFilters']);
    }

    public function test_catalog_service_filters_by_language_level_and_part_of_speech(): void
    {
        $matchingDictionary = ReadyDictionary::factory()->create([
            'name' => 'English A1 nouns',
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'noun',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'English B1 nouns',
            'language' => 'English',
            'level' => 'B1',
            'part_of_speech' => 'noun',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'Spanish A1 nouns',
            'language' => 'Spanish',
            'level' => 'A1',
            'part_of_speech' => 'noun',
        ]);

        ReadyDictionary::factory()->create([
            'name' => 'English A1 verbs',
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'verb',
        ]);

        $catalog = app(ReadyDictionaryCatalogService::class)->catalog([
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'noun',
        ]);

        $this->assertCount(1, $catalog['dictionaries']);
        $this->assertTrue($catalog['dictionaries']->first()->is($matchingDictionary));
        $this->assertSame([
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'noun',
        ], $catalog['selectedFilters']);
    }

    public function test_catalog_service_ignores_unsupported_filters_without_breaking_query(): void
    {
        ReadyDictionary::factory()->create([
            'name' => 'English nouns',
            'language' => 'English',
            'level' => 'A1',
            'part_of_speech' => 'noun',
        ]);

        $catalog = app(ReadyDictionaryCatalogService::class)->catalog([
            'language' => 'Klingon',
            'level' => 'C9',
            'part_of_speech' => 'invalid',
        ]);

        $this->assertCount(2, $catalog['dictionaries']);
        $this->assertSame([
            'language' => null,
            'level' => null,
            'part_of_speech' => null,
        ], $catalog['selectedFilters']);
    }

    public function test_ready_dictionaries_route_passes_catalog_data_to_view(): void
    {
        $user = User::factory()->create();

        ReadyDictionary::factory()
            ->has(ReadyDictionaryWord::factory()->count(2), 'words')
            ->create([
                'name' => 'English nouns',
                'language' => 'English',
                'level' => 'A1',
                'part_of_speech' => 'noun',
            ]);

        $this->actingAs($user)
            ->get('/ready-dictionaries?language=English&level=A1&part_of_speech=noun')
            ->assertOk()
            ->assertSee('Ready dictionaries')
            ->assertSee('English nouns')
            ->assertSee(route('ready-dictionaries.show', ReadyDictionary::where('name', 'English nouns')->first()), false)
            ->assertSee('English')
            ->assertSee('2 words')
            ->assertSee('A1 Elementary')
            ->assertSee('Noun')
            ->assertSee('Language')
            ->assertSee('Level')
            ->assertSee('Part of speech')
            ->assertDontSee('New Dictionary')
            ->assertViewHas('readyDictionaries', fn ($dictionaries): bool => $dictionaries->count() === 1
                && $dictionaries->first()->words_count === 2)
            ->assertViewHas('filterOptions', fn (array $filterOptions): bool => $filterOptions['languages'] === ['English']
                && $filterOptions['levels']['A1'] === 'A1 Elementary'
                && array_key_exists('noun', $filterOptions['parts_of_speech']))
            ->assertViewHas('selectedFilters', [
                'language' => 'English',
                'level' => 'A1',
                'part_of_speech' => 'noun',
            ]);
    }

    public function test_ready_dictionary_show_page_displays_words_without_write_actions(): void
    {
        $user = User::factory()->create();
        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'My English',
            'language' => 'English',
        ]);
        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Travel Words',
            'language' => 'English',
        ]);
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Readonly English',
            'language' => 'English',
        ]);

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit.',
        ]);

        $this->actingAs($user)
            ->get(route('ready-dictionaries.show', $dictionary))
            ->assertOk()
            ->assertSee('Readonly English')
            ->assertSee('A ready dictionary in')
            ->assertSee('Word List')
            ->assertSee('apple')
            ->assertSee('яблоко')
            ->assertSee('Noun')
            ->assertSee('Fruit.')
            ->assertSee('Search word or translation...')
            ->assertSee('Action')
            ->assertSee('Add to dictionary')
            ->assertSee('Choose a personal dictionary for apple')
            ->assertSee('My English')
            ->assertSee('Travel Words')
            ->assertDontSee('Add Word')
            ->assertDontSee('Edit word')
            ->assertDontSee('Delete word');
    }

    public function test_ready_dictionary_word_can_be_copied_to_users_dictionary(): void
    {
        $user = User::factory()->create();
        $userDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'My English',
            'language' => 'English',
        ]);
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Readonly English',
            'language' => 'English',
        ]);
        $oldTimestamp = now()->subYear()->startOfSecond();

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit.',
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);

        $copiedAt = now()->startOfSecond();
        $this->travelTo($copiedAt);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ReadyDictionaries\Show::class, ['readyDictionary' => $dictionary])
            ->call('transferWordToDictionary', $readyWord->id, $userDictionary->id)
            ->assertSet('transferBannerType', 'success')
            ->assertSee('"apple" has been added to "My English".');

        $copiedWord = Word::query()
            ->where('word', 'apple')
            ->where('translation', 'яблоко')
            ->where('part_of_speech', 'noun')
            ->where('comment', 'Fruit.')
            ->firstOrFail();

        $this->assertSame($copiedAt->toDateTimeString(), $copiedWord->created_at?->toDateTimeString());
        $this->assertNotSame($oldTimestamp->toDateTimeString(), $copiedWord->created_at?->toDateTimeString());
        $this->assertDatabaseHas('user_dictionary_word', [
            'user_dictionary_id' => $userDictionary->id,
            'word_id' => $copiedWord->id,
        ]);

        $this->travelBack();
    }

    public function test_ready_dictionary_word_cannot_be_copied_to_another_users_dictionary(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign English',
            'language' => 'English',
        ]);
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Readonly English',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ReadyDictionaries\Show::class, ['readyDictionary' => $dictionary])
            ->call('transferWordToDictionary', $readyWord->id, $foreignDictionary->id)
            ->assertSet('transferBannerType', 'error')
            ->assertSee('We could not add this word to the selected dictionary. Please try again.');

        $this->assertDatabaseMissing('words', [
            'word' => 'apple',
            'translation' => 'яблоко',
        ]);
    }

    public function test_ready_dictionary_show_page_explains_when_user_has_no_personal_dictionaries(): void
    {
        $user = User::factory()->create();
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Readonly English',
            'language' => 'English',
        ]);

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $this->actingAs($user)
            ->get(route('ready-dictionaries.show', $dictionary))
            ->assertOk()
            ->assertSee('Create your own dictionary to add a word to it.');
    }

    public function test_ready_dictionary_show_component_filters_searches_and_paginates_words(): void
    {
        $user = User::factory()->create();
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Interactive English',
            'language' => 'English',
        ]);

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'created_at' => now()->subMinutes(3),
        ]);

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'run',
            'translation' => 'бежать',
            'part_of_speech' => 'verb',
            'created_at' => now()->subMinutes(2),
        ]);

        for ($i = 1; $i <= 21; $i++) {
            ReadyDictionaryWord::factory()->create([
                'ready_dictionary_id' => $dictionary->id,
                'word' => 'page-word-'.$i,
                'translation' => 'страница '.$i,
                'part_of_speech' => 'noun',
                'created_at' => now()->subMinutes(30 + $i),
            ]);
        }

        Livewire::actingAs($user)
            ->test(\App\Livewire\ReadyDictionaries\Show::class, ['readyDictionary' => $dictionary])
            ->assertSee('run')
            ->set('partOfSpeechFilter', 'verb')
            ->assertSee('run')
            ->assertDontSee('apple')
            ->set('partOfSpeechFilter', 'all')
            ->set('search', 'яблоко')
            ->call('applySearch')
            ->assertSee('apple')
            ->assertDontSee('run')
            ->set('search', '')
            ->call('applySearch')
            ->call('nextPage')
            ->assertSee('page-word-19');
    }
}
