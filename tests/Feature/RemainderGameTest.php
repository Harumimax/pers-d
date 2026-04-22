<?php

namespace Tests\Feature;

use App\Livewire\Remainder\Show;
use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RemainderGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_start_manual_game(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'English Core', 'English');
        $this->attachWord($dictionary, 'apple', 'apple', 'noun');
        $this->attachWord($dictionary, 'book', 'book', 'noun');

        $response = $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 2,
        ]);

        $gameSession = GameSession::query()->first();

        $response->assertRedirect(route('remainder.sessions.show', $gameSession));
        $this->assertNotNull($gameSession);
        $this->assertSame($user->id, $gameSession->user_id);
        $this->assertSame(GameSession::MODE_MANUAL, $gameSession->mode);
        $this->assertSame(GameSession::STATUS_ACTIVE, $gameSession->status);
        $this->assertSame(2, $gameSession->items()->count());
    }

    public function test_authenticated_user_can_start_game_from_ready_dictionary_only(): void
    {
        $user = User::factory()->create();
        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready English',
            'language' => 'English',
        ]);
        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'accurate',
            'translation' => 'точный',
            'part_of_speech' => 'adjective',
            'comment' => 'Ready dictionary source',
        ]);
        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'adapt',
            'translation' => 'адаптироваться',
            'part_of_speech' => 'verb',
            'comment' => null,
        ]);

        $response = $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'ready_dictionary_ids' => [$readyDictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 2,
        ]);

        $gameSession = GameSession::query()->firstOrFail();
        $items = $gameSession->items()->with('word')->get();

        $response->assertRedirect(route('remainder.sessions.show', $gameSession));
        $this->assertSame(2, $gameSession->items()->count());
        $this->assertSame([$readyDictionary->id], $gameSession->config_snapshot['ready_dictionary_ids']);
        $this->assertSame([], $gameSession->config_snapshot['dictionary_ids']);
        $this->assertEqualsCanonicalizing(['accurate', 'adapt'], $items->pluck('word.word')->all());
        $this->assertDatabaseMissing('user_dictionary_word', [
            'word_id' => $items->first()->word_id,
        ]);
    }

    public function test_game_can_combine_personal_and_ready_dictionaries(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'My deck', 'English');
        $this->attachWord($dictionary, 'personal', 'личный', 'adjective');

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready deck',
            'language' => 'English',
        ]);
        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'ready',
            'translation' => 'готовый',
            'part_of_speech' => 'adjective',
        ]);

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'ready_dictionary_ids' => [$readyDictionary->id],
            'parts_of_speech' => ['adjective'],
            'words_count' => 2,
        ]);

        $gameSession = GameSession::query()->firstOrFail();

        $this->assertSame(2, $gameSession->items()->count());
        $this->assertEqualsCanonicalizing(
            ['personal', 'ready'],
            $gameSession->items()->pluck('prompt_text')->all(),
        );
    }

    public function test_user_cannot_use_another_users_dictionary_in_game_configuration(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignDictionary = $this->createDictionaryForUser($otherUser, 'Private', 'English');
        $this->attachWord($foreignDictionary, 'secret', 'secret', 'noun');

        $response = $this->actingAs($user)->from(route('remainder'))->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$foreignDictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 5,
        ]);

        $response->assertRedirect(route('remainder'));
        $response->assertSessionHasErrors('dictionary_ids');
        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_game_uses_available_word_count_when_less_than_requested(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Spanish Core', 'Spanish');
        $this->attachWord($dictionary, 'casa', 'house', 'noun');

        $response = $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 10,
        ]);

        $gameSession = GameSession::query()->firstOrFail();

        $response->assertRedirect(route('remainder.sessions.show', $gameSession));
        $response->assertSessionHas('gameNotice');
        $this->assertSame(1, $gameSession->total_words);
        $this->assertSame(1, $gameSession->items()->count());
    }

    public function test_words_do_not_repeat_inside_a_single_game_session(): void
    {
        $user = User::factory()->create();
        $firstDictionary = $this->createDictionaryForUser($user, 'English Core', 'English');
        $secondDictionary = $this->createDictionaryForUser($user, 'English Extra', 'English');

        $sharedWord = Word::create([
            'word' => 'shared',
            'translation' => 'shared',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);
        $uniqueWord = Word::create([
            'word' => 'unique',
            'translation' => 'unique',
            'part_of_speech' => 'adjective',
            'comment' => null,
        ]);

        $firstDictionary->words()->attach([$sharedWord->id, $uniqueWord->id]);
        $secondDictionary->words()->attach($sharedWord->id);

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$firstDictionary->id, $secondDictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 10,
        ]);

        $wordIds = GameSession::query()->firstOrFail()
            ->items()
            ->pluck('word_id');

        $this->assertCount($wordIds->unique()->count(), $wordIds);
    }

    public function test_all_part_of_speech_filter_includes_words_without_part_of_speech(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Mixed', 'English');
        $this->attachWord($dictionary, 'apple', 'apple', 'noun');

        $legacyWord = Word::create([
            'word' => 'legacy',
            'translation' => 'legacy',
            'part_of_speech' => null,
            'comment' => null,
        ]);
        $dictionary->words()->attach($legacyWord->id);

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 10,
        ]);

        $this->assertSame(2, GameSession::query()->firstOrFail()->items()->count());
    }

    public function test_specific_part_of_speech_filter_excludes_words_without_part_of_speech(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Mixed', 'English');
        $this->attachWord($dictionary, 'apple', 'apple', 'noun');

        $legacyWord = Word::create([
            'word' => 'legacy',
            'translation' => 'legacy',
            'part_of_speech' => null,
            'comment' => null,
        ]);
        $dictionary->words()->attach($legacyWord->id);

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['noun'],
            'words_count' => 10,
        ]);

        $this->assertSame(1, GameSession::query()->firstOrFail()->items()->count());
    }

    public function test_game_is_not_created_when_no_words_match_filters(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Verb deck', 'English');
        $this->attachWord($dictionary, 'speak', 'speak', 'verb');

        $response = $this->actingAs($user)->from(route('remainder'))->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['noun'],
            'words_count' => 5,
        ]);

        $response->assertRedirect(route('remainder'));
        $response->assertSessionHasErrors('dictionary_ids');
        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_authenticated_user_can_start_choice_game(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'English Core', 'English');
        $this->attachWord($dictionary, 'apple', 'red', 'noun');
        $this->attachWord($dictionary, 'book', 'blue', 'noun');
        $this->attachWord($dictionary, 'cloud', 'green', 'noun');

        $response = $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 3,
        ]);

        $gameSession = GameSession::query()->firstOrFail();

        $response->assertRedirect(route('remainder.sessions.show', $gameSession));
        $this->assertSame(GameSession::MODE_CHOICE, $gameSession->mode);
        $this->assertSame(3, $gameSession->items()->count());
    }

    public function test_choice_game_uses_existing_dictionary_ownership_restrictions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignDictionary = $this->createDictionaryForUser($otherUser, 'Private', 'English');
        $this->attachWord($foreignDictionary, 'secret', 'red', 'noun');
        $this->attachWord($foreignDictionary, 'hidden', 'blue', 'noun');

        $response = $this->actingAs($user)->from(route('remainder'))->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$foreignDictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 2,
        ]);

        $response->assertRedirect(route('remainder'));
        $response->assertSessionHasErrors('dictionary_ids');
        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_choice_items_store_options_without_duplicates_and_include_correct_answer(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'apple', 'translation' => 'red', 'part_of_speech' => 'noun'],
            ['word' => 'book', 'translation' => 'blue', 'part_of_speech' => 'noun'],
            ['word' => 'cloud', 'translation' => 'green', 'part_of_speech' => 'noun'],
            ['word' => 'desk', 'translation' => 'yellow', 'part_of_speech' => 'noun'],
            ['word' => 'earth', 'translation' => 'orange', 'part_of_speech' => 'noun'],
            ['word' => 'flame', 'translation' => 'violet', 'part_of_speech' => 'noun'],
        ], GameSession::MODE_CHOICE);

        foreach ($gameSession->items as $item) {
            $this->assertIsArray($item->options_json);
            $this->assertContains($item->correct_answer, $item->options_json);
            $this->assertCount(count(array_unique($item->options_json)), $item->options_json);
            $this->assertCount(6, $item->options_json);
        }
    }

    public function test_choice_options_use_the_full_filtered_answer_pool_and_not_only_the_selected_round_words(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Wide pool', 'English');

        $words = [
            ['word' => 'word-1', 'translation' => 'answer-1', 'part_of_speech' => 'noun'],
            ['word' => 'word-2', 'translation' => 'answer-2', 'part_of_speech' => 'noun'],
            ['word' => 'word-3', 'translation' => 'answer-3', 'part_of_speech' => 'noun'],
            ['word' => 'word-4', 'translation' => 'answer-4', 'part_of_speech' => 'noun'],
            ['word' => 'word-5', 'translation' => 'answer-5', 'part_of_speech' => 'noun'],
            ['word' => 'word-6', 'translation' => 'answer-6', 'part_of_speech' => 'noun'],
            ['word' => 'word-7', 'translation' => 'answer-7', 'part_of_speech' => 'noun'],
            ['word' => 'word-8', 'translation' => 'answer-8', 'part_of_speech' => 'noun'],
            ['word' => 'word-9', 'translation' => 'answer-9', 'part_of_speech' => 'noun'],
            ['word' => 'word-10', 'translation' => 'answer-10', 'part_of_speech' => 'noun'],
        ];

        foreach ($words as $wordData) {
            $this->attachWord($dictionary, $wordData['word'], $wordData['translation'], $wordData['part_of_speech']);
        }

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 4,
        ]);

        $gameSession = GameSession::query()->firstOrFail();
        $questionAnswers = $gameSession->items->pluck('correct_answer')->all();
        $fullAnswerPool = collect($words)->pluck('translation')->all();

        $this->assertCount(4, $questionAnswers);

        $usedExternalDistractor = $gameSession->items->contains(function (GameSessionItem $item) use ($questionAnswers, $fullAnswerPool): bool {
            $distractors = collect($item->options_json)
                ->reject(fn (string $option): bool => $option === $item->correct_answer);

            return $distractors->contains(function (string $option) use ($questionAnswers, $fullAnswerPool): bool {
                return in_array($option, $fullAnswerPool, true) && ! in_array($option, $questionAnswers, true);
            });
        });

        $this->assertTrue($usedExternalDistractor);
    }

    public function test_choice_game_does_not_fail_when_there_are_fewer_than_six_unique_options_and_warning_is_formed(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Compact deck', 'English');
        $this->attachWord($dictionary, 'apple', 'red', 'noun');
        $this->attachWord($dictionary, 'book', 'blue', 'noun');
        $this->attachWord($dictionary, 'cloud', 'green', 'noun');

        $response = $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 3,
        ]);

        $gameSession = GameSession::query()->firstOrFail();
        $warnings = $gameSession->config_snapshot['warnings'] ?? [];

        $response->assertRedirect(route('remainder.sessions.show', $gameSession));
        $this->assertNotEmpty($warnings);
        $this->assertSame(
            'Only 3 answer options were available for some questions because the selected dictionaries and filters did not contain enough unique answers.',
            $warnings[0],
        );

        foreach ($gameSession->items as $item) {
            $this->assertLessThan(6, count($item->options_json));
            $this->assertGreaterThanOrEqual(2, count($item->options_json));
        }

        $this->actingAs($user)
            ->get(route('remainder.sessions.show', $gameSession))
            ->assertOk()
            ->assertSee($warnings[0]);
    }

    public function test_choice_warning_is_hidden_on_feedback_and_result_screens(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Compact deck', 'English');
        $this->attachWord($dictionary, 'apple', 'red', 'noun');
        $this->attachWord($dictionary, 'book', 'blue', 'noun');

        $response = $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 5,
        ]);

        $gameSession = GameSession::query()->latest('id')->firstOrFail();
        $warning = $gameSession->config_snapshot['warnings'][0] ?? null;
        $gameNotice = session('gameNotice');
        $item = $gameSession->items()->firstOrFail();

        $this->assertIsString($warning);
        $this->assertIsString($gameNotice);
        $response->assertRedirect(route('remainder.sessions.show', $gameSession));

        $this->actingAs($user)
            ->get(route('remainder.sessions.show', $gameSession))
            ->assertOk()
            ->assertSeeInOrder([
                $gameNotice,
                $warning,
                'Remainder',
            ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->assertSee($warning)
            ->set('selectedChoice', $item->correct_answer)
            ->call('submitAnswer')
            ->assertDontSee($warning)
            ->call('continueToNext')
            ->assertSee($warning)
            ->set('selectedChoice', collect($gameSession->items()->orderBy('order_index')->skip(1)->firstOrFail()->options_json)->first())
            ->call('submitAnswer')
            ->assertDontSee($warning)
            ->call('continueToNext')
            ->assertDontSee($warning)
            ->assertSee('Remainder results');
    }

    public function test_choice_game_is_not_created_if_it_cannot_build_two_unique_options(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Too small', 'English');
        $this->attachWord($dictionary, 'apple', 'same', 'noun');
        $this->attachWord($dictionary, 'book', 'same', 'noun');

        $response = $this->actingAs($user)->from(route('remainder'))->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_CHOICE,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 2,
        ]);

        $response->assertRedirect(route('remainder'));
        $response->assertSessionHasErrors('dictionary_ids');
        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_choice_answer_is_recorded_as_correct_when_selected_option_matches(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'apple', 'translation' => 'red', 'part_of_speech' => 'noun'],
            ['word' => 'book', 'translation' => 'blue', 'part_of_speech' => 'noun'],
        ], GameSession::MODE_CHOICE);

        $firstItem = $gameSession->items()->orderBy('order_index')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('selectedChoice', $firstItem->correct_answer)
            ->call('submitAnswer')
            ->assertSet('showFeedback', true);

        $firstItem->refresh();
        $gameSession->refresh();

        $this->assertTrue((bool) $firstItem->is_correct);
        $this->assertSame($firstItem->correct_answer, $firstItem->user_answer);
        $this->assertSame(1, $gameSession->correct_answers);
    }

    public function test_choice_answer_is_recorded_as_incorrect_when_selected_option_does_not_match_and_last_answer_finishes_session(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'apple', 'translation' => 'red', 'part_of_speech' => 'noun'],
            ['word' => 'book', 'translation' => 'blue', 'part_of_speech' => 'noun'],
        ], GameSession::MODE_CHOICE);

        $items = $gameSession->items()->orderBy('order_index')->get();
        $firstItem = $items[0];
        $secondItem = $items[1];
        $wrongOption = collect($secondItem->options_json)
            ->first(fn (string $option): bool => $option !== $secondItem->correct_answer);

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('selectedChoice', $firstItem->correct_answer)
            ->call('submitAnswer')
            ->call('continueToNext')
            ->set('selectedChoice', $wrongOption)
            ->call('submitAnswer')
            ->assertSet('showFeedback', true);

        $gameSession->refresh();
        $firstItem->refresh();
        $secondItem->refresh();

        $this->assertSame(GameSession::STATUS_FINISHED, $gameSession->status);
        $this->assertTrue((bool) $firstItem->is_correct);
        $this->assertFalse((bool) $secondItem->is_correct);
        $this->assertSame($wrongOption, $secondItem->user_answer);
        $this->assertSame(1, $gameSession->correct_answers);
    }

    public function test_game_screen_displays_part_of_speech_for_manual_and_choice_modes(): void
    {
        $manualUser = User::factory()->create();

        $manualSession = $this->startGameForWords($manualUser, [
            ['word' => 'apple', 'translation' => 'apple', 'part_of_speech' => 'cardinal'],
        ]);

        $this->actingAs($manualUser)
            ->get(route('remainder.sessions.show', $manualSession))
            ->assertOk()
            ->assertSee('Cardinal');

        $choiceUser = User::factory()->create();
        $choiceSession = $this->startGameForWords($choiceUser, [
            ['word' => 'phrase one', 'translation' => 'alpha', 'part_of_speech' => 'stable_expression'],
            ['word' => 'phrase two', 'translation' => 'beta', 'part_of_speech' => 'stable_expression'],
        ], GameSession::MODE_CHOICE);

        $this->actingAs($choiceUser)
            ->get(route('remainder.sessions.show', $choiceSession))
            ->assertOk()
            ->assertSee('Stable expression');
    }

    public function test_game_screen_uses_snapshot_part_of_speech_and_not_live_word_data(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'apple', 'translation' => 'apple', 'part_of_speech' => 'noun'],
        ]);

        $item = $gameSession->items()->firstOrFail();
        $this->assertSame('noun', $item->part_of_speech_snapshot);

        $item->word()->update([
            'part_of_speech' => 'verb',
        ]);

        $this->actingAs($user)
            ->get(route('remainder.sessions.show', $gameSession))
            ->assertOk()
            ->assertSee('Noun')
            ->assertDontSee('Verb');
    }

    public function test_manual_answer_is_checked_case_insensitively_and_last_answer_finishes_session(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'Apple', 'translation' => 'apple', 'part_of_speech' => 'noun'],
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('answer', '  APPLE  ')
            ->call('submitAnswer')
            ->assertSet('showFeedback', true);

        $gameSession->refresh();
        $item = $gameSession->items()->firstOrFail();

        $this->assertSame(GameSession::STATUS_FINISHED, $gameSession->status);
        $this->assertSame(1, $gameSession->correct_answers);
        $this->assertTrue((bool) $item->is_correct);
        $this->assertSame('APPLE', $item->user_answer);
    }

    public function test_results_are_shown_only_after_continue_on_the_last_answer(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'Apple', 'translation' => 'apple', 'part_of_speech' => 'noun'],
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('answer', 'apple')
            ->call('submitAnswer')
            ->assertSet('showFeedback', true)
            ->assertSee('Correct')
            ->assertDontSee('Remainder results')
            ->call('continueToNext')
            ->assertSet('showFeedback', false)
            ->assertSee('Remainder results');
    }

    public function test_correct_answers_counter_is_counted_properly_across_multiple_answers(): void
    {
        $user = User::factory()->create();
        $gameSession = $this->startGameForWords($user, [
            ['word' => 'Apple', 'translation' => 'apple', 'part_of_speech' => 'noun'],
            ['word' => 'Book', 'translation' => 'book', 'part_of_speech' => 'noun'],
        ]);

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession]);

        $firstItem = $gameSession->items()->orderBy('order_index')->firstOrFail();
        $secondItem = $gameSession->items()->orderBy('order_index')->skip(1)->firstOrFail();

        $component
            ->set('answer', $firstItem->correct_answer)
            ->call('submitAnswer')
            ->assertSet('showFeedback', true)
            ->call('continueToNext')
            ->set('answer', 'wrong answer')
            ->call('submitAnswer');

        $gameSession->refresh();
        $firstItem->refresh();
        $secondItem->refresh();

        $this->assertSame(GameSession::STATUS_FINISHED, $gameSession->status);
        $this->assertSame(1, $gameSession->correct_answers);
        $this->assertTrue((bool) $firstItem->is_correct);
        $this->assertFalse((bool) $secondItem->is_correct);
    }

    public function test_finished_session_marks_incorrect_personal_words_as_remainder_mistakes(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Training deck', 'English');
        $word = $this->attachWord($dictionary, 'Apple', 'apple', 'noun');

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 1,
        ]);

        $gameSession = GameSession::query()->latest('id')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('answer', 'wrong answer')
            ->call('submitAnswer');

        $this->assertTrue($word->refresh()->remainder_had_mistake);
    }

    public function test_finished_session_clears_previous_remainder_mistake_for_correct_personal_words(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'Training deck', 'English');
        $word = $this->attachWord($dictionary, 'Apple', 'apple', 'noun', true);

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 1,
        ]);

        $gameSession = GameSession::query()->latest('id')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('answer', 'apple')
            ->call('submitAnswer');

        $this->assertFalse($word->refresh()->remainder_had_mistake);
    }

    public function test_finished_session_ignores_ready_dictionary_snapshot_words_for_remainder_mistakes(): void
    {
        $user = User::factory()->create();
        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready English',
            'language' => 'English',
        ]);
        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'accurate',
            'translation' => 'accurate',
            'part_of_speech' => 'adjective',
        ]);

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => GameSession::MODE_MANUAL,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'ready_dictionary_ids' => [$readyDictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 1,
        ]);

        $gameSession = GameSession::query()->latest('id')->firstOrFail();
        $snapshotWord = $gameSession->items()->firstOrFail()->word;

        $snapshotWord->forceFill(['remainder_had_mistake' => true])->save();

        Livewire::actingAs($user)
            ->test(Show::class, ['gameSession' => $gameSession])
            ->set('answer', 'accurate')
            ->call('submitAnswer');

        $this->assertTrue($snapshotWord->refresh()->remainder_had_mistake);
    }

    public function test_user_cannot_open_another_users_game_session(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $gameSession = $this->startGameForWords($owner, [
            ['word' => 'Apple', 'translation' => 'apple', 'part_of_speech' => 'noun'],
        ]);

        $this->actingAs($viewer)
            ->get(route('remainder.sessions.show', $gameSession))
            ->assertForbidden();
    }

    /**
     * @param array<int, array{word:string,translation:string,part_of_speech:?string}> $words
     */
    private function startGameForWords(User $user, array $words, string $mode = GameSession::MODE_MANUAL): GameSession
    {
        $dictionary = $this->createDictionaryForUser($user, 'Training deck', 'English');

        foreach ($words as $wordData) {
            $this->attachWord(
                $dictionary,
                $wordData['word'],
                $wordData['translation'],
                $wordData['part_of_speech'],
            );
        }

        $this->actingAs($user)->post(route('remainder.sessions.store'), [
            'mode' => $mode,
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => count($words),
        ]);

        return GameSession::query()->latest('id')->firstOrFail();
    }

    private function createDictionaryForUser(User $user, string $name, string $language): UserDictionary
    {
        return UserDictionary::create([
            'user_id' => $user->id,
            'name' => $name,
            'language' => $language,
        ]);
    }

    private function attachWord(
        UserDictionary $dictionary,
        string $word,
        string $translation,
        ?string $partOfSpeech,
        bool $remainderHadMistake = false,
    ): Word
    {
        $wordModel = Word::create([
            'word' => $word,
            'translation' => $translation,
            'part_of_speech' => $partOfSpeech,
            'comment' => null,
            'remainder_had_mistake' => $remainderHadMistake,
        ]);

        $dictionary->words()->attach($wordModel->id);

        return $wordModel;
    }
}
