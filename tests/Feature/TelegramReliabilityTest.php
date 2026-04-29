<?php

namespace Tests\Feature;

use App\Models\TelegramGameRun;
use App\Models\TelegramGameRunItem;
use App\Models\TelegramProcessedUpdate;
use App\Models\TelegramRandomWordSession;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramReliabilityTest extends TestCase
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

    public function test_duplicate_callback_update_is_skipped_without_second_side_effects(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);

        [$run, $item] = $this->createInProgressRun();
        $payload = $this->callbackUpdate("telegram_answer:{$run->id}:{$item->id}:0", 70001);

        $this->postJson('/telegram/webhook/telegram-secret', $payload)->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $payload)->assertOk();

        $item->refresh();

        $this->assertSame('яблоко', $item->user_answer);
        Http::assertSentCount(4);
        $this->assertDatabaseHas('telegram_processed_updates', [
            'telegram_update_id' => 70001,
            'status' => TelegramProcessedUpdate::STATUS_PROCESSED,
        ]);
    }

    public function test_bot_service_retries_recoverable_errors(): void
    {
        Http::fakeSequence()
            ->push(['ok' => false], 500)
            ->push(['ok' => true, 'result' => ['message_id' => 123]], 200);

        $response = app(TelegramBotService::class)->sendMessage('1001', 'test');

        $this->assertSame(123, data_get($response, 'result.message_id'));
        Http::assertSentCount(2);
    }

    public function test_bot_service_does_not_retry_non_recoverable_errors(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        try {
            app(TelegramBotService::class)->sendMessage('1001', 'test');
            $this->fail('Expected RequestException was not thrown.');
        } catch (RequestException) {
            Http::assertSentCount(1);
        }
    }

    public function test_cleanup_command_marks_stale_runs_as_expired_and_abandoned(): void
    {
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

        $expiredRun = TelegramGameRun::query()->create([
            'user_id' => $user->id,
            'telegram_setting_id' => $setting->id,
            'telegram_random_word_session_id' => $session->id,
            'mode' => 'choice',
            'direction' => 'foreign_to_ru',
            'total_words' => 10,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramGameRun::STATUS_AWAITING_START,
            'scheduled_for' => now()->subHours(13),
            'intro_message_sent_at' => now()->subHours(13),
            'config_snapshot' => [],
        ]);

        $abandonedRun = TelegramGameRun::query()->create([
            'user_id' => $user->id,
            'telegram_setting_id' => $setting->id,
            'telegram_random_word_session_id' => $session->id,
            'mode' => 'choice',
            'direction' => 'foreign_to_ru',
            'total_words' => 10,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramGameRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now()->subHours(14),
            'started_at' => now()->subHours(13),
            'last_interaction_at' => now()->subHours(13),
            'config_snapshot' => [],
        ]);

        $freshRun = TelegramGameRun::query()->create([
            'user_id' => $user->id,
            'telegram_setting_id' => $setting->id,
            'telegram_random_word_session_id' => $session->id,
            'mode' => 'choice',
            'direction' => 'foreign_to_ru',
            'total_words' => 10,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramGameRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now()->subHours(2)->addMinute(),
            'started_at' => now()->subHours(2),
            'last_interaction_at' => now()->subHours(2),
            'config_snapshot' => [],
        ]);

        $this->artisan('telegram:cleanup-stale-runs')->assertSuccessful();

        $expiredRun->refresh();
        $abandonedRun->refresh();
        $freshRun->refresh();

        $this->assertSame(TelegramGameRun::STATUS_EXPIRED, $expiredRun->status);
        $this->assertSame('expired', $expiredRun->last_error_code);
        $this->assertSame(TelegramGameRun::STATUS_ABANDONED, $abandonedRun->status);
        $this->assertSame('abandoned', $abandonedRun->last_error_code);
        $this->assertSame(TelegramGameRun::STATUS_IN_PROGRESS, $freshRun->status);
    }

    /**
     * @return array{0:TelegramGameRun,1:TelegramGameRunItem}
     */
    private function createInProgressRun(): array
    {
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
            'total_words' => 1,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramGameRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now(),
            'started_at' => now(),
            'config_snapshot' => [],
        ]);

        $item = $run->items()->create([
            'word_id' => null,
            'order_index' => 1,
            'prompt_text' => 'apple',
            'part_of_speech_snapshot' => 'noun',
            'correct_answer' => 'яблоко',
            'source_type_snapshot' => 'user',
            'options_json' => ['яблоко', 'река', 'трава в поле', 'синее море', 'открытое окно', 'большой город'],
        ]);

        return [$run->fresh(['user', 'items']), $item];
    }

    /**
     * @return array<string,mixed>
     */
    private function callbackUpdate(string $data, int $updateId): array
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
