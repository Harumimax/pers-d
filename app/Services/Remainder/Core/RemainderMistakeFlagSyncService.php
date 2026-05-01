<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramGameRun;
use App\Models\Word;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RemainderMistakeFlagSyncService
{
    public function sync(GameSession $session): void
    {
        if ($session->isDemo()) {
            return;
        }

        $this->sessionUserWordsQuery($session, false)
            ->update(['remainder_had_mistake' => true]);

        $this->sessionUserWordsQuery($session, true)
            ->where('words.remainder_had_mistake', true)
            ->update(['remainder_had_mistake' => false]);
    }

    public function syncTelegramRun(TelegramGameRun $run): void
    {
        if ($run->user_id === null) {
            return;
        }

        $this->telegramRunUserWordsQuery($run, false)
            ->update(['remainder_had_mistake' => true]);

        $this->telegramRunUserWordsQuery($run, true)
            ->where('words.remainder_had_mistake', true)
            ->update(['remainder_had_mistake' => false]);
    }

    public function syncTelegramIntervalReviewRun(TelegramIntervalReviewRun $run): void
    {
        if ($run->user_id === null) {
            return;
        }

        $this->telegramIntervalReviewRunUserWordsQuery($run, false)
            ->update(['remainder_had_mistake' => true]);

        $this->telegramIntervalReviewRunUserWordsQuery($run, true)
            ->where('words.remainder_had_mistake', true)
            ->update(['remainder_had_mistake' => false]);
    }

    private function sessionUserWordsQuery(GameSession $session, bool $isCorrect): Builder
    {
        return Word::query()
            ->whereIn('words.id', function ($query) use ($session, $isCorrect): void {
                $query->select('game_session_items.word_id')
                    ->from('game_session_items')
                    ->where('game_session_items.game_session_id', $session->id)
                    ->where('game_session_items.is_correct', $isCorrect);
            })
            ->whereExists(function ($query) use ($session): void {
                $query->select(DB::raw(1))
                    ->from('user_dictionary_word')
                    ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
                    ->whereColumn('user_dictionary_word.word_id', 'words.id')
                    ->where('user_dictionaries.user_id', $session->user_id);
            });
    }

    private function telegramRunUserWordsQuery(TelegramGameRun $run, bool $isCorrect): Builder
    {
        return Word::query()
            ->whereIn('words.id', function ($query) use ($run, $isCorrect): void {
                $query->select('telegram_game_run_items.word_id')
                    ->from('telegram_game_run_items')
                    ->where('telegram_game_run_items.telegram_game_run_id', $run->id)
                    ->where('telegram_game_run_items.is_correct', $isCorrect)
                    ->where('telegram_game_run_items.source_type_snapshot', 'user');
            })
            ->whereExists(function ($query) use ($run): void {
                $query->select(DB::raw(1))
                    ->from('user_dictionary_word')
                    ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
                    ->whereColumn('user_dictionary_word.word_id', 'words.id')
                    ->where('user_dictionaries.user_id', $run->user_id);
            });
    }

    private function telegramIntervalReviewRunUserWordsQuery(TelegramIntervalReviewRun $run, bool $isCorrect): Builder
    {
        return Word::query()
            ->whereIn('words.id', function ($query) use ($run, $isCorrect): void {
                $query->select('telegram_interval_review_plan_words.source_word_id')
                    ->from('telegram_interval_review_run_items')
                    ->join(
                        'telegram_interval_review_plan_words',
                        'telegram_interval_review_plan_words.id',
                        '=',
                        'telegram_interval_review_run_items.telegram_interval_review_plan_word_id'
                    )
                    ->where('telegram_interval_review_run_items.telegram_interval_review_run_id', $run->id)
                    ->where('telegram_interval_review_run_items.is_correct', $isCorrect)
                    ->where('telegram_interval_review_plan_words.source_type', 'user');
            })
            ->whereExists(function ($query) use ($run): void {
                $query->select(DB::raw(1))
                    ->from('user_dictionary_word')
                    ->join('user_dictionaries', 'user_dictionaries.id', '=', 'user_dictionary_word.user_dictionary_id')
                    ->whereColumn('user_dictionary_word.word_id', 'words.id')
                    ->where('user_dictionaries.user_id', $run->user_id);
            });
    }
}
