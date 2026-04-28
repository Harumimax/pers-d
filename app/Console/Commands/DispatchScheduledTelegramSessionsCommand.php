<?php

namespace App\Console\Commands;

use App\Models\TelegramGameRun;
use App\Services\Telegram\CreateTelegramGameRunService;
use App\Services\Telegram\TelegramGameRunNotifier;
use App\Services\Telegram\TelegramScheduledSessionLocator;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
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
                $run = $createTelegramGameRunService->create($user, $setting, $session, $scheduledFor);
                $notifier->sendIntro($run);
                $createdRuns++;
            } catch (QueryException $exception) {
                if ($this->isDuplicateScheduleConstraint($exception)) {
                    $skippedRuns++;

                    continue;
                }

                report($exception);
                $this->error('Не удалось создать scheduled Telegram-сессию: '.$exception->getMessage());
            } catch (ValidationException $exception) {
                report($exception);
                $this->warn('Пропущена Telegram-сессия из-за ошибки конфигурации или пустой выборки слов.');
            } catch (Throwable $exception) {
                if (isset($run) && $run instanceof TelegramGameRun) {
                    $run->forceFill([
                        'status' => TelegramGameRun::STATUS_FAILED,
                    ])->save();
                }

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
