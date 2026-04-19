<?php

namespace Tests\Feature;

use App\Livewire\Dictionaries\Show;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->assertSeeHtml('Total words: <b>3</b>')
            ->assertSee('casa')
            ->assertSee('hablar')
            ->assertSee('legacy')
            ->set('partOfSpeechFilter', 'verb')
            ->assertSeeHtml('Total words: <b>3</b>')
            ->assertSee('hablar')
            ->assertDontSee('casa')
            ->assertDontSee('legacy')
            ->set('partOfSpeechFilter', 'all')
            ->assertSee('casa')
            ->assertSee('hablar')
            ->assertSee('legacy');
    }

    public function test_word_list_renders_edit_placeholder_ui(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'apple',
            'part_of_speech' => 'noun',
            'translation' => 'яблоко',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->assertSee('Edit word apple')
            ->assertSee('word-edit-translation-'.$word->id)
            ->assertSee('word-edit-part-of-speech-'.$word->id)
            ->assertSee('word-edit-comment-'.$word->id)
            ->assertSee('Apply')
            ->assertSee('Cancel');
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

    public function test_search_is_case_insensitive_for_word_and_translation(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Case Search',
            'language' => 'English',
        ]);

        $matchByWord = Word::create([
            'word' => 'Green',
            'part_of_speech' => 'adjective',
            'translation' => 'зелёный',
            'comment' => null,
        ]);
        $matchByTranslation = Word::create([
            'word' => 'blue',
            'part_of_speech' => 'adjective',
            'translation' => 'Bright SKY',
            'comment' => null,
        ]);
        $nonMatch = Word::create([
            'word' => 'red',
            'part_of_speech' => 'adjective',
            'translation' => 'warm tone',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$matchByWord->id, $matchByTranslation->id, $nonMatch->id]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('search', 'gReE')
            ->call('applySearch')
            ->assertSee('Green')
            ->assertDontSee('blue')
            ->assertDontSee('red')
            ->set('search', 'sky')
            ->call('applySearch')
            ->assertSee('blue')
            ->assertDontSee('Green')
            ->assertDontSee('red');
    }

    public function test_user_can_add_word_from_translate_automatically_mode(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Italian',
            'language' => 'Italian',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('showCreateForm', true)
            ->set('autoWord', 'buongiorno')
            ->set('autoTranslation', 'good morning')
            ->set('autoPartOfSpeech', 'interjection')
            ->set('autoComment', 'formal greeting')
            ->call('addTranslatedWord')
            ->assertHasNoErrors()
            ->assertSet('autoWord', '')
            ->assertSet('autoTranslation', '')
            ->assertSet('autoPartOfSpeech', '')
            ->assertSet('autoComment', '');

        $this->assertDatabaseHas('words', [
            'word' => 'buongiorno',
            'translation' => 'good morning',
            'part_of_speech' => 'interjection',
            'comment' => 'formal greeting',
        ]);

        $wordId = Word::query()
            ->where('word', 'buongiorno')
            ->value('id');

        $this->assertNotNull($wordId);

        $this->assertDatabaseHas('user_dictionary_word', [
            'user_dictionary_id' => $dictionary->id,
            'word_id' => $wordId,
        ]);
    }

    public function test_word_input_is_trimmed_and_zero_width_characters_are_removed_before_save(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Normalization',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('showCreateForm', true)
            ->set('word', " \u{200B}green\u{200D} ")
            ->set('partOfSpeech', 'adjective')
            ->set('translation', " \u{FEFF}зелёный ")
            ->set('comment', " \u{2060}basic color ")
            ->call('addWord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('words', [
            'word' => 'green',
            'translation' => 'зелёный',
            'comment' => 'basic color',
            'part_of_speech' => 'adjective',
        ]);
    }

    public function test_word_input_with_control_characters_is_rejected(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Control Chars',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('showCreateForm', true)
            ->set('word', "bad\x07word")
            ->set('partOfSpeech', 'noun')
            ->set('translation', 'test')
            ->call('addWord')
            ->assertHasErrors(['word']);

        $this->assertDatabaseMissing('words', [
            'translation' => 'test',
        ]);
    }

    public function test_dictionary_page_displays_cardinal_part_of_speech_option(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->assertSee('Cardinal')
            ->assertSee('Числительное');
    }

    public function test_translate_automatically_loads_suggestions_from_service(): void
    {
        Http::fake([
            'https://api.mymemory.translated.net/get*' => Http::response([
                'responseData' => [
                    'translatedText' => 'доброе утро',
                ],
                'matches' => [
                    [
                        'translation' => 'здравствуйте',
                        'created-by' => 'memory',
                        'match' => 0.87,
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('showCreateForm', true)
            ->set('autoWord', 'good morning')
            ->call('translateAutomatically')
            ->assertSet('autoTranslated', true)
            ->assertSet('autoTranslation', 'доброе утро')
            ->assertSet('showCreateForm', true)
            ->assertSee('доброе утро')
            ->assertSee('здравствуйте')
            ->assertSet('autoTranslationError', '');
    }

    public function test_translate_automatically_shows_fallback_message_when_translation_is_unavailable(): void
    {
        Http::fake([
            'https://api.mymemory.translated.net/get*' => Http::response([
                'responseData' => [
                    'translatedText' => '',
                ],
                'matches' => [],
            ]),
        ]);

        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish',
            'language' => 'Spanish',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('showCreateForm', true)
            ->set('autoWord', 'hola')
            ->call('translateAutomatically')
            ->assertSet('autoTranslated', false)
            ->assertSet('autoTranslation', '')
            ->assertSet('autoTranslationError', 'Translation is currently unavailable. Please switch to Enter manually.')
            ->assertSee('Translation is currently unavailable. Please switch to Enter manually.')
            ->assertSee('Switch to Enter manually');
    }

    public function test_user_can_switch_selected_auto_translation_chip_by_index(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->set('showCreateForm', true)
            ->set('autoSuggestions', [
                ['text' => 'потребитель', 'label' => 'top result'],
                ['text' => 'Consumer Protection Law (2005).', 'label' => 'memory match'],
                ['text' => 'Отдел индекса потребительских цен', 'label' => 'memory match'],
            ])
            ->set('autoTranslated', true)
            ->set('autoTranslation', 'потребитель')
            ->call('selectAutoTranslationByIndex', 1)
            ->assertSet('autoTranslation', 'Consumer Protection Law (2005).')
            ->call('selectAutoTranslationByIndex', 2)
            ->assertSet('autoTranslation', 'Отдел индекса потребительских цен');
    }
}
