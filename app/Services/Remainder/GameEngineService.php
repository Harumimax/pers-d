<?php

namespace App\Services\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameEngineService
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
        /** @var array{item: GameSessionItem, finished: bool} $result */
        $result = DB::transaction(function () use ($gameSession, $answer): array {
            /** @var GameSession $session */
            $session = GameSession::query()
                ->whereKey($gameSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== GameSession::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'answer' => __('remainder.messages.play.finished'),
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
                    'answer' => __('remainder.messages.play.finished'),
                ]);
            }

            [$storedAnswer, $isCorrect] = $this->evaluateAnswer($session, $currentItem, $answer);

            $currentItem->forceFill([
                'user_answer' => $storedAnswer,
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

    /**
     * @return array{0: string, 1: bool}
     */
    private function evaluateAnswer(GameSession $gameSession, GameSessionItem $currentItem, string $answer): array
    {
        if ($gameSession->mode === GameSession::MODE_CHOICE) {
            $selectedChoice = $this->sanitizeAnswer($answer);

            if ($selectedChoice === '') {
                throw ValidationException::withMessages([
                    'selectedChoice' => __('remainder.messages.play.choose_option'),
                ]);
            }

            $options = collect($currentItem->options_json ?? [])
                ->map(static fn ($option): string => (string) $option)
                ->values();

            if (! $options->contains($selectedChoice)) {
                throw ValidationException::withMessages([
                    'selectedChoice' => __('remainder.messages.play.choose_available_option'),
                ]);
            }

            return [
                $selectedChoice,
                $selectedChoice === $currentItem->correct_answer,
            ];
        }

        $sanitizedAnswer = $this->sanitizeAnswer($answer);

        if ($sanitizedAnswer === '') {
            throw ValidationException::withMessages([
                'answer' => __('remainder.messages.play.enter_translation'),
            ]);
        }

        return [
            $sanitizedAnswer,
            $this->normalizeForComparison($sanitizedAnswer) === $this->normalizeForComparison($currentItem->correct_answer),
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
