<?php

namespace App\Services\Profile;

use App\Models\GameSession;
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
        $incorrectAnswers = max($totalWords - $correctAnswers, 0);
        $accuracyPercentage = $totalWords > 0
            ? round(($correctAnswers / $totalWords) * 100, 1)
            : null;

        return [
            'sessions_count' => $sessionsCount,
            'first_finished_at' => $summary?->first_finished_at,
            'last_finished_at' => $summary?->last_finished_at,
            'preferred_mode' => $this->preferredModeLabel($finishedSessionsQuery, $sessionsCount),
            'preferred_direction' => $this->preferredDirectionLabel($finishedSessionsQuery, $sessionsCount),
            'total_words' => $totalWords,
            'incorrect_answers' => $incorrectAnswers,
            'correct_answers' => $correctAnswers,
            'accuracy_percentage' => $accuracyPercentage,
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameSession> $finishedSessionsQuery
     */
    private function preferredModeLabel($finishedSessionsQuery, int $sessionsCount): ?string
    {
        if ($sessionsCount === 0) {
            return null;
        }

        $modeCounts = (clone $finishedSessionsQuery)
            ->select('mode', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mode')
            ->orderByDesc('aggregate')
            ->orderBy('mode')
            ->get();

        if ($modeCounts->count() > 1 && (int) $modeCounts[0]->aggregate === (int) $modeCounts[1]->aggregate) {
            return __('profile.statistics.mode.both_equally');
        }

        return match ($modeCounts->first()?->mode) {
            GameSession::MODE_CHOICE => __('profile.statistics.mode.choice'),
            GameSession::MODE_MANUAL => __('profile.statistics.mode.manual'),
            default => null,
        };
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameSession> $finishedSessionsQuery
     */
    private function preferredDirectionLabel($finishedSessionsQuery, int $sessionsCount): ?string
    {
        if ($sessionsCount === 0) {
            return null;
        }

        $directionCounts = (clone $finishedSessionsQuery)
            ->select('direction', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('direction')
            ->orderByDesc('aggregate')
            ->orderBy('direction')
            ->get();

        if ($directionCounts->count() > 1 && (int) $directionCounts[0]->aggregate === (int) $directionCounts[1]->aggregate) {
            return __('profile.statistics.direction.both_equally');
        }

        return match ($directionCounts->first()?->direction) {
            GameSession::DIRECTION_FOREIGN_TO_RU => __('profile.statistics.direction.foreign_to_ru'),
            GameSession::DIRECTION_RU_TO_FOREIGN => __('profile.statistics.direction.ru_to_foreign'),
            default => null,
        };
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
