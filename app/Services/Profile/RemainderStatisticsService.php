<?php

namespace App\Services\Profile;

use App\Models\GameSession;
use App\Models\TelegramGameRun;
use Illuminate\Support\Facades\DB;

class RemainderStatisticsService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(?int $userId): array
    {
        if ($userId === null) {
            return $this->emptyStatistics();
        }

        $finishedSessionsQuery = GameSession::query()
            ->where('user_id', $userId)
            ->where('status', GameSession::STATUS_FINISHED);

        $summary = (clone $finishedSessionsQuery)
            ->selectRaw('COUNT(*) as sessions_count')
            ->selectRaw('MIN(finished_at) as first_finished_at')
            ->selectRaw('MAX(finished_at) as last_finished_at')
            ->selectRaw('COALESCE(SUM(total_words), 0) as total_words')
            ->selectRaw('COALESCE(SUM(correct_answers), 0) as correct_answers')
            ->first();

        $sessionsCount = (int) ($summary?->sessions_count ?? 0);
        $correctAnswers = (int) ($summary?->correct_answers ?? 0);
        $totalWords = (int) ($summary?->total_words ?? 0);
        $telegramSummary = TelegramGameRun::query()
            ->where('user_id', $userId)
            ->where('status', TelegramGameRun::STATUS_FINISHED)
            ->selectRaw('COUNT(*) as sessions_count')
            ->selectRaw('MIN(finished_at) as first_finished_at')
            ->selectRaw('MAX(finished_at) as last_finished_at')
            ->selectRaw('COALESCE(SUM(total_words), 0) as total_words')
            ->selectRaw('COALESCE(SUM(correct_answers), 0) as correct_answers')
            ->selectRaw('COALESCE(SUM(incorrect_answers), 0) as incorrect_answers')
            ->first();

        $sessionsCount += (int) ($telegramSummary?->sessions_count ?? 0);
        $correctAnswers += (int) ($telegramSummary?->correct_answers ?? 0);
        $totalWords += (int) ($telegramSummary?->total_words ?? 0);
        $incorrectAnswers = max(
            ($totalWords - $correctAnswers),
            0,
        );

        if ($telegramSummary !== null) {
            $incorrectAnswers = max(
                ((int) ($summary?->total_words ?? 0) - (int) ($summary?->correct_answers ?? 0))
                + (int) ($telegramSummary->incorrect_answers ?? 0),
                0,
            );
        }

        $accuracyPercentage = $totalWords > 0
            ? round(($correctAnswers / $totalWords) * 100, 1)
            : null;

        return [
            'sessions_count' => $sessionsCount,
            'first_finished_at' => $this->minDateTime($summary?->first_finished_at, $telegramSummary?->first_finished_at),
            'last_finished_at' => $this->maxDateTime($summary?->last_finished_at, $telegramSummary?->last_finished_at),
            'preferred_mode' => $this->preferredModeLabel($finishedSessionsQuery, $userId, $sessionsCount),
            'preferred_direction' => $this->preferredDirectionLabel($finishedSessionsQuery, $userId, $sessionsCount),
            'total_words' => $totalWords,
            'incorrect_answers' => $incorrectAnswers,
            'correct_answers' => $correctAnswers,
            'accuracy_percentage' => $accuracyPercentage,
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameSession> $finishedSessionsQuery
     */
    private function preferredModeLabel($finishedSessionsQuery, int $userId, int $sessionsCount): ?string
    {
        if ($sessionsCount === 0) {
            return null;
        }

        $modeCounts = (clone $finishedSessionsQuery)
            ->select('mode', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mode')
            ->get();

        $aggregates = collect([
            GameSession::MODE_MANUAL => (int) $modeCounts->firstWhere('mode', GameSession::MODE_MANUAL)?->aggregate,
            GameSession::MODE_CHOICE => (int) $modeCounts->firstWhere('mode', GameSession::MODE_CHOICE)?->aggregate
                + TelegramGameRun::query()
                    ->where('user_id', $userId)
                    ->where('status', TelegramGameRun::STATUS_FINISHED)
                    ->where('mode', GameSession::MODE_CHOICE)
                    ->count(),
        ])
            ->sortDesc()
            ->values();

        if ($aggregates->count() > 1 && $aggregates[0] === $aggregates[1] && $aggregates[0] > 0) {
            return __('profile.statistics.mode.both_equally');
        }

        $manualCount = (int) $modeCounts->firstWhere('mode', GameSession::MODE_MANUAL)?->aggregate;
        $choiceCount = (int) $modeCounts->firstWhere('mode', GameSession::MODE_CHOICE)?->aggregate
            + TelegramGameRun::query()
                ->where('user_id', $userId)
                ->where('status', TelegramGameRun::STATUS_FINISHED)
                ->where('mode', GameSession::MODE_CHOICE)
                ->count();

        return match (true) {
            $choiceCount > $manualCount => __('profile.statistics.mode.choice'),
            $manualCount > $choiceCount => __('profile.statistics.mode.manual'),
            default => null,
        };
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameSession> $finishedSessionsQuery
     */
    private function preferredDirectionLabel($finishedSessionsQuery, int $userId, int $sessionsCount): ?string
    {
        if ($sessionsCount === 0) {
            return null;
        }

        $directionCounts = (clone $finishedSessionsQuery)
            ->select('direction', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('direction')
            ->get();

        $foreignToRuCount = (int) $directionCounts->firstWhere('direction', GameSession::DIRECTION_FOREIGN_TO_RU)?->aggregate
            + TelegramGameRun::query()
                ->where('user_id', $userId)
                ->where('status', TelegramGameRun::STATUS_FINISHED)
                ->where('direction', GameSession::DIRECTION_FOREIGN_TO_RU)
                ->count();

        $ruToForeignCount = (int) $directionCounts->firstWhere('direction', GameSession::DIRECTION_RU_TO_FOREIGN)?->aggregate
            + TelegramGameRun::query()
                ->where('user_id', $userId)
                ->where('status', TelegramGameRun::STATUS_FINISHED)
                ->where('direction', GameSession::DIRECTION_RU_TO_FOREIGN)
                ->count();

        if ($foreignToRuCount === $ruToForeignCount && $foreignToRuCount > 0) {
            return __('profile.statistics.direction.both_equally');
        }

        return match (true) {
            $foreignToRuCount > $ruToForeignCount => __('profile.statistics.direction.foreign_to_ru'),
            $ruToForeignCount > $foreignToRuCount => __('profile.statistics.direction.ru_to_foreign'),
            default => null,
        };
    }

    private function minDateTime(mixed $left, mixed $right): mixed
    {
        if ($left === null) {
            return $right;
        }

        if ($right === null) {
            return $left;
        }

        return $left <= $right ? $left : $right;
    }

    private function maxDateTime(mixed $left, mixed $right): mixed
    {
        if ($left === null) {
            return $right;
        }

        if ($right === null) {
            return $left;
        }

        return $left >= $right ? $left : $right;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyStatistics(): array
    {
        return [
            'sessions_count' => 0,
            'first_finished_at' => null,
            'last_finished_at' => null,
            'preferred_mode' => null,
            'preferred_direction' => null,
            'total_words' => 0,
            'incorrect_answers' => 0,
            'correct_answers' => 0,
            'accuracy_percentage' => null,
        ];
    }
}
