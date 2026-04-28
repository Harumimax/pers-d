<?php

namespace App\Services\Remainder\Core;

use App\Models\GameSession;
use Illuminate\Support\Collection;

class GameItemSnapshotFactory
{
    /**
     * @param Collection<int, array{source:string,word_id:int|null,word:string,translation:string,part_of_speech:?string,comment:?string,remainder_had_mistake:bool}> $selectedWords
     * @return Collection<int, array{source:string,word_id:int|null,word:string,translation:string,comment:?string,order_index:int,prompt_text:string,part_of_speech_snapshot:?string,correct_answer:string,options_json:null|array<int,string>,user_answer:null,is_correct:null|bool,answered_at:null}>
     */
    public function build(GameSessionConfigData $config, Collection $selectedWords): Collection
    {
        return $selectedWords
            ->values()
            ->map(function (array $word, int $index) use ($config): array {
                return [
                    'source' => $word['source'],
                    'word_id' => $word['word_id'],
                    'word' => $word['word'],
                    'translation' => $word['translation'],
                    'comment' => $word['comment'],
                    'order_index' => $index + 1,
                    'prompt_text' => $config->direction === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word['word']
                        : $word['translation'],
                    'part_of_speech_snapshot' => $word['part_of_speech'],
                    'correct_answer' => $config->direction === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word['translation']
                        : $word['word'],
                    'options_json' => null,
                    'user_answer' => null,
                    'is_correct' => null,
                    'answered_at' => null,
                ];
            });
    }
}
