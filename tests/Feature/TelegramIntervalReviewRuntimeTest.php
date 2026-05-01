<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewPlanWord;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewRunItem;
use App\Models\TelegramIntervalReviewSession;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramIntervalReviewRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'telegram-test-token',
            'services.telegram.webhook_secret' => 'telegram-secret',
        ]);
    }

    public function test_begin_quiz_deletes_word_list_and_sends_first_question(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 901]], 200),
        ]);

        [$run, $firstItem] = $this->createRunWithItems();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('interval_run:begin_quiz:'.$run->id))
            ->assertOk();

        $run->refresh();

        $this->assertNull($run->word_list_message_id);
        $this->assertSame(TelegramIntervalReviewRun::STATUS_IN_PROGRESS, $run->status);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && (string) $request['text'] === 'Квиз начат.');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/deleteMessage') && (int) $request['message_id'] === 654);
        Http::assertSent(function (Request $request) use ($run, $firstItem): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Вопрос 1 из 2')
                && str_contains((string) $request['text'], 'Варианты ответа:')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.callback_data') === "interval_answer:{$run->id}:{$firstItem->id}:0";
        });
    }

    public function test_correct_answer_sends_feedback_and_next_question(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 902]], 200),
        ]);

        [$run, $firstItem, $secondItem] = $this->createRunWithItems();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$firstItem->id}:0", 45002, 765))
            ->assertOk();

        $firstItem->refresh();
        $run->refresh();

        $this->assertSame('яблоко', $firstItem->user_answer);
        $this->assertTrue($firstItem->is_correct);
        $this->assertNotNull($firstItem->answered_at);
        $this->assertSame(TelegramIntervalReviewRun::STATUS_IN_PROGRESS, $run->status);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && (string) $request['text'] === 'Ответ принят.');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/editMessageReplyMarkup') && (int) $request['message_id'] === 765);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && (string) $request['text'] === 'Корректно.');
        Http::assertSent(function (Request $request) use ($run, $secondItem): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Вопрос 2 из 2')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.callback_data') === "interval_answer:{$run->id}:{$secondItem->id}:0";
        });
    }

    public function test_incorrect_last_answer_finishes_interval_session_stores_counters_and_sends_errors_block(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 903]], 200),
        ]);

        [$run, $firstItem, $secondItem, $session] = $this->createRunWithItems();

        $firstItem->forceFill([
            'user_answer' => 'яблоко',
            'is_correct' => true,
            'answered_at' => now(),
        ])->save();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$secondItem->id}:1", 45003, 766))
            ->assertOk();

        $secondItem->refresh();
        $run->refresh();
        $session->refresh();

        $this->assertSame('река', $secondItem->user_answer);
        $this->assertFalse($secondItem->is_correct);
        $this->assertNotNull($secondItem->answered_at);
        $this->assertSame(TelegramIntervalReviewRun::STATUS_FINISHED, $run->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_FINISHED, $session->status);
        $this->assertNotNull($run->finished_at);
        $this->assertSame(1, $run->correct_answers);
        $this->assertSame(1, $run->incorrect_answers);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'Некорректно. Правильный ответ: книга'));
        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Сессия завершена.')
                && str_contains((string) $request['text'], 'Правильных ответов: 1 из 2.')
                && str_contains((string) $request['text'], 'Ошибок: 1.')
                && str_contains((string) $request['text'], 'Ошибки:')
                && str_contains((string) $request['text'], '1. book')
                && str_contains((string) $request['text'], 'Правильный ответ: книга')
                && str_contains((string) $request['text'], 'Ваш ответ: река');
        });
    }

    public function test_finalization_updates_mistake_flags_for_user_words(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 904]], 200),
        ]);

        [$run, $firstItem, $secondItem, $session, $firstWord, $secondWord] = $this->createRunWithItems(firstWordHadMistake: true, secondWordHadMistake: false);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$firstItem->id}:0", 45006, 771))
            ->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$secondItem->id}:1", 45007, 772))
            ->assertOk();

        $run->refresh();
        $session->refresh();
        $firstWord->refresh();
        $secondWord->refresh();

        $this->assertSame(TelegramIntervalReviewRun::STATUS_FINISHED, $run->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_FINISHED, $session->status);
        $this->assertFalse($firstWord->remainder_had_mistake);
        $this->assertTrue($secondWord->remainder_had_mistake);
    }

    public function test_last_interval_session_completes_the_whole_plan_and_sends_completion_message(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 905]], 200),
        ]);

        [$run, $firstItem, $secondItem, $session, $firstWord, $secondWord, $plan] = $this->createRunWithItems(
            firstWordHadMistake: false,
            secondWordHadMistake: false,
            sessionNumber: 6,
            completedSessionsBeforeCurrent: 5,
        );

        $firstItem->forceFill([
            'user_answer' => 'яблоко',
            'is_correct' => true,
            'answered_at' => now(),
        ])->save();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$secondItem->id}:0", 45008, 773))
            ->assertOk();

        $run->refresh();
        $session->refresh();
        $plan->refresh();

        $this->assertSame(TelegramIntervalReviewRun::STATUS_FINISHED, $run->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_FINISHED, $session->status);
        $this->assertSame(TelegramIntervalReviewPlan::STATUS_COMPLETED, $plan->status);
        $this->assertSame(6, $plan->completed_sessions_count);
        $this->assertNotNull($plan->completed_at);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'Интервальное повторение выбранных слов завершено.'));
    }

    public function test_cannot_answer_same_item_twice(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        [$run, $firstItem] = $this->createRunWithItems();

        $firstItem->forceFill([
            'user_answer' => 'яблоко',
            'is_correct' => true,
            'answered_at' => now(),
        ])->save();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$firstItem->id}:0", 45004, 767))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains((string) $request['text'], 'уже ответили'));
    }

    public function test_invalid_option_index_is_rejected(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        [$run, $firstItem] = $this->createRunWithItems();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("interval_answer:{$run->id}:{$firstItem->id}:99", 45005, 768))
            ->assertOk();

        $firstItem->refresh();

        $this->assertNull($firstItem->user_answer);
        $this->assertNull($firstItem->answered_at);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains((string) $request['text'], 'недоступен'));
    }

    /**
     * @return array{0:TelegramIntervalReviewRun,1:TelegramIntervalReviewRunItem,2:TelegramIntervalReviewRunItem,3:TelegramIntervalReviewSession,4:Word,5:Word,6:TelegramIntervalReviewPlan}
     */
    private function createRunWithItems(
        bool $firstWordHadMistake = false,
        bool $secondWordHadMistake = false,
        int $sessionNumber = 1,
        int $completedSessionsBeforeCurrent = 0,
    ): array {
        $user = User::factory()->create([
            'tg_chat_id' => '2001',
            'tg_linked_at' => now(),
        ]);

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'User pool',
            'language' => 'English',
        ]);

        $firstWord = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'remainder_had_mistake' => $firstWordHadMistake,
        ]);
        $userDictionary->words()->attach($firstWord->id);

        $secondWord = Word::query()->create([
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
            'remainder_had_mistake' => $secondWordHadMistake,
        ]);
        $userDictionary->words()->attach($secondWord->id);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready pool',
            'language' => 'English',
        ]);

        foreach ([
            ['word' => 'desk', 'translation' => 'стол'],
            ['word' => 'window', 'translation' => 'окно'],
            ['word' => 'sea', 'translation' => 'море'],
            ['word' => 'river', 'translation' => 'река'],
        ] as $wordData) {
            ReadyDictionaryWord::factory()->create([
                'ready_dictionary_id' => $readyDictionary->id,
                'word' => $wordData['word'],
                'translation' => $wordData['translation'],
                'part_of_speech' => 'noun',
            ]);
        }

        $plan = TelegramIntervalReviewPlan::query()->create([
            'user_id' => $user->id,
            'status' => TelegramIntervalReviewPlan::STATUS_ACTIVE,
            'language' => 'English',
            'start_time' => '09:15',
            'timezone' => 'UTC',
            'words_count' => 2,
            'completed_sessions_count' => 0,
        ]);

        $planWordOne = TelegramIntervalReviewPlanWord::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'source_type' => 'user',
            'source_dictionary_id' => $userDictionary->id,
            'source_word_id' => $firstWord->id,
            'dictionary_name' => 'Plan source',
            'language' => 'English',
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit',
            'position' => 1,
        ]);

        $planWordTwo = TelegramIntervalReviewPlanWord::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'source_type' => 'user',
            'source_dictionary_id' => $userDictionary->id,
            'source_word_id' => $secondWord->id,
            'dictionary_name' => 'Plan source',
            'language' => 'English',
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
            'comment' => null,
            'position' => 2,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            TelegramIntervalReviewSession::query()->create([
                'telegram_interval_review_plan_id' => $plan->id,
                'session_number' => $i,
                'scheduled_for' => now()->addDays($i),
                'status' => $i < $sessionNumber && $i <= $completedSessionsBeforeCurrent
                    ? TelegramIntervalReviewSession::STATUS_FINISHED
                    : ($i === $sessionNumber ? TelegramIntervalReviewSession::STATUS_IN_PROGRESS : TelegramIntervalReviewSession::STATUS_SCHEDULED),
            ]);
        }

        $session = TelegramIntervalReviewSession::query()
            ->where('telegram_interval_review_plan_id', $plan->id)
            ->where('session_number', $sessionNumber)
            ->firstOrFail();

        $run = TelegramIntervalReviewRun::query()->create([
            'user_id' => $user->id,
            'telegram_interval_review_plan_id' => $plan->id,
            'telegram_interval_review_session_id' => $session->id,
            'session_number' => $sessionNumber,
            'total_words' => 2,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramIntervalReviewRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now(),
            'intro_message_id' => 321,
            'word_list_message_id' => 654,
            'started_at' => now(),
            'config_snapshot' => [
                'language' => 'English',
                'mode' => 'choice',
            ],
        ]);

        $firstItem = $run->items()->create([
            'telegram_interval_review_plan_word_id' => $planWordOne->id,
            'order_index' => 1,
            'word_snapshot' => 'apple',
            'translation_snapshot' => 'яблоко',
            'part_of_speech_snapshot' => 'noun',
            'comment_snapshot' => 'Fruit',
            'prompt_text' => 'apple',
            'correct_answer' => 'яблоко',
            'source_type_snapshot' => 'user',
            'options_json' => ['яблоко', 'река', 'море', 'стол', 'окно', 'трава'],
        ]);

        $secondItem = $run->items()->create([
            'telegram_interval_review_plan_word_id' => $planWordTwo->id,
            'order_index' => 2,
            'word_snapshot' => 'book',
            'translation_snapshot' => 'книга',
            'part_of_speech_snapshot' => 'noun',
            'comment_snapshot' => null,
            'prompt_text' => 'book',
            'correct_answer' => 'книга',
            'source_type_snapshot' => 'user',
            'options_json' => ['книга', 'река', 'море', 'стол', 'окно', 'трава'],
        ]);

        return [$run->fresh(['user', 'items', 'session', 'plan.sessions']), $firstItem, $secondItem, $session, $firstWord, $secondWord, $plan];
    }

    /**
     * @return array<string,mixed>
     */
    private function callbackUpdate(string $data, int $updateId = 45001, int $messageId = 654): array
    {
        return [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'interval-runtime-'.$updateId,
                'from' => [
                    'id' => 7001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'message' => [
                    'message_id' => $messageId,
                    'chat' => [
                        'id' => 2001,
                        'type' => 'private',
                    ],
                    'date' => now()->timestamp,
                    'text' => 'interval runtime',
                ],
                'data' => $data,
            ],
        ];
    }
}
