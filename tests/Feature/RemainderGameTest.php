<?php

namespace Tests\Feature;

use App\Livewire\Remainder\Show;
use App\Models\GameSession;
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

    public function test_choice_mode_cannot_start_a_game(): void
    {
        $user = User::factory()->create();
        $dictionary = $this->createDictionaryForUser($user, 'English Core', 'English');
        $this->attachWord($dictionary, 'apple', 'apple', 'noun');

        $response = $this->actingAs($user)->from(route('remainder'))->post(route('remainder.sessions.store'), [
            'mode' => 'choice',
            'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'dictionary_ids' => [$dictionary->id],
            'parts_of_speech' => ['all'],
            'words_count' => 5,
        ]);

        $response->assertRedirect(route('remainder'));
        $response->assertSessionHasErrors('mode');
        $this->assertDatabaseCount('game_sessions', 0);
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
            ->assertSet('showFeedback', false);

        $gameSession->refresh();
        $item = $gameSession->items()->firstOrFail();

        $this->assertSame(GameSession::STATUS_FINISHED, $gameSession->status);
        $this->assertSame(1, $gameSession->correct_answers);
        $this->assertTrue((bool) $item->is_correct);
        $this->assertSame('APPLE', $item->user_answer);
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
    private function startGameForWords(User $user, array $words): GameSession
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
            'mode' => GameSession::MODE_MANUAL,
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

    private function attachWord(UserDictionary $dictionary, string $word, string $translation, ?string $partOfSpeech): Word
    {
        $wordModel = Word::create([
            'word' => $word,
            'translation' => $translation,
            'part_of_speech' => $partOfSpeech,
            'comment' => null,
        ]);

        $dictionary->words()->attach($wordModel->id);

        return $wordModel;
    }
}