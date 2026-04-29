<?php

namespace Tests\Feature;

use App\Models\TelegramGameRun;
use App\Models\TelegramGameRunItem;
use App\Models\TelegramRandomWordSession;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramGameRuntimeTest extends TestCase
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

    public function test_correct_answer_is_saved_feedback_is_sent_and_next_question_is_opened(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);

        [$run, $firstItem, $secondItem] = $this->createRunWithItems();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("telegram_answer:{$run->id}:{$firstItem->id}:0", 20001))
            ->assertOk();

        $firstItem->refresh();
        $run->refresh();

        $this->assertSame('яблоко', $firstItem->user_answer);
        $this->assertTrue($firstItem->is_correct);
        $this->assertNotNull($firstItem->answered_at);
        $this->assertSame(TelegramGameRun::STATUS_IN_PROGRESS, $run->status);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && (string) $request['text'] === 'Ответ принят.');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/editMessageReplyMarkup'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && (string) $request['text'] === 'Корректно.');
        Http::assertSent(function (Request $request) use ($run, $secondItem): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Вопрос 2 из 2')
                && str_contains((string) $request['text'], 'Варианты ответа:')
                && str_contains((string) $request['text'], '1. книга')
                && str_contains((string) $request['text'], '6. большой город')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.text') === '1'
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.callback_data') === "telegram_answer:{$run->id}:{$secondItem->id}:0";
        });
    }

    public function test_incorrect_last_answer_finishes_run_saves_counters_and_sends_summary(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);

        [$run, $firstItem, $secondItem] = $this->createRunWithItems();

        $firstItem->forceFill([
            'user_answer' => 'яблоко',
            'is_correct' => true,
            'answered_at' => now(),
        ])->save();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("telegram_answer:{$run->id}:{$secondItem->id}:1", 20002))
            ->assertOk();

        $secondItem->refresh();
        $run->refresh();

        $this->assertSame('река', $secondItem->user_answer);
        $this->assertFalse($secondItem->is_correct);
        $this->assertNotNull($secondItem->answered_at);
        $this->assertSame(TelegramGameRun::STATUS_FINISHED, $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertSame(1, $run->correct_answers);
        $this->assertSame(1, $run->incorrect_answers);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'Некорректно. Правильный ответ: книга'));
        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Сессия завершена.')
                && str_contains((string) $request['text'], 'Правильных ответов: 1 из 2.')
                && str_contains((string) $request['text'], 'Неправильных ответов: 1.')
                && str_contains((string) $request['text'], 'Ошибки:')
                && str_contains((string) $request['text'], '1. book')
                && str_contains((string) $request['text'], 'Правильный ответ: книга')
                && str_contains((string) $request['text'], 'Ваш ответ: река');
        });
    }

    public function test_finalization_updates_mistake_flags_for_user_words(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);

        [$run, $firstItem, $secondItem, $firstWord, $secondWord] = $this->createRunWithItems(firstWordHadMistake: true, secondWordHadMistake: false);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("telegram_answer:{$run->id}:{$firstItem->id}:0", 20001))
            ->assertOk();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("telegram_answer:{$run->id}:{$secondItem->id}:1", 20002))
            ->assertOk();

        $run->refresh();
        $firstWord->refresh();
        $secondWord->refresh();

        $this->assertSame(TelegramGameRun::STATUS_FINISHED, $run->status);
        $this->assertSame(1, $run->correct_answers);
        $this->assertSame(1, $run->incorrect_answers);
        $this->assertFalse($firstWord->remainder_had_mistake);
        $this->assertTrue($secondWord->remainder_had_mistake);
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

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("telegram_answer:{$run->id}:{$firstItem->id}:0", 20001))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains((string) $request['text'], 'уже ответили'));
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && ((string) $request['text'] === 'Корректно.' || str_contains((string) $request['text'], 'Некорректно.')));
    }

    public function test_invalid_option_index_is_rejected(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        [$run, $firstItem] = $this->createRunWithItems();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("telegram_answer:{$run->id}:{$firstItem->id}:99", 20001))
            ->assertOk();

        $firstItem->refresh();

        $this->assertNull($firstItem->user_answer);
        $this->assertNull($firstItem->answered_at);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains((string) $request['text'], 'недоступен'));
    }

    /**
     * @return array{0:TelegramGameRun,1:TelegramGameRunItem,2:TelegramGameRunItem,3:Word,4:Word}
     */
    private function createRunWithItems(
        bool $firstWordHadMistake = false,
        bool $secondWordHadMistake = false,
    ): array {
        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Telegram test dictionary',
            'language' => 'English',
        ]);

        $firstWord = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'remainder_had_mistake' => $firstWordHadMistake,
        ]);

        $secondWord = Word::query()->create([
            'word' => 'book',
            'translation' => 'книга',
            'part_of_speech' => 'noun',
            'remainder_had_mistake' => $secondWordHadMistake,
        ]);

        $dictionary->words()->attach([$firstWord->id, $secondWord->id]);

        $setting = TelegramSetting::query()->create([
            'user_id' => $user->id,
            'timezone' => 'UTC',
            'random_words_enabled' => true,
        ]);

        $session = TelegramRandomWordSession::query()->create([
            'telegram_setting_id' => $setting->id,
            'position' => 1,
            'send_time' => '09:15:00',
            'translation_direction' => 'foreign_to_ru',
            'words_count' => 10,
        ]);

        $run = TelegramGameRun::query()->create([
            'user_id' => $user->id,
            'telegram_setting_id' => $setting->id,
            'telegram_random_word_session_id' => $session->id,
            'mode' => 'choice',
            'direction' => 'foreign_to_ru',
            'total_words' => 2,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramGameRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now(),
            'started_at' => now(),
            'config_snapshot' => [],
        ]);

        $firstItem = $run->items()->create([
            'word_id' => $firstWord->id,
            'order_index' => 1,
            'prompt_text' => 'apple',
            'part_of_speech_snapshot' => 'noun',
            'correct_answer' => 'яблоко',
            'source_type_snapshot' => 'user',
            'options_json' => ['яблоко', 'река', 'трава в поле', 'синее море', 'открытое окно', 'большой город'],
        ]);

        $secondItem = $run->items()->create([
            'word_id' => $secondWord->id,
            'order_index' => 2,
            'prompt_text' => 'book',
            'part_of_speech_snapshot' => 'noun',
            'correct_answer' => 'книга',
            'source_type_snapshot' => 'user',
            'options_json' => ['книга', 'река', 'трава в поле', 'синее море', 'открытое окно', 'большой город'],
        ]);

        return [$run->fresh(['user', 'items']), $firstItem, $secondItem, $firstWord, $secondWord];
    }

    /**
     * @return array<string,mixed>
     */
    private function callbackUpdate(string $data, int $updateId = 20001): array
    {
        return [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'callback-runtime-'.$updateId,
                'from' => [
                    'id' => 5001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'message' => [
                    'message_id' => 765,
                    'chat' => [
                        'id' => 1001,
                        'type' => 'private',
                    ],
                    'date' => now()->timestamp,
                    'text' => 'question',
                ],
                'data' => $data,
            ],
        ];
    }
}
