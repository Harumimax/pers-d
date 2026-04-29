<?php

namespace App\Console\Commands;

use App\Models\TelegramGameRun;
use App\Services\Telegram\CreateTelegramGameRunService;
use App\Services\Telegram\TelegramGameRunMonitorService;
use App\Services\Telegram\TelegramGameRunNotifier;
use App\Services\Telegram\TelegramRunConflictResolverService;
use App\Services\Telegram\TelegramScheduledSessionLocator;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DispatchScheduledTelegramSessionsCommand extends Command
{
    protected $signature = 'telegram:dispatch-scheduled-sessions';

    protected $description = 'Create and dispatch due Telegram scheduled sessions.';

    public function handle(
        TelegramScheduledSessionLocator $locator,
        CreateTelegramGameRunService $createTelegramGameRunService,
        TelegramGameRunNotifier $notifier,
        TelegramGameRunMonitorService $telegramGameRunMonitorService,
        TelegramRunConflictResolverService $telegramRunConflictResolverService,
    ): int {
        $createdRuns = 0;
        $skippedRuns = 0;

        foreach ($locator->dueSessions() as $dueSession) {
            $setting = $dueSession['setting'];
            $session = $dueSession['session'];
            $scheduledFor = $dueSession['scheduled_for'];
            $user = $setting->user;
            $run = null;

            try {
                $telegramRunConflictResolverService->expireRunsBeforeSchedulingNext($user, $scheduledFor);
                $run = $createTelegramGameRunService->create($user, $setting, $session, $scheduledFor);
                $notifier->sendIntro($run);

                Log::info('telegram.scheduler.run_dispatched', [
                    'telegram_game_run_id' => $run->id,
                    'user_id' => $user->id,
                    'telegram_random_word_session_id' => $session->id,
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                ]);

                $createdRuns++;
            } catch (QueryException $exception) {
                if ($this->isDuplicateScheduleConstraint($exception)) {
                    Log::info('telegram.scheduler.duplicate_run_skipped', [
                        'user_id' => $user->id,
                        'telegram_random_word_session_id' => $session->id,
                        'scheduled_for' => $scheduledFor->toIso8601String(),
                    ]);

                    $skippedRuns++;

                    continue;
                }

                report($exception);
                $this->error('Не удалось создать scheduled Telegram-сессию: '.$exception->getMessage());
            } catch (ValidationException $exception) {
                Log::warning('telegram.scheduler.validation_skipped', [
                    'user_id' => $user->id,
                    'telegram_random_word_session_id' => $session->id,
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                    'errors' => $exception->errors(),
                ]);

                report($exception);
                $this->warn('Пропущена Telegram-сессия из-за ошибки конфигурации или пустой выборки слов.');
            } catch (Throwable $exception) {
                if ($run instanceof TelegramGameRun) {
                    $telegramGameRunMonitorService->recordFailure(
                        $run,
                        'dispatch_failed',
                        $exception->getMessage(),
                        TelegramGameRun::STATUS_FAILED,
                    );
                }

                Log::error('telegram.scheduler.dispatch_failed', [
                    'telegram_game_run_id' => $run?->id,
                    'user_id' => $user->id,
                    'telegram_random_word_session_id' => $session->id,
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                    'message' => $exception->getMessage(),
                ]);

                report($exception);
                $this->error('Ошибка dispatch Telegram-сессии: '.$exception->getMessage());
            }
        }

        $this->info("Создано Telegram-сессий: {$createdRuns}. Пропущено дублей: {$skippedRuns}.");

        return self::SUCCESS;
    }

    private function isDuplicateScheduleConstraint(QueryException $exception): bool
    {
        return str_contains((string) $exception->getMessage(), 'telegram_game_runs_unique_schedule');
    }
}
