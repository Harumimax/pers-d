<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Index;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DictionarySearchExamplesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_dictionary_search_renders_saved_examples_for_found_word(): void
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
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);
        $word->examples()->create([
            'example_text' => 'I eat an apple every morning.',
            'example_translation' => 'Я ем яблоко каждое утро.',
            'sort_order' => 0,
            'source' => 'tatoeba',
            'source_external_id' => '101',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('searchQuery', 'apple')
            ->call('searchWords')
            ->assertSee('apple')
            ->assertSee('I eat an apple every morning.')
            ->assertSee('Я ем яблоко каждое утро.');
    }
}
