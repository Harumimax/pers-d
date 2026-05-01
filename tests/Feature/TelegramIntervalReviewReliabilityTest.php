<?php

namespace Tests\Feature;

use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewPlanWord;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewRunItem;
use App\Models\TelegramIntervalReviewSession;
use App\Models\TelegramProcessedUpdate;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramIntervalReviewReliabilityTest extends TestCase
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

    public function test_duplicate_interval_answer_update_is_skipped_without_second_side_effects(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 991]], 200),
        ]);

        [$run, $item] = $this->createInProgressIntervalRun();
        $payload = $this->callbackUpdate("interval_answer:{$run->id}:{$item->id}:0", 88001, 765);

        $this->postJson('/telegram/webhook/telegram-secret', $payload)->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $payload)->assertOk();

        $item->refresh();
        $run->refresh();

        $this->assertSame('яблоко', $item->user_answer);
        $this->assertTrue($item->is_correct);
        $this->assertSame(TelegramIntervalReviewRun::STATUS_FINISHED, $run->status);
        Http::assertSentCount(4);
        $this->assertDatabaseHas('telegram_processed_updates', [
            'telegram_update_id' => 88001,
            'status' => TelegramProcessedUpdate::STATUS_PROCESSED,
        ]);
    }

    public function test_interval_cleanup_command_marks_stale_runs_as_expired_and_abandoned(): void
    {
        [$expiredRun, $abandonedRun, $freshRun] = $this->createStaleIntervalRuns();

        $this->artisan('telegram:cleanup-stale-interval-review-runs')
            ->expectsOutput('Expired interval runs: 1. Abandoned interval runs: 1.')
            ->assertSuccessful();

        $expiredRun->refresh();
        $abandonedRun->refresh();
        $freshRun->refresh();

        $this->assertSame(TelegramIntervalReviewRun::STATUS_EXPIRED, $expiredRun->status);
        $this->assertSame('expired', $expiredRun->last_error_code);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_EXPIRED, $expiredRun->session->fresh()->status);

        $this->assertSame(TelegramIntervalReviewRun::STATUS_ABANDONED, $abandonedRun->status);
        $this->assertSame('abandoned', $abandonedRun->last_error_code);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_ABANDONED, $abandonedRun->session->fresh()->status);

        $this->assertSame(TelegramIntervalReviewRun::STATUS_IN_PROGRESS, $freshRun->status);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_IN_PROGRESS, $freshRun->session->fresh()->status);
    }

    public function test_dispatch_command_marks_interval_session_and_run_as_failed_when_intro_delivery_fails(): void
    {
        CarbonImmutable::setTestNow('2026-05-01 09:15:00 UTC');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Service unavailable'], 503),
        ]);

        [$plan, $session] = $this->createActiveIntervalPlan('English', '2026-05-01 09:15:00 UTC', 1);

        $this->artisan('telegram:dispatch-interval-review-sessions')->assertSuccessful();

        $run = TelegramIntervalReviewRun::query()->firstOrFail();
        $run->refresh();
        $session->refresh();

        $this->assertSame($plan->id, $run->telegram_interval_review_plan_id);
        $this->assertSame(TelegramIntervalReviewRun::STATUS_FAILED, $run->status);
        $this->assertSame('dispatch_failed', $run->last_error_code);
        $this->assertNotNull($run->last_error_at);
        $this->assertSame(TelegramIntervalReviewSession::STATUS_FAILED, $session->status);

        Http::assertSentCount(3);
    }

    /**
     * @return array{0:TelegramIntervalReviewRun,1:TelegramIntervalReviewRunItem}
     */
    private function createInProgressIntervalRun(): array
    {
        $user = User::factory()->create([
            'tg_chat_id' => '4001',
            'tg_linked_at' => now(),
        ]);

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Interval user pool',
            'language' => 'English',
        ]);

        $word = Word::query()->create([
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
        ]);
        $userDictionary->words()->attach($word->id);

        $readyDictionary = ReadyDictionary::factory()->create([
            'name' => 'Ready pool',
            'language' => 'English',
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

        $plan = TelegramIntervalReviewPlan::query()->create([
            'user_id' => $user->id,
            'status' => TelegramIntervalReviewPlan::STATUS_ACTIVE,
            'language' => 'English',
            'start_time' => '09:15',
            'timezone' => 'UTC',
            'words_count' => 1,
            'completed_sessions_count' => 0,
        ]);

        $planWord = TelegramIntervalReviewPlanWord::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'source_type' => 'user',
            'source_dictionary_id' => $userDictionary->id,
            'source_word_id' => $word->id,
            'dictionary_name' => 'Plan source',
            'language' => 'English',
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => null,
            'position' => 1,
        ]);

        $session = TelegramIntervalReviewSession::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'session_number' => 1,
            'scheduled_for' => now(),
            'status' => TelegramIntervalReviewSession::STATUS_IN_PROGRESS,
        ]);

        $run = TelegramIntervalReviewRun::query()->create([
            'user_id' => $user->id,
            'telegram_interval_review_plan_id' => $plan->id,
            'telegram_interval_review_session_id' => $session->id,
            'session_number' => 1,
            'total_words' => 1,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramIntervalReviewRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now(),
            'intro_message_id' => 321,
            'started_at' => now(),
            'last_interaction_at' => now(),
            'config_snapshot' => [
                'language' => 'English',
                'mode' => 'choice',
            ],
        ]);

        $item = $run->items()->create([
            'telegram_interval_review_plan_word_id' => $planWord->id,
            'order_index' => 1,
            'word_snapshot' => 'apple',
            'translation_snapshot' => 'яблоко',
            'part_of_speech_snapshot' => 'noun',
            'comment_snapshot' => null,
            'prompt_text' => 'apple',
            'correct_answer' => 'яблоко',
            'source_type_snapshot' => 'user',
            'options_json' => ['яблоко', 'река', 'море', 'стол', 'окно', 'трава'],
        ]);

        return [$run->fresh(['user', 'items', 'session', 'plan.sessions']), $item];
    }

    /**
     * @return array{0:TelegramIntervalReviewRun,1:TelegramIntervalReviewRun,2:TelegramIntervalReviewRun}
     */
    private function createStaleIntervalRuns(): array
    {
        $user = User::factory()->create([
            'tg_chat_id' => '5001',
            'tg_linked_at' => now(),
        ]);

        $plan = TelegramIntervalReviewPlan::query()->create([
            'user_id' => $user->id,
            'status' => TelegramIntervalReviewPlan::STATUS_ACTIVE,
            'language' => 'English',
            'start_time' => '09:15',
            'timezone' => 'UTC',
            'words_count' => 1,
        ]);

        $expiredSession = TelegramIntervalReviewSession::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'session_number' => 1,
            'scheduled_for' => now()->subHours(13),
            'status' => TelegramIntervalReviewSession::STATUS_AWAITING_START,
        ]);

        $abandonedSession = TelegramIntervalReviewSession::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'session_number' => 2,
            'scheduled_for' => now()->subHours(13),
            'status' => TelegramIntervalReviewSession::STATUS_IN_PROGRESS,
        ]);

        $freshSession = TelegramIntervalReviewSession::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'session_number' => 3,
            'scheduled_for' => now()->subHour(),
            'status' => TelegramIntervalReviewSession::STATUS_IN_PROGRESS,
        ]);

        $expiredRun = TelegramIntervalReviewRun::query()->create([
            'user_id' => $user->id,
            'telegram_interval_review_plan_id' => $plan->id,
            'telegram_interval_review_session_id' => $expiredSession->id,
            'session_number' => 1,
            'total_words' => 1,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramIntervalReviewRun::STATUS_AWAITING_START,
            'scheduled_for' => now()->subHours(13),
            'intro_message_sent_at' => now()->subHours(13),
            'config_snapshot' => [],
        ]);

        $abandonedRun = TelegramIntervalReviewRun::query()->create([
            'user_id' => $user->id,
            'telegram_interval_review_plan_id' => $plan->id,
            'telegram_interval_review_session_id' => $abandonedSession->id,
            'session_number' => 2,
            'total_words' => 1,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramIntervalReviewRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now()->subHours(13),
            'started_at' => now()->subHours(13),
            'last_interaction_at' => now()->subHours(13),
            'config_snapshot' => [],
        ]);

        $freshRun = TelegramIntervalReviewRun::query()->create([
            'user_id' => $user->id,
            'telegram_interval_review_plan_id' => $plan->id,
            'telegram_interval_review_session_id' => $freshSession->id,
            'session_number' => 3,
            'total_words' => 1,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'status' => TelegramIntervalReviewRun::STATUS_IN_PROGRESS,
            'scheduled_for' => now()->subHour(),
            'started_at' => now()->subHour(),
            'last_interaction_at' => now()->subHour(),
            'config_snapshot' => [],
        ]);

        return [$expiredRun, $abandonedRun, $freshRun];
    }

    /**
     * @return array{0:TelegramIntervalReviewPlan,1:TelegramIntervalReviewSession}
     */
    private function createActiveIntervalPlan(string $language, string $scheduledFor, int $sessionNumber): array
    {
        $user = User::factory()->create([
            'tg_chat_id' => '6001',
            'tg_linked_at' => now(),
        ]);

        $userDictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'User pool',
            'language' => $language,
        ]);

        foreach ([
            ['word' => 'apple', 'translation' => 'яблоко'],
            ['word' => 'book', 'translation' => 'книга'],
            ['word' => 'cloud', 'translation' => 'облако'],
        ] as $wordData) {
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
            'source_dictionary_id' => $userDictionary->id,
            'source_word_id' => 1,
            'dictionary_name' => 'Plan source',
            'language' => $language,
            'word' => 'apple',
            'translation' => 'яблоко',
            'part_of_speech' => 'noun',
            'comment' => 'Fruit',
            'position' => 1,
        ]);

        TelegramIntervalReviewPlanWord::query()->create([
            'telegram_interval_review_plan_id' => $plan->id,
            'source_type' => 'user',
            'source_dictionary_id' => $userDictionary->id,
            'source_word_id' => 2,
            'dictionary_name' => 'Plan source',
            'language' => $language,
            'word' => 'book',
            'translation' => 'книга',
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
     * @return array<string, mixed>
     */
    private function callbackUpdate(string $data, int $updateId, int $messageId): array
    {
        return [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'interval-reliability-'.$updateId,
                'from' => [
                    'id' => 9001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'message' => [
                    'message_id' => $messageId,
                    'chat' => [
                        'id' => 4001,
                        'type' => 'private',
                    ],
                    'date' => now()->timestamp,
                    'text' => 'interval reliability',
                ],
                'data' => $data,
            ],
        ];
    }
}
