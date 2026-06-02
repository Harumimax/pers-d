<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Index;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserDictionaryGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_word_by_partial_match(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $matchingWord = Word::create([
            'word' => 'pineapple',
            'translation' => 'ананас',
            'part_of_speech' => 'noun',
            'comment' => 'tropical fruit',
        ]);

        $nonMatchingWord = Word::create([
            'word' => 'window',
            'translation' => 'окно',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$matchingWord->id, $nonMatchingWord->id]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'apple')
            ->call('searchWords')
            ->assertSee('Search results')
            ->assertSee('Pronounce')
            ->assertSee('pineapple')
            ->assertDontSee('window');
    }

    public function test_user_can_search_word_by_partial_translation_match(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        $matchingWord = Word::create([
            'word' => 'ventana',
            'translation' => 'окно в комнате',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $nonMatchingWord = Word::create([
            'word' => 'mesa',
            'translation' => 'стол',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$matchingWord->id, $nonMatchingWord->id]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'комнат')
            ->call('searchWords')
            ->assertSee('ventana')
            ->assertDontSee('mesa');
    }

    public function test_search_looks_through_all_current_users_dictionaries(): void
    {
        $user = User::factory()->create();
        $firstDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);
        $secondDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        $firstWord = Word::create([
            'word' => 'shared alpha',
            'translation' => 'первая метка',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);
        $secondWord = Word::create([
            'word' => 'shared beta',
            'translation' => 'вторая метка',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $firstDictionary->words()->attach($firstWord->id);
        $secondDictionary->words()->attach($secondWord->id);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'shared')
            ->call('searchWords')
            ->assertSee('shared alpha')
            ->assertSee('shared beta')
            ->assertSee('English Core')
            ->assertSee('Spanish Travel');
    }

    public function test_search_does_not_show_words_from_other_users_dictionaries(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'My Dictionary',
            'language' => 'English',
        ]);
        $foreignDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Hidden Dictionary',
            'language' => 'English',
        ]);

        $ownWord = Word::create([
            'word' => 'orbit own',
            'translation' => 'моя орбита',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);
        $foreignWord = Word::create([
            'word' => 'orbit foreign',
            'translation' => 'чужая орбита',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $ownDictionary->words()->attach($ownWord->id);
        $foreignDictionary->words()->attach($foreignWord->id);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'orbit')
            ->call('searchWords')
            ->assertSee('orbit own')
            ->assertDontSee('orbit foreign')
            ->assertDontSee('Hidden Dictionary');
    }

    public function test_search_does_not_show_ready_dictionary_words(): void
    {
        $user = User::factory()->create();

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => ReadyDictionary::factory()->create([
                'name' => 'Ready Travel',
                'language' => 'English',
            ])->id,
            'word' => 'ready-orchid',
            'translation' => 'готовая орхидея',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'ready-orchid')
            ->call('searchWords')
            ->assertSee('No such words were found in your dictionaries.')
            ->assertDontSee('Ready Travel')
            ->assertDontSee('ready-orchid');
    }

    public function test_empty_state_is_shown_when_nothing_is_found(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'sun',
            'translation' => 'солнце',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'moonlight')
            ->call('searchWords')
            ->assertSee('No such words were found in your dictionaries.');
    }

    public function test_dictionary_list_remains_visible_after_search_results(): void
    {
        $user = User::factory()->create();
        $firstDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);
        $secondDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish Travel',
            'language' => 'Spanish',
        ]);

        $firstDictionary->words()->attach(Word::create([
            'word' => 'harbor',
            'translation' => 'гавань',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]));

        $response = $this->actingAs($user)->get(route('dictionaries.index'));
        $response->assertOk();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'harbor')
            ->call('searchWords')
            ->assertSee('harbor')
            ->assertSee('English Core')
            ->assertSee('Spanish Travel');
    }
}
