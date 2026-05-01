<?php

namespace App\Console\Commands;

use App\Models\TelegramIntervalReviewRun;
use App\Services\Telegram\CreateTelegramIntervalReviewRunService;
use App\Services\Telegram\TelegramIntervalReviewDueSessionLocator;
use App\Services\Telegram\TelegramIntervalReviewRunNotifier;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DispatchScheduledTelegramIntervalReviewSessionsCommand extends Command
{
    protected $signature = 'telegram:dispatch-interval-review-sessions';

    protected $description = 'Create and dispatch due Telegram interval review sessions.';

    public function handle(
        TelegramIntervalReviewDueSessionLocator $locator,
        CreateTelegramIntervalReviewRunService $createTelegramIntervalReviewRunService,
        TelegramIntervalReviewRunNotifier $notifier,
    ): int {
        $createdRuns = 0;
        $skippedRuns = 0;

        foreach ($locator->dueSessions() as $session) {
            $plan = $session->plan;
            $run = null;

            try {
                $run = $createTelegramIntervalReviewRunService->create($plan, $session);
                $notifier->sendIntro($run);

                Log::info('telegram.interval_review.run_dispatched', [
                    'telegram_interval_review_run_id' => $run->id,
                    'user_id' => $plan->user_id,
                    'telegram_interval_review_plan_id' => $plan->id,
                    'telegram_interval_review_session_id' => $session->id,
                    'scheduled_for' => $session->scheduled_for?->toIso8601String(),
                ]);

                $createdRuns++;
            } catch (QueryException $exception) {
                if ($this->isDuplicateScheduleConstraint($exception)) {
                    Log::info('telegram.interval_review.duplicate_run_skipped', [
                        'user_id' => $plan->user_id,
                        'telegram_interval_review_plan_id' => $plan->id,
                        'telegram_interval_review_session_id' => $session->id,
                    ]);

                    $skippedRuns++;

                    continue;
                }

                report($exception);
                $this->error('Не удалось создать interval review Telegram-сессию: '.$exception->getMessage());
            } catch (ValidationException $exception) {
                Log::warning('telegram.interval_review.validation_skipped', [
                    'user_id' => $plan->user_id,
                    'telegram_interval_review_plan_id' => $plan->id,
                    'telegram_interval_review_session_id' => $session->id,
                    'errors' => $exception->errors(),
                ]);

                report($exception);
                $this->warn('Пропущена interval review Telegram-сессия из-за пустого или некорректного пула вариантов ответа.');
            } catch (Throwable $exception) {
                if ($run instanceof TelegramIntervalReviewRun) {
                    $run->forceFill([
                        'status' => TelegramIntervalReviewRun::STATUS_FAILED,
                        'last_error_code' => 'dispatch_failed',
                        'last_error_message' => $exception->getMessage(),
                        'last_error_at' => now(),
                    ])->save();
                }

                Log::error('telegram.interval_review.dispatch_failed', [
                    'telegram_interval_review_run_id' => $run?->id,
                    'user_id' => $plan->user_id,
                    'telegram_interval_review_plan_id' => $plan->id,
                    'telegram_interval_review_session_id' => $session->id,
                    'message' => $exception->getMessage(),
                ]);

                report($exception);
                $this->error('Ошибка dispatch interval review Telegram-сессии: '.$exception->getMessage());
            }
        }

        $this->info("Создано interval review Telegram-сессий: {$createdRuns}. Пропущено дублей: {$skippedRuns}.");

        return self::SUCCESS;
    }

    private function isDuplicateScheduleConstraint(QueryException $exception): bool
    {
        return str_contains((string) $exception->getMessage(), 'tir_runs_unique_session');
    }
}
