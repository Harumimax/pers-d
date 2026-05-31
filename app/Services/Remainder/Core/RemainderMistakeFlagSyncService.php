<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramGameRun;
use App\Models\UserWordProgress;
use Illuminate\Support\Collection;

class RemainderMistakeFlagSyncService
{
    public function sync(GameSession $session): void
    {
        if ($session->isDemo() || $session->user_id === null) {
            return;
        }

        $this->syncUserWordIds(
            (int) $session->user_id,
            $this->sessionUserWordIds($session, false),
            $this->sessionUserWordIds($session, true),
        );
    }

    public function syncTelegramRun(TelegramGameRun $run): void
    {
        if ($run->user_id === null) {
            return;
        }

        $this->syncUserWordIds(
            (int) $run->user_id,
            $this->telegramRunUserWordIds($run, false),
            $this->telegramRunUserWordIds($run, true),
        );
    }

    public function syncTelegramIntervalReviewRun(TelegramIntervalReviewRun $run): void
    {
        if ($run->user_id === null) {
            return;
        }

        $this->syncUserWordIds(
            (int) $run->user_id,
            $this->telegramIntervalReviewRunUserWordIds($run, false),
            $this->telegramIntervalReviewRunUserWordIds($run, true),
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function sessionUserWordIds(GameSession $session, bool $isCorrect): Collection
    {
        return $session->items()
            ->where('source_type_snapshot', 'user')
            ->where('is_correct', $isCorrect)
            ->whereNotNull('word_id')
            ->pluck('word_id')
            ->map(static fn ($wordId): int => (int) $wordId)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function telegramRunUserWordIds(TelegramGameRun $run, bool $isCorrect): Collection
    {
        return $run->items()
            ->where('source_type_snapshot', 'user')
            ->where('is_correct', $isCorrect)
            ->whereNotNull('word_id')
            ->pluck('word_id')
            ->map(static fn ($wordId): int => (int) $wordId)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function telegramIntervalReviewRunUserWordIds(TelegramIntervalReviewRun $run, bool $isCorrect): Collection
    {
        return $run->items()
            ->where('is_correct', $isCorrect)
            ->with('planWord:id,source_type,source_word_id')
            ->get()
            ->filter(static function ($item): bool {
                return $item->planWord?->source_type === 'user'
                    && $item->planWord?->source_word_id !== null;
            })
            ->map(static fn ($item): int => (int) $item->planWord->source_word_id)
            ->unique()
            ->values();
    }

    /**
     * @param Collection<int, int> $incorrectWordIds
     * @param Collection<int, int> $correctWordIds
     */
    private function syncUserWordIds(int $userId, Collection $incorrectWordIds, Collection $correctWordIds): void
    {
        if ($incorrectWordIds->isNotEmpty()) {
            $timestamp = now();

            UserWordProgress::query()->upsert(
                $incorrectWordIds
                    ->map(static fn (int $wordId): array => [
                        'user_id' => $userId,
                        'word_id' => $wordId,
                        'remainder_had_mistake' => true,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])
                    ->all(),
                ['user_id', 'word_id'],
                ['remainder_had_mistake', 'updated_at'],
            );
        }

        if ($correctWordIds->isEmpty()) {
            return;
        }

        UserWordProgress::query()
            ->where('user_id', $userId)
            ->whereIn('word_id', $correctWordIds->all())
            ->where('remainder_had_mistake', true)
            ->update([
                'remainder_had_mistake' => false,
                'updated_at' => now(),
            ]);
    }
}
