<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Show;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DictionaryWordFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_list_can_be_filtered_by_part_of_speech(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish',
            'language' => 'Spanish',
        ]);

        $nounWord = Word::create([
            'word' => 'casa',
            'part_of_speech' => 'noun',
            'translation' => 'house',
            'comment' => null,
        ]);
        $verbWord = Word::create([
            'word' => 'hablar',
            'part_of_speech' => 'verb',
            'translation' => 'to speak',
            'comment' => null,
        ]);
        $legacyWord = Word::create([
            'word' => 'legacy',
            'part_of_speech' => null,
            'translation' => 'legacy',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$nounWord->id, $verbWord->id, $legacyWord->id]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->assertSeeHtml('Total words: <b> 3 </b>')
            ->assertSee('casa')
            ->assertSee('hablar')
            ->assertSee('legacy')
            ->set('partOfSpeechFilter', 'verb')
            ->assertSeeHtml('Total words: <b> 3 </b>')
            ->assertSee('hablar')
            ->assertDontSee('casa')
            ->assertDontSee('legacy')
            ->set('partOfSpeechFilter', 'all')
            ->assertSee('casa')
            ->assertSee('hablar')
            ->assertSee('legacy');
    }

    public function test_part_of_speech_filter_resets_pagination_and_works_with_sort(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        for ($index = 1; $index <= 21; $index++) {
            $word = Word::create([
                'word' => 'noun-'.$index,
                'part_of_speech' => 'noun',
                'translation' => 'translation-'.$index,
                'comment' => null,
            ]);

            $dictionary->words()->attach($word->id);
        }

        $zebra = Word::create([
            'word' => 'Zebra',
            'part_of_speech' => 'verb',
            'translation' => 'zebra',
            'comment' => null,
        ]);
        $alpha = Word::create([
            'word' => 'Alpha',
            'part_of_speech' => 'verb',
            'translation' => 'alpha',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$zebra->id, $alpha->id]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('gotoPage', 2)
            ->assertSee('Showing 21-23 of 23 words')
            ->set('partOfSpeechFilter', 'verb')
            ->assertSee('Showing 1-2 of 2 words')
            ->set('sort', 'a-z')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    public function test_search_finds_partial_matches_in_word_and_translation_within_selected_part_of_speech_filter(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Mixed',
            'language' => 'English',
        ]);

        $matchByWord = Word::create([
            'word' => 'one two three',
            'part_of_speech' => 'noun',
            'translation' => 'sequence',
            'comment' => null,
        ]);
        $matchByTranslation = Word::create([
            'word' => 'cuatro',
            'part_of_speech' => 'verb',
            'translation' => 'two directions',
            'comment' => null,
        ]);
        $nonMatch = Word::create([
            'word' => 'apple',
            'part_of_speech' => 'noun',
            'translation' => 'fruit',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$matchByWord->id, $matchByTranslation->id, $nonMatch->id]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('partOfSpeechFilter', 'verb')
            ->set('search', 'two')
            ->call('applySearch')
            ->assertSee('cuatro')
            ->assertDontSee('one two three')
            ->assertDontSee('apple')
            ->assertSee('Showing 1-1 of 1 words');
    }
}
