<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Services\ReadyDictionaries\ReadyDictionaryCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadyDictionaryCatalogTest extends TestCase
{
    use RefreshDatabase;

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

        $this->assertCount(2, $catalog['dictionaries']);
        $this->assertSame(['English', 'Spanish'], $catalog['filterOptions']['languages']);
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

        $this->assertCount(1, $catalog['dictionaries']);
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
            ->assertViewHas('readyDictionaries', fn ($dictionaries): bool => $dictionaries->count() === 1
                && $dictionaries->first()->words_count === 2)
            ->assertViewHas('filterOptions', fn (array $filterOptions): bool => $filterOptions['languages'] === ['English']
                && $filterOptions['levels'] === ['A1']
                && array_key_exists('noun', $filterOptions['parts_of_speech']))
            ->assertViewHas('selectedFilters', [
                'language' => 'English',
                'level' => 'A1',
                'part_of_speech' => 'noun',
            ]);
    }
}
