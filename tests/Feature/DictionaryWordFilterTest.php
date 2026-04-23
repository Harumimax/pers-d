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
            'translation' => 'Р В Р Р‹Р В Р РЏР В Р’В Р вЂ™Р’В±Р В Р’В Р вЂ™Р’В»Р В Р’В Р РЋРІР‚СћР В Р’В Р РЋРІР‚СњР В Р’В Р РЋРІР‚Сћ',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->assertSee('Edit word apple')
            ->call('startEditingWord', $word->id)
            ->assertSee('word-edit-translation-'.$word->id)
            ->assertSee('word-edit-part-of-speech-'.$word->id)
            ->assertSee('word-edit-comment-'.$word->id)
            ->assertSee('Apply')
            ->assertSee('Cancel');
    }

    public function test_word_list_displays_remainder_mistake_marker_and_legend(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        $mistakeWord = Word::create([
            'word' => 'apple',
            'part_of_speech' => 'noun',
            'translation' => 'apple',
            'comment' => null,
            'remainder_had_mistake' => true,
        ]);
        $cleanWord = Word::create([
            'word' => 'book',
            'part_of_speech' => 'noun',
            'translation' => 'book',
            'comment' => null,
            'remainder_had_mistake' => false,
        ]);

        $dictionary->words()->attach([$mistakeWord->id, $cleanWord->id]);

        $response = $this->actingAs($user)->get(route('dictionaries.show', $dictionary));

        $response
            ->assertOk()
            ->assertSee('apple')
            ->assertSee('book')
            ->assertSee('The red dot means you previously made a mistake with this word in the Remainder game.');

        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="Previous Remainder mistake"'));
    }

    public function test_user_can_update_word_translation_part_of_speech_and_comment(): void
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
            'translation' => 'Р В Р Р‹Р В Р РЏР В Р’В Р вЂ™Р’В±Р В Р’В Р вЂ™Р’В»Р В Р’В Р РЋРІР‚СћР В Р’В Р РЋРІР‚СњР В Р’В Р РЋРІР‚Сћ',
            'comment' => 'fruit',
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('startEditingWord', $word->id)
            ->assertSet('editingWordId', $word->id)
            ->assertSet('editingWordTranslation', 'Р В Р Р‹Р В Р РЏР В Р’В Р вЂ™Р’В±Р В Р’В Р вЂ™Р’В»Р В Р’В Р РЋРІР‚СћР В Р’В Р РЋРІР‚СњР В Р’В Р РЋРІР‚Сћ')
            ->assertSet('editingWordPartOfSpeech', 'noun')
            ->assertSet('editingWordComment', 'fruit')
            ->set('editingWordTranslation', 'apple fruit')
            ->set('editingWordPartOfSpeech', 'stable_expression')
            ->set('editingWordComment', 'updated comment')
            ->call('updateEditingWord')
            ->assertHasNoErrors()
            ->assertSet('editingWordId', null)
            ->assertSet('editingWordTranslation', '')
            ->assertSet('editingWordPartOfSpeech', '')
            ->assertSet('editingWordComment', '')
            ->assertSee('apple fruit')
            ->assertSee('updated comment');

        $this->assertDatabaseHas('words', [
            'id' => $word->id,
            'translation' => 'apple fruit',
            'part_of_speech' => 'stable_expression',
            'comment' => 'updated comment',
        ]);
    }

    public function test_user_can_clear_word_comment_while_editing_word(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'book',
            'part_of_speech' => 'noun',
            'translation' => 'книга',
            'comment' => 'paper',
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('startEditingWord', $word->id)
            ->set('editingWordComment', '')
            ->call('updateEditingWord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('words', [
            'id' => $word->id,
            'comment' => null,
        ]);
    }

    public function test_word_translation_is_required_when_editing_word(): void
    {
        $user = User::factory()->create();
        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'green',
            'part_of_speech' => 'adjective',
            'translation' => 'Р В Р’В Р вЂ™Р’В·Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р Р†Р вЂљР’ВР В Р’В Р В РІР‚В¦Р В Р Р‹Р Р†Р вЂљРІвЂћвЂ“Р В Р’В Р Р†РІР‚С›РІР‚вЂњ',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('startEditingWord', $word->id)
            ->set('editingWordTranslation', '')
            ->call('updateEditingWord')
            ->assertHasErrors('editingWordTranslation')
            ->assertSet('editingWordId', $word->id);

        $this->assertDatabaseHas('words', [
            'id' => $word->id,
            'translation' => 'Р В Р’В Р вЂ™Р’В·Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р Р†Р вЂљР’ВР В Р’В Р В РІР‚В¦Р В Р Р‹Р Р†Р вЂљРІвЂћвЂ“Р В Р’В Р Р†РІР‚С›РІР‚вЂњ',
        ]);
    }

    public function test_user_cannot_edit_word_from_another_dictionary(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English',
            'language' => 'English',
        ]);

        $otherDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'language' => 'English',
        ]);

        $foreignWord = Word::create([
            'word' => 'hidden',
            'part_of_speech' => 'noun',
            'translation' => 'secret',
            'comment' => null,
        ]);

        $otherDictionary->words()->attach($foreignWord->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['dictionary' => $dictionary])
            ->call('startEditingWord', $foreignWord->id)
            ->assertForbidden();
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
            'translation' => 'Р В Р’В Р вЂ™Р’В·Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р Р†Р вЂљР’ВР В Р’В Р В РІР‚В¦Р В Р Р‹Р Р†Р вЂљРІвЂћвЂ“Р В Р’В Р Р†РІР‚С›РІР‚вЂњ',
            'comment' => null,
        ]);
        $matchByTranslation = Word::create([
            'word' => 'blue',
            'part_of_speech' => 'adjective',
            'translation' => 'Bright SKY',
            'comment' => null,
        ]);
        $nonMatch = Word::create([
            'word' => 'purple',
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
            ->assertDontSee('purple')
            ->set('search', 'sky')
            ->call('applySearch')
            ->assertSee('blue')
            ->assertDontSee('Green')
            ->assertDontSee('purple');
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
            ->set('translation', ' Р·РµР»С‘РЅС‹Р№ ')
            ->set('comment', " \u{2060}basic color ")
            ->call('addWord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('words', [
            'word' => 'green',
            'translation' => 'Р·РµР»С‘РЅС‹Р№',
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
                    'translatedText' => 'Р В Р’В Р СћРІР‚ВР В Р’В Р РЋРІР‚СћР В Р’В Р вЂ™Р’В±Р В Р Р‹Р В РІР‚С™Р В Р’В Р РЋРІР‚СћР В Р’В Р вЂ™Р’Вµ Р В Р Р‹Р РЋРІР‚СљР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р РЋРІР‚Сћ',
                ],
                'matches' => [
                    [
                        'translation' => 'Р В Р’В Р вЂ™Р’В·Р В Р’В Р СћРІР‚ВР В Р Р‹Р В РІР‚С™Р В Р’В Р вЂ™Р’В°Р В Р’В Р В РІР‚В Р В Р Р‹Р В РЎвЂњР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р В РІР‚В Р В Р Р‹Р РЋРІР‚СљР В Р’В Р Р†РІР‚С›РІР‚вЂњР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р вЂ™Р’Вµ',
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
            ->assertSet('autoTranslation', 'Р В Р’В Р СћРІР‚ВР В Р’В Р РЋРІР‚СћР В Р’В Р вЂ™Р’В±Р В Р Р‹Р В РІР‚С™Р В Р’В Р РЋРІР‚СћР В Р’В Р вЂ™Р’Вµ Р В Р Р‹Р РЋРІР‚СљР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р РЋРІР‚Сћ')
            ->assertSet('showCreateForm', true)
            ->assertSee('Р В Р’В Р СћРІР‚ВР В Р’В Р РЋРІР‚СћР В Р’В Р вЂ™Р’В±Р В Р Р‹Р В РІР‚С™Р В Р’В Р РЋРІР‚СћР В Р’В Р вЂ™Р’Вµ Р В Р Р‹Р РЋРІР‚СљР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р РЋРІР‚Сћ')
            ->assertSee('Р В Р’В Р вЂ™Р’В·Р В Р’В Р СћРІР‚ВР В Р Р‹Р В РІР‚С™Р В Р’В Р вЂ™Р’В°Р В Р’В Р В РІР‚В Р В Р Р‹Р В РЎвЂњР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р В РІР‚В Р В Р Р‹Р РЋРІР‚СљР В Р’В Р Р†РІР‚С›РІР‚вЂњР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р вЂ™Р’Вµ')
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
                ['text' => 'Р В Р’В Р РЋРІР‚вЂќР В Р’В Р РЋРІР‚СћР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В±Р В Р’В Р РЋРІР‚ВР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р В Р вЂ°', 'label' => 'top result'],
                ['text' => 'Consumer Protection Law (2005).', 'label' => 'memory match'],
                ['text' => 'Р В Р’В Р РЋРІР‚С”Р В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р СћРІР‚ВР В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В» Р В Р’В Р РЋРІР‚ВР В Р’В Р В РІР‚В¦Р В Р’В Р СћРІР‚ВР В Р’В Р вЂ™Р’ВµР В Р’В Р РЋРІР‚СњР В Р Р‹Р В РЎвЂњР В Р’В Р вЂ™Р’В° Р В Р’В Р РЋРІР‚вЂќР В Р’В Р РЋРІР‚СћР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В±Р В Р’В Р РЋРІР‚ВР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р В Р вЂ°Р В Р Р‹Р В РЎвЂњР В Р’В Р РЋРІР‚СњР В Р’В Р РЋРІР‚ВР В Р Р‹Р Р†Р вЂљР’В¦ Р В Р Р‹Р Р†Р вЂљР’В Р В Р’В Р вЂ™Р’ВµР В Р’В Р В РІР‚В¦', 'label' => 'memory match'],
            ])
            ->set('autoTranslated', true)
            ->set('autoTranslation', 'Р В Р’В Р РЋРІР‚вЂќР В Р’В Р РЋРІР‚СћР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В±Р В Р’В Р РЋРІР‚ВР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р В Р вЂ°')
            ->call('selectAutoTranslationByIndex', 1)
            ->assertSet('autoTranslation', 'Consumer Protection Law (2005).')
            ->call('selectAutoTranslationByIndex', 2)
            ->assertSet('autoTranslation', 'Р В Р’В Р РЋРІР‚С”Р В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р СћРІР‚ВР В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В» Р В Р’В Р РЋРІР‚ВР В Р’В Р В РІР‚В¦Р В Р’В Р СћРІР‚ВР В Р’В Р вЂ™Р’ВµР В Р’В Р РЋРІР‚СњР В Р Р‹Р В РЎвЂњР В Р’В Р вЂ™Р’В° Р В Р’В Р РЋРІР‚вЂќР В Р’В Р РЋРІР‚СћР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р Р‹Р В РІР‚С™Р В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В±Р В Р’В Р РЋРІР‚ВР В Р Р‹Р Р†Р вЂљРЎв„ўР В Р’В Р вЂ™Р’ВµР В Р’В Р вЂ™Р’В»Р В Р Р‹Р В Р вЂ°Р В Р Р‹Р В РЎвЂњР В Р’В Р РЋРІР‚СњР В Р’В Р РЋРІР‚ВР В Р Р‹Р Р†Р вЂљР’В¦ Р В Р Р‹Р Р†Р вЂљР’В Р В Р’В Р вЂ™Р’ВµР В Р’В Р В РІР‚В¦');
    }
    public function test_translate_automatically_filters_out_mixed_latin_and_cyrillic_suggestions(): void
    {
        Http::fake([
            'https://api.mymemory.translated.net/get*' => Http::response([
                'responseData' => [
                    'translatedText' => 'Р РЋРІР‚С™Р В РЎвЂўР РЋРІР‚РЋР В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“',
                ],
                'matches' => [
                    [
                        'translation' => 'Р РЋРІР‚С™Р В РЎвЂўР РЋРІР‚РЋР В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“',
                        'match' => 1,
                    ],
                    [
                        'translation' => 'Р РЋРІР‚С™Р В РЎвЂўР РЋРІР‚РЋР В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“ (accurate)',
                        'created-by' => 'tm',
                        'match' => 0.97,
                    ],
                    [
                        'translation' => 'Р В РЎвЂ”Р В РЎвЂўР В РўвЂР РЋРІР‚В¦Р В РЎвЂўР В РўвЂР РЋР РЏР РЋРІР‚В°Р В РЎвЂР В РІвЂћвЂ“',
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
            ->set('autoWord', 'accurate')
            ->call('translateAutomatically')
            ->assertSet('autoTranslated', true)
            ->assertSet('autoTranslation', 'Р РЋРІР‚С™Р В РЎвЂўР РЋРІР‚РЋР В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“')
            ->assertSet('autoSuggestions', [
                ['text' => 'Р РЋРІР‚С™Р В РЎвЂўР РЋРІР‚РЋР В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“', 'label' => 'top result'],
                ['text' => 'Р В РЎвЂ”Р В РЎвЂўР В РўвЂР РЋРІР‚В¦Р В РЎвЂўР В РўвЂР РЋР РЏР РЋРІР‚В°Р В РЎвЂР В РІвЂћвЂ“', 'label' => 'suggested'],
            ])
            ->assertDontSee('Р РЋРІР‚С™Р В РЎвЂўР РЋРІР‚РЋР В Р вЂ¦Р РЋРІР‚в„–Р В РІвЂћвЂ“ (accurate)');
    }
}


