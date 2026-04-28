<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\TelegramGameRun;
use App\Models\TelegramRandomWordSession;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramScheduledSessionsTest extends TestCase
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

    public function test_dispatch_command_creates_due_run_items_and_intro_message(): void
    {
        CarbonImmutable::setTestNow('2026-04-28 09:15:00 UTC');
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 444],
            ], 200),
        ]);

        [$user, $session] = $this->createConnectedTelegramSession('UTC', '09:15:00', 6);

        $this->artisan('telegram:dispatch-scheduled-sessions')
            ->expectsOutput('Создано Telegram-сессий: 1. Пропущено дублей: 0.')
            ->assertSuccessful();

        $run = TelegramGameRun::query()
            ->with('items')
            ->firstOrFail();

        $this->assertSame($user->id, $run->user_id);
        $this->assertSame($session->id, $run->telegram_random_word_session_id);
        $this->assertSame(TelegramGameRun::STATUS_AWAITING_START, $run->status);
        $this->assertSame(4, $run->total_words);
        $this->assertCount(4, $run->items);
        $this->assertSame(444, $run->intro_message_id);
        $this->assertNotNull($run->intro_message_sent_at);
        $this->assertSame(['all'], $run->config_snapshot['parts_of_speech']);
        $this->assertSame(6, $run->config_snapshot['requested_words_count']);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && $request['chat_id'] === '1001'
                && str_contains((string) $request['text'], 'Запланировано к повторению 4 слов')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.text') === 'Начать'
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.1.text') === 'Отмена';
        });
    }

    public function test_dispatch_command_does_not_create_duplicate_run_for_same_slot(): void
    {
        CarbonImmutable::setTestNow('2026-04-28 09:15:00 UTC');
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 444],
            ], 200),
        ]);

        $this->createConnectedTelegramSession('UTC', '09:15:00', 6);

        $this->artisan('telegram:dispatch-scheduled-sessions')->assertSuccessful();
        $this->artisan('telegram:dispatch-scheduled-sessions')->assertSuccessful();

        $this->assertDatabaseCount('telegram_game_runs', 1);
    }

    public function test_cancel_callback_marks_run_as_cancelled(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);
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
            'status' => TelegramGameRun::STATUS_AWAITING_START,
            'scheduled_for' => now(),
            'intro_message_id' => 321,
            'config_snapshot' => [],
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('telegram_run:cancel:'.$run->id))
            ->assertOk();

        $run->refresh();

        $this->assertSame(TelegramGameRun::STATUS_CANCELLED, $run->status);
        $this->assertNotNull($run->cancelled_at);

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery'));
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/editMessageReplyMarkup'));
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'отменена'));
    }

    public function test_start_callback_marks_run_as_in_progress_and_sends_first_question(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 555]], 200),
        ]);

        [$user, $session] = $this->createConnectedTelegramSession('UTC', '09:15:00', 6);

        $run = TelegramGameRun::query()->create([
            'user_id' => $user->id,
            'telegram_setting_id' => $session->telegram_setting_id,
            'telegram_random_word_session_id' => $session->id,
            'mode' => 'choice',
            'direction' => 'foreign_to_ru',
            'total_words' => 2,
            'status' => TelegramGameRun::STATUS_AWAITING_START,
            'scheduled_for' => now(),
            'intro_message_id' => 321,
            'config_snapshot' => [],
        ]);

        $item = $run->items()->create([
            'word_id' => null,
            'order_index' => 1,
            'prompt_text' => 'apple',
            'part_of_speech_snapshot' => 'noun',
            'correct_answer' => 'яблоко',
            'source_type_snapshot' => 'user_dictionary',
            'options_json' => ['яблоко', 'груша', 'стол', 'окно', 'дом', 'море'],
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('telegram_run:start:'.$run->id))
            ->assertOk();

        $run->refresh();

        $this->assertSame(TelegramGameRun::STATUS_IN_PROGRESS, $run->status);
        $this->assertNotNull($run->started_at);

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery'));
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/editMessageReplyMarkup'));
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($run, $item): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Вопрос 1 из 2')
                && str_contains((string) $request['text'], 'apple')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.callback_data') === "telegram_answer:{$run->id}:{$item->id}:0";
        });
    }

    public function test_callback_cannot_control_run_from_another_chat(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '9999',
            'tg_linked_at' => now(),
        ]);
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
            'status' => TelegramGameRun::STATUS_AWAITING_START,
            'scheduled_for' => now(),
            'intro_message_id' => 321,
            'config_snapshot' => [],
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('telegram_run:start:'.$run->id))
            ->assertOk();

        $run->refresh();

        $this->assertSame(TelegramGameRun::STATUS_AWAITING_START, $run->status);
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains((string) ($request['text'] ?? ''), 'не найдена'));
    }

    /**
     * @return array{0:User,1:TelegramRandomWordSession}
     */
    private function createConnectedTelegramSession(string $timezone, string $sendTime, int $wordsCount = 10): array
    {
        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);
        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'My deck',
            'language' => 'English',
        ]);
        $wordOne = Word::query()->create([
            'word' => 'apple',
            'translation' => 'red',
            'part_of_speech' => 'noun',
            'comment' => null,
            'remainder_had_mistake' => false,
        ]);
        $wordTwo = Word::query()->create([
            'word' => 'book',
            'translation' => 'blue',
            'part_of_speech' => 'noun',
            'comment' => null,
            'remainder_had_mistake' => true,
        ]);
        $dictionary->words()->attach([$wordOne->id, $wordTwo->id]);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready deck',
            'language' => 'English',
        ]);
        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'cloud',
            'translation' => 'green',
            'part_of_speech' => 'noun',
        ]);
        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => $readyDictionary->id,
            'word' => 'desk',
            'translation' => 'yellow',
            'part_of_speech' => 'noun',
        ]);

        $setting = TelegramSetting::query()->create([
            'user_id' => $user->id,
            'timezone' => $timezone,
            'random_words_enabled' => true,
        ]);

        $session = TelegramRandomWordSession::query()->create([
            'telegram_setting_id' => $setting->id,
            'position' => 1,
            'send_time' => $sendTime,
            'translation_direction' => 'foreign_to_ru',
            'words_count' => $wordsCount,
        ]);
        $session->userDictionaries()->attach($dictionary->id);
        $session->readyDictionaries()->attach($readyDictionary->id);

        return [$user, $session->fresh(['userDictionaries', 'readyDictionaries', 'partsOfSpeech'])];
    }

    /**
     * @return array<string,mixed>
     */
    private function callbackUpdate(string $data): array
    {
        return [
            'update_id' => 20000,
            'callback_query' => [
                'id' => 'callback-1',
                'from' => [
                    'id' => 5001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'message' => [
                    'message_id' => 321,
                    'chat' => [
                        'id' => 1001,
                        'type' => 'private',
                    ],
                    'date' => now()->timestamp,
                    'text' => 'intro',
                ],
                'data' => $data,
            ],
        ];
    }
}
