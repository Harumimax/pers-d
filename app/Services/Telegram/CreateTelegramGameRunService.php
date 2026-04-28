<?php

namespace App\Services\Telegram;

use App\Models\TelegramGameRun;
use App\Models\TelegramGameRunItem;
use App\Models\TelegramRandomWordSession;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Models\Word;
use App\Services\Remainder\ChoiceOptionsBuilder;
use App\Services\Remainder\Core\GameItemSnapshotFactory;
use App\Services\Remainder\Core\GameWordSelectionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTelegramGameRunService
{
    public function __construct(
        private readonly TelegramGameConfigFactory $configFactory,
        private readonly GameWordSelectionService $gameWordSelectionService,
        private readonly GameItemSnapshotFactory $gameItemSnapshotFactory,
        private readonly ChoiceOptionsBuilder $choiceOptionsBuilder,
    ) {
    }

    public function create(User $user, TelegramSetting $setting, TelegramRandomWordSession $session, CarbonImmutable $scheduledFor): TelegramGameRun
    {
        $config = $this->configFactory->fromRandomWordSession($session);
        $availableWords = $this->gameWordSelectionService->availableWordCandidates($user, $config);

        if ($availableWords->isEmpty()) {
            throw ValidationException::withMessages([
                'telegram' => 'Не удалось подготовить Telegram-сессию: для выбранных словарей и фильтров нет слов.',
            ]);
        }

        $targetWordsCount = min($config->requestedWordsCount, $availableWords->count());
        $selectedWords = $this->gameWordSelectionService->selectWordsForSession($availableWords, $targetWordsCount);
        $itemPayloads = $this->gameItemSnapshotFactory->build($config, $selectedWords);
        $warnings = [];

        $availableAnswers = $availableWords
            ->map(fn (array $word): string => $config->direction === 'foreign_to_ru' ? $word['translation'] : $word['word'])
            ->values();

        $choicePayload = $this->choiceOptionsBuilder->build($itemPayloads, $availableAnswers);
        $itemPayloads = $choicePayload['items'];
        $warnings = $choicePayload['warnings'];

        /** @var TelegramGameRun $run */
        $run = DB::transaction(function () use ($user, $setting, $session, $scheduledFor, $config, $selectedWords, $itemPayloads, $warnings): TelegramGameRun {
            $run = TelegramGameRun::query()->create([
                'user_id' => $user->id,
                'telegram_setting_id' => $setting->id,
                'telegram_random_word_session_id' => $session->id,
                'mode' => $config->mode,
                'direction' => $config->direction,
                'total_words' => $selectedWords->count(),
                'correct_answers' => 0,
                'incorrect_answers' => 0,
                'status' => TelegramGameRun::STATUS_SCHEDULED,
                'scheduled_for' => $scheduledFor,
                'config_snapshot' => [
                    'requested_words_count' => $config->requestedWordsCount,
                    'actual_words_count' => $selectedWords->count(),
                    'dictionary_ids' => $config->dictionaryIds,
                    'ready_dictionary_ids' => $config->readyDictionaryIds,
                    'parts_of_speech' => $config->partsOfSpeech,
                    'warnings' => $warnings,
                    'options_target_count' => 6,
                ],
            ]);

            $items = $this->prepareRunItems($run, $itemPayloads);
            TelegramGameRunItem::query()->insert($items);

            return $run->fresh(['items', 'user']);
        });

        return $run;
    }

    /**
     * @param Collection<int, array{source:string,word_id:int|null,word:string,translation:string,comment:?string,order_index:int,prompt_text:string,part_of_speech_snapshot:?string,correct_answer:string,options_json:null|array<int,string>,user_answer:null|string,is_correct:null|bool,answered_at:mixed}> $itemPayloads
     * @return array<int, array<string, mixed>>
     */
    private function prepareRunItems(TelegramGameRun $run, Collection $itemPayloads): array
    {
        return $itemPayloads
            ->values()
            ->map(function (array $item) use ($run): array {
                $wordId = $item['word_id'];

                if ($wordId === null) {
                    $word = Word::query()->create([
                        'word' => $item['word'],
                        'translation' => $item['translation'],
                        'part_of_speech' => $item['part_of_speech_snapshot'],
                        'comment' => $item['comment'],
                    ]);

                    $wordId = $word->id;
                }

                return [
                    'telegram_game_run_id' => $run->id,
                    'word_id' => $wordId,
                    'order_index' => $item['order_index'],
                    'prompt_text' => $item['prompt_text'],
                    'part_of_speech_snapshot' => $item['part_of_speech_snapshot'],
                    'correct_answer' => $item['correct_answer'],
                    'source_type_snapshot' => $item['source'],
                    'options_json' => $item['options_json'] !== null
                        ? json_encode($item['options_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : null,
                    'user_answer' => $item['user_answer'],
                    'is_correct' => $item['is_correct'],
                    'answered_at' => $item['answered_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->all();
    }
}
