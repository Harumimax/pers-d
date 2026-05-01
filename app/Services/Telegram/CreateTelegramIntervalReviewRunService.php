<?php

namespace App\Services\Telegram;

use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\Word;
use App\Services\Remainder\ChoiceOptionsBuilder;
use App\Services\Remainder\Core\GameItemSnapshotFactory;
use App\Services\Remainder\Core\GameSessionConfigData;
use App\Models\GameSession;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewRun;
use App\Models\TelegramIntervalReviewSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateTelegramIntervalReviewRunService
{
    public function __construct(
        private readonly TelegramIntervalReviewOptionsBuilder $telegramIntervalReviewOptionsBuilder,
        private readonly GameItemSnapshotFactory $gameItemSnapshotFactory,
    ) {
    }

    public function create(TelegramIntervalReviewPlan $plan, TelegramIntervalReviewSession $session): TelegramIntervalReviewRun
    {
        return DB::transaction(function () use ($plan, $session): TelegramIntervalReviewRun {
            $plan->loadMissing(['user', 'words']);
            $session->loadMissing('plan');

            $selectedWords = $plan->words
                ->map(fn ($word): array => [
                    'source' => (string) $word->source_type,
                    'word_id' => null,
                    'word' => (string) $word->word,
                    'translation' => (string) $word->translation,
                    'part_of_speech' => $word->part_of_speech !== null && trim((string) $word->part_of_speech) !== ''
                        ? (string) $word->part_of_speech
                        : null,
                    'comment' => $word->comment !== null && trim((string) $word->comment) !== ''
                        ? (string) $word->comment
                        : null,
                    'remainder_had_mistake' => false,
                ])
                ->values();

            $config = new GameSessionConfigData(
                mode: GameSession::MODE_CHOICE,
                direction: GameSession::DIRECTION_FOREIGN_TO_RU,
                dictionaryIds: [],
                readyDictionaryIds: [],
                partsOfSpeech: ['all'],
                requestedWordsCount: $selectedWords->count(),
            );

            $itemPayloads = $this->gameItemSnapshotFactory->build($config, $selectedWords);
            $itemsWithOptions = $this->telegramIntervalReviewOptionsBuilder->build(
                $plan->user,
                $plan,
                $itemPayloads,
            );

            $run = TelegramIntervalReviewRun::query()->create([
                'user_id' => $plan->user_id,
                'telegram_interval_review_plan_id' => $plan->id,
                'telegram_interval_review_session_id' => $session->id,
                'session_number' => $session->session_number,
                'total_words' => $plan->words->count(),
                'status' => TelegramIntervalReviewRun::STATUS_SCHEDULED,
                'scheduled_for' => $session->scheduled_for,
                'config_snapshot' => [
                    'language' => $plan->language,
                    'timezone' => $plan->timezone,
                    'start_time' => (string) $plan->start_time,
                    'session_number' => $session->session_number,
                    'direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
                    'mode' => GameSession::MODE_CHOICE,
                ],
            ]);

            $planWords = $plan->words->values();

            foreach ($itemsWithOptions->values() as $index => $item) {
                $planWord = $planWords->get($index);

                $run->items()->create([
                    'telegram_interval_review_plan_word_id' => $planWord?->id,
                    'order_index' => (int) $item['order_index'],
                    'word_snapshot' => (string) $item['word'],
                    'translation_snapshot' => (string) $item['translation'],
                    'part_of_speech_snapshot' => $item['part_of_speech_snapshot'],
                    'comment_snapshot' => $item['comment'],
                    'prompt_text' => (string) $item['prompt_text'],
                    'correct_answer' => (string) $item['correct_answer'],
                    'source_type_snapshot' => (string) $item['source'],
                    'options_json' => $item['options_json'],
                ]);
            }

            return $run->fresh(['user', 'plan', 'session', 'items']);
        });
    }
}
