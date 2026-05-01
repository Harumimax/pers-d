<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewPlanWord;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewSession;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramIntervalReviewSchedulerTest extends TestCase
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

    public function test_dispatch_command_creates_due_interval_review_run_items_and_intro_message(): void
    {
        CarbonImmutable::setTestNow('2026-04-30 09:15:00 UTC');
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 777],
            ], 200),
        ]);

        [$plan, $session] = $this->createActiveIntervalPlan('English', '2026-04-30 09:15:00 UTC', 1);

        $this->artisan('telegram:dispatch-interval-review-sessions')
            ->expectsOutput('Создано interval review Telegram-сессий: 1. Пропущено дублей: 0.')
            ->assertSuccessful();

        $run = TelegramIntervalReviewRun::query()
            ->with(['items', 'session'])
            ->firstOrFail();

        $this->assertSame($plan->user_id, $run->user_id);
        $this->assertSame($plan->id, $run->telegram_interval_review_plan_id);
        $this->assertSame($session->id, $run->telegram_interval_review_session_id);
        $this->assertSame(TelegramIntervalReviewRun::STATUS_AWAITING_START, $run->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_AWAITING_START, $run->session->status);
        $this->assertSame(2, $run->total_words);
        $this->assertCount(2, $run->items);
        $this->assertSame(777, $run->intro_message_id);
        $this->assertSame('English', $run->config_snapshot['language']);
        $this->assertSame('choice', $run->config_snapshot['mode']);

        $firstItem = $run->items->first();
        $this->assertNotNull($firstItem);
        $this->assertSame('apple', $firstItem->word_snapshot);
        $this->assertSame('яблоко', $firstItem->correct_answer);
        $this->assertCount(6, $firstItem->options_json ?? []);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && $request['chat_id'] === '2001'
                && str_contains((string) $request['text'], 'Первая сессия интервального повторения слов')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.text') === 'Начать'
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.1.text') === 'Отменить';
        });
    }

    public function test_dispatch_command_skips_paused_plan(): void
    {
        CarbonImmutable::setTestNow('2026-04-30 09:15:00 UTC');
        Http::fake();

        $this->createPausedIntervalPlan('English', '2026-04-30 09:15:00 UTC', 1);

        $this->artisan('telegram:dispatch-interval-review-sessions')
            ->expectsOutput('Создано interval review Telegram-сессий: 0. Пропущено дублей: 0.')
            ->assertSuccessful();

        $this->assertDatabaseCount('telegram_interval_review_runs', 0);
        Http::assertNothingSent();
    }

    public function test_cancel_callback_cancels_only_current_interval_session_and_next_due_session_can_still_dispatch(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 701]], 200),
        ]);

        [$plan, $firstSession] = $this->createActiveIntervalPlan('English', '2026-04-30 09:15:00 UTC', 1);
        $secondSession = TelegramIntervalReviewSession::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'session_number' => 2,
            'scheduled_for' => CarbonImmutable::parse('2026-04-30 09:20:00 UTC'),
            'status' => TelegramIntervalReviewSession::STATUS_SCHEDULED,
        ]);

        CarbonImmutable::setTestNow('2026-04-30 09:15:00 UTC');
        $this->artisan('telegram:dispatch-interval-review-sessions')->assertSuccessful();

        $run = TelegramIntervalReviewRun::query()->firstOrFail();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('interval_run:cancel:'.$run->id))
            ->assertOk();

        $run->refresh();
        $firstSession->refresh();
        $secondSession->refresh();

        $this->assertSame(TelegramIntervalReviewRun::STATUS_CANCELLED, $run->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_CANCELLED, $firstSession->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_SCHEDULED, $secondSession->status);

        CarbonImmutable::setTestNow('2026-04-30 09:20:00 UTC');
        $this->artisan('telegram:dispatch-interval-review-sessions')->assertSuccessful();

        $this->assertDatabaseCount('telegram_interval_review_runs', 2);
        $this->assertDatabaseHas('telegram_interval_review_runs', [
            'telegram_interval_review_session_id' => $secondSession->id,
            'status' => TelegramIntervalReviewRun::STATUS_AWAITING_START,
        ]);
    }

    public function test_start_callback_marks_interval_run_as_in_progress_and_sends_stub_message(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 801]], 200),
        ]);

        [$plan, $session] = $this->createActiveIntervalPlan('English', '2026-04-30 09:15:00 UTC', 1);
        CarbonImmutable::setTestNow('2026-04-30 09:15:00 UTC');
        $this->artisan('telegram:dispatch-interval-review-sessions')->assertSuccessful();

        $run = TelegramIntervalReviewRun::query()->firstOrFail();

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('interval_run:start:'.$run->id))
            ->assertOk();

        $run->refresh();
        $session->refresh();

        $this->assertSame(TelegramIntervalReviewRun::STATUS_IN_PROGRESS, $run->status);
        $this->assertNotNull($run->started_at);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_IN_PROGRESS, $session->status);

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery'));
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/editMessageReplyMarkup'));
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_ends_with($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'Полный игровой поток будет подключён следующим этапом'));
    }

    /**
     * @return array{0:TelegramIntervalReviewPlan,1:TelegramIntervalReviewSession}
     */
    private function createActiveIntervalPlan(string $language, string $scheduledFor, int $sessionNumber): array
    {
        [$user, $words] = $this->createLanguagePool($language);

        $plan = TelegramIntervalReviewPlan::query()->create([
            'user_id' => $user->id,
            'status' => TelegramIntervalReviewPlan::STATUS_ACTIVE,
            'language' => $language,
            'start_time' => '09:15',
            'timezone' => 'UTC',
            'words_count' => 2,
        ]);

        TelegramIntervalReviewPlanWord::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'source_type' => 'user',
            'source_dictionary_id' => 1,
            'source_word_id' => 1,
            'dictionary_name' => 'Plan source',
            'language' => $language,
            'word' => $words[0]['word'],
            'translation' => $words[0]['translation'],
            'part_of_speech' => 'noun',
            'comment' => 'Fruit',
            'position' => 1,
        ]);

        TelegramIntervalReviewPlanWord::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'source_type' => 'ready',
            'source_dictionary_id' => 2,
            'source_word_id' => 2,
            'dictionary_name' => 'Plan source',
            'language' => $language,
            'word' => $words[1]['word'],
            'translation' => $words[1]['translation'],
            'part_of_speech' => 'noun',
            'comment' => null,
            'position' => 2,
        ]);

        $session = TelegramIntervalReviewSession::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'session_number' => $sessionNumber,
            'scheduled_for' => CarbonImmutable::parse($scheduledFor),
            'status' => TelegramIntervalReviewSession::STATUS_SCHEDULED,
        ]);

        return [$plan->fresh(['user', 'words']), $session];
    }

    /**
     * @return array{0:TelegramIntervalReviewPlan,1:TelegramIntervalReviewSession}
     */
    private function createPausedIntervalPlan(string $language, string $scheduledFor, int $sessionNumber): array
    {
        [$plan, $session] = $this->createActiveIntervalPlan($language, $scheduledFor, $sessionNumber);

        $plan->forceFill(['status' => TelegramIntervalReviewPlan::STATUS_PAUSED])->save();
        $session->forceFill(['status' => TelegramIntervalReviewSession::STATUS_PAUSED])->save();

        return [$plan->fresh(['user', 'words']), $session->fresh()];
    }

    /**
     * @return array{0:User,1:array<int,array{word:string,translation:string}>}
     */
    private function createLanguagePool(string $language): array
    {
        $user = User::factory()->create([
            'tg_chat_id' => '2001',
            'tg_linked_at' => now(),
        ]);

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'User pool',
            'language' => $language,
        ]);

        $words = [
            ['word' => 'apple', 'translation' => 'яблоко'],
            ['word' => 'book', 'translation' => 'книга'],
            ['word' => 'cloud', 'translation' => 'облако'],
        ];

        foreach ($words as $wordData) {
            $word = Word::query()->create([
                'word' => $wordData['word'],
                'translation' => $wordData['translation'],
                'part_of_speech' => 'noun',
            ]);
            $userDictionary->words()->attach($word->id);
        }

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready pool',
            'language' => $language,
        ]);

        foreach ([
            ['word' => 'desk', 'translation' => 'стол'],
            ['word' => 'window', 'translation' => 'окно'],
            ['word' => 'sea', 'translation' => 'море'],
        ] as $wordData) {
            ReadyDictionaryWord::factory()->create([
                'ready_dictionary_id' => $readyDictionary->id,
                'word' => $wordData['word'],
                'translation' => $wordData['translation'],
                'part_of_speech' => 'noun',
            ]);
        }

        return [$user, $words];
    }

    /**
     * @return array<string,mixed>
     */
    private function callbackUpdate(string $data): array
    {
        return [
            'update_id' => 33000 + random_int(1, 1000),
            'callback_query' => [
                'id' => 'interval-callback-1',
                'from' => [
                    'id' => 7001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'message' => [
                    'message_id' => 321,
                    'chat' => [
                        'id' => 2001,
                        'type' => 'private',
                    ],
                    'date' => now()->timestamp,
                    'text' => 'interval intro',
                ],
                'data' => $data,
            ],
        ];
    }
}
