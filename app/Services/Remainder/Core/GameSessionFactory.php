<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameSessionFactory
{
    /**
     * @param Collection<int, array{source:string,word_id:int|null,word:string,translation:string,comment:?string,order_index:int,prompt_text:string,part_of_speech_snapshot:?string,correct_answer:string,options_json:null|array<int,string>,user_answer:null|string,is_correct:null|bool,answered_at:mixed}> $itemPayloads
     * @param array<int, string> $warnings
     */
    public function create(
        ?User $user,
        GameSessionConfigData $config,
        int $requestedWordsCount,
        Collection $selectedWords,
        array $warnings,
        Collection $itemPayloads,
        bool $isDemo,
    ): GameSession {
        /** @var GameSession $gameSession */
        $gameSession = DB::transaction(function () use ($user, $config, $requestedWordsCount, $selectedWords, $warnings, $itemPayloads, $isDemo): GameSession {
            $session = GameSession::create([
                'user_id' => $user?->id,
                'mode' => $config->mode,
                'direction' => $config->direction,
                'total_words' => $selectedWords->count(),
                'correct_answers' => 0,
                'status' => GameSession::STATUS_ACTIVE,
                'started_at' => now(),
                'finished_at' => null,
                'config_snapshot' => [
                    'mode' => $config->mode,
                    'direction' => $config->direction,
                    'requested_words_count' => $requestedWordsCount,
                    'actual_words_count' => $selectedWords->count(),
                    'dictionary_ids' => $config->dictionaryIds,
                    'ready_dictionary_ids' => $config->readyDictionaryIds,
                    'parts_of_speech' => $config->partsOfSpeech,
                    'warnings' => $warnings,
                    'options_target_count' => $config->usesChoiceMode() ? 6 : null,
                    'is_demo' => $isDemo,
                ],
            ]);

            $items = $itemPayloads
                ->values()
                ->map(function (array $item) use ($session): array {
                    $wordId = $item['word_id'];

                    if ($wordId === null) {
                        $word = Word::create([
                            'word' => $item['word'],
                            'translation' => $item['translation'],
                            'part_of_speech' => $item['part_of_speech_snapshot'],
                            'comment' => $item['comment'],
                        ]);

                        $wordId = $word->id;
                    }

                    return [
                        'game_session_id' => $session->id,
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

            GameSessionItem::insert($items);

            return $session->fresh();
        });

        return $gameSession;
    }
}
