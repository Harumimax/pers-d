<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Favorites;
use App\Livewire\Dictionaries\Index;
use App\Livewire\Dictionaries\Show;
use App\Models\FavoriteWord;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FavoriteWordsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dictionaries_index_shows_non_clickable_favorites_card_when_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dictionaries.index'));

        $response
            ->assertOk()
            ->assertSee('Favorite Words')
            ->assertSee('No favorite words yet: 0')
            ->assertDontSee(route('dictionaries.favorites'), false);
    }

    public function test_dictionaries_index_shows_clickable_favorites_card_when_user_has_favorites(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach($word->id);

        FavoriteWord::query()->create([
            'user_id' => $user->id,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_USER,
            'source_dictionary_id' => $dictionary->id,
            'source_word_type' => FavoriteWord::SOURCE_WORD_USER,
            'source_word_id' => $word->id,
        ]);

        $this->actingAs($user)
            ->get(route('dictionaries.index'))
            ->assertOk()
            ->assertSee('Favorite Words')
            ->assertSee('1 favorite word')
            ->assertSee(route('dictionaries.favorites'), false);
    }

    public function test_user_can_toggle_favorite_word_from_personal_dictionary_page(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('toggleFavoriteWord', $word->id)
            ->assertSet('favoriteWordMap.'.$word->id, true)
            ->call('toggleFavoriteWord', $word->id)
            ->assertSet('favoriteWordMap.'.$word->id, false);

        $this->assertDatabaseCount('favorite_words', 0);
    }

    public function test_user_can_toggle_favorite_word_from_ready_dictionary_page(): void
    {
        $user = User::factory()->create();
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Readonly English',
            'language' => 'English',
        ]);

        $word = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ReadyDictionaries\Show::class, ['readyDictionary' => $dictionary])
            ->call('toggleFavoriteWord', $word->id)
            ->assertSet('favoriteWordMap.'.$word->id, true);

        $this->assertDatabaseHas('favorite_words', [
            'user_id' => $user->id,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_READY,
            'source_dictionary_id' => $dictionary->id,
            'source_word_type' => FavoriteWord::SOURCE_WORD_READY,
            'source_word_id' => $word->id,
        ]);
    }

    public function test_favorites_page_displays_saved_words_and_can_remove_them(): void
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
            'comment' => 'fruit',
        ]);

        $userDictionary->words()->attach($word->id);

        $favorite = FavoriteWord::query()->create([
            'user_id' => $user->id,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_USER,
            'source_dictionary_id' => $userDictionary->id,
            'source_word_type' => FavoriteWord::SOURCE_WORD_USER,
            'source_word_id' => $word->id,
        ]);

        $this->actingAs($user)
            ->get(route('dictionaries.favorites'))
            ->assertOk()
            ->assertSee('Favorite Words')
            ->assertSee('apple')
            ->assertSee('English Core')
            ->assertSee(route('dictionaries.show', $userDictionary), false);

        Livewire::actingAs($user)
            ->test(Favorites::class)
            ->call('removeFavorite', $favorite->id);

        $this->assertDatabaseMissing('favorite_words', [
            'id' => $favorite->id,
        ]);
    }

    public function test_favorites_page_uses_same_visual_actions_for_ready_dictionary_source(): void
    {
        $user = User::factory()->create();
        $dictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $readyWord = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $dictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        FavoriteWord::query()->create([
            'user_id' => $user->id,
            'source_dictionary_type' => FavoriteWord::SOURCE_DICTIONARY_READY,
            'source_dictionary_id' => $dictionary->id,
            'source_word_type' => FavoriteWord::SOURCE_WORD_READY,
            'source_word_id' => $readyWord->id,
        ]);

        $this->actingAs($user)
            ->get(route('dictionaries.favorites'))
            ->assertOk()
            ->assertSee('airport')
            ->assertSee('Ready Travel')
            ->assertSee(route('ready-dictionaries.show', $dictionary), false);
    }
}
