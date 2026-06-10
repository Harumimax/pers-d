<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DictionaryWordExamplesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dictionary_page_renders_saved_examples_for_word(): void
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
        $word->examples()->create([
            'example_text' => 'I eat an apple every morning.',
            'example_translation' => 'Я ем яблоко каждое утро.',
            'sort_order' => 0,
            'source' => 'tatoeba',
            'source_external_id' => '101',
        ]);

        $this->actingAs($user)
            ->get(route('dictionaries.show', $dictionary))
            ->assertOk()
            ->assertSee('I eat an apple every morning.')
            ->assertSee('Я ем яблоко каждое утро.');
    }

    public function test_ready_dictionary_page_renders_saved_examples_for_word(): void
    {
        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready Travel',
            'language' => 'English',
        ]);

        $word = ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'airport',
            'translation' => 'аэропорт',
            'part_of_speech' => 'noun',
        ]);

        $word->examples()->create([
            'example_text' => 'The airport is close to the city.',
            'example_translation' => 'Аэропорт находится рядом с городом.',
            'sort_order' => 0,
            'source' => 'tatoeba',
            'source_external_id' => '202',
        ]);

        $this->get(route('ready-dictionaries.show', $readyDictionary))
            ->assertOk()
            ->assertSee('The airport is close to the city.')
            ->assertSee('Аэропорт находится рядом с городом.');
    }
}
