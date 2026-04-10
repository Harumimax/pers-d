<?php

namespace App\Services\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualGameEngineService
{
    private const ZERO_WIDTH_CHARACTER_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u';

    public function currentItem(GameSession $gameSession): ?GameSessionItem
    {
        return $gameSession->items()
            ->whereNull('answered_at')
            ->orderBy('order_index')
            ->first();
    }

    /**
     * @return array{item: GameSessionItem, finished: bool}
     */
    public function submitAnswer(GameSession $gameSession, string $answer): array
    {
        $sanitizedAnswer = $this->sanitizeAnswer($answer);

        if ($sanitizedAnswer === '') {
            throw ValidationException::withMessages([
                'answer' => 'Enter your translation before submitting.',
            ]);
        }

        /** @var array{item: GameSessionItem, finished: bool} $result */
        $result = DB::transaction(function () use ($gameSession, $sanitizedAnswer): array {
            /** @var GameSession $session */
            $session = GameSession::query()
                ->whereKey($gameSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== GameSession::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'answer' => 'This game session is already finished.',
                ]);
            }

            /** @var GameSessionItem|null $currentItem */
            $currentItem = GameSessionItem::query()
                ->where('game_session_id', $session->id)
                ->whereNull('answered_at')
                ->orderBy('order_index')
                ->lockForUpdate()
                ->first();

            if ($currentItem === null) {
                $session->forceFill([
                    'status' => GameSession::STATUS_FINISHED,
                    'finished_at' => now(),
                ])->save();

                throw ValidationException::withMessages([
                    'answer' => 'This game session is already finished.',
                ]);
            }

            $isCorrect = $this->normalizeForComparison($sanitizedAnswer) === $this->normalizeForComparison($currentItem->correct_answer);

            $currentItem->forceFill([
                'user_answer' => $sanitizedAnswer,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
            ])->save();

            if ($isCorrect) {
                $session->increment('correct_answers');
                $session->refresh();
            }

            $hasRemainingItems = GameSessionItem::query()
                ->where('game_session_id', $session->id)
                ->whereNull('answered_at')
                ->exists();

            if (! $hasRemainingItems) {
                $session->forceFill([
                    'status' => GameSession::STATUS_FINISHED,
                    'finished_at' => now(),
                ])->save();
            }

            return [
                'item' => $currentItem->fresh(),
                'finished' => ! $hasRemainingItems,
            ];
        });

        return $result;
    }

    /**
     * @return array{correct_answers:int,total_words:int,incorrect_items:Collection<int, GameSessionItem>}
     */
    public function resultSummary(GameSession $gameSession): array
    {
        /** @var Collection<int, GameSessionItem> $incorrectItems */
        $incorrectItems = $gameSession->items()
            ->where('is_correct', false)
            ->orderBy('order_index')
            ->get();

        return [
            'correct_answers' => (int) $gameSession->correct_answers,
            'total_words' => (int) $gameSession->total_words,
            'incorrect_items' => $incorrectItems,
        ];
    }

    private function sanitizeAnswer(string $answer): string
    {
        $withoutZeroWidth = preg_replace(self::ZERO_WIDTH_CHARACTER_PATTERN, '', $answer) ?? $answer;

        return trim($withoutZeroWidth);
    }

    private function normalizeForComparison(string $value): string
    {
        return mb_strtolower($this->sanitizeAnswer($value));
    }
}
