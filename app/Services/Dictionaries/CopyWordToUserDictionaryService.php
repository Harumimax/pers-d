<?php

namespace App\Services\Dictionaries;

use App\Models\ReadyDictionaryWord;
use App\Models\UserDictionary;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Services\Examples\ExampleEnrichmentService;
use App\Support\DictionaryLanguageCode;
use Illuminate\Support\Facades\DB;

class CopyWordToUserDictionaryService
{
    private const TARGET_LANGUAGE = 'ru';

    public function __construct(
        private readonly ExampleEnrichmentService $exampleEnrichmentService,
    ) {
    }

    /**
     * @param array{
     *     word:string,
     *     translation:string,
     *     part_of_speech:?string,
     *     comment:?string,
     *     remainder_had_mistake?:bool,
     *     source_language?:?string
     * } $payload
     */
    public function copy(UserDictionary $userDictionary, array $payload, Word|ReadyDictionaryWord|null $sourceWord = null): Word
    {
        /** @var Word $word */
        $word = DB::transaction(function () use ($userDictionary, $payload): Word {
            $word = Word::create([
                'word' => $payload['word'],
                'translation' => $payload['translation'],
                'part_of_speech' => $payload['part_of_speech'],
                'comment' => $payload['comment'],
            ]);

            $userDictionary->words()->attach($word->id);

            if (($payload['remainder_had_mistake'] ?? false) === true) {
                UserWordProgress::query()->updateOrCreate(
                    [
                        'user_id' => $userDictionary->user_id,
                        'word_id' => $word->id,
                    ],
                    [
                        'remainder_had_mistake' => true,
                    ],
                );
            }

            return $word;
        });

        if ($sourceWord !== null && $sourceWord->examples()->exists()) {
            $this->exampleEnrichmentService->copyStoredExamples($sourceWord, $word);

            return $word;
        }

        $sourceLanguage = isset($payload['source_language'])
            ? (is_string($payload['source_language']) ? trim($payload['source_language']) : null)
            : DictionaryLanguageCode::fromDictionaryLanguage($userDictionary->language);

        if ($sourceLanguage !== null && $sourceLanguage !== '') {
            $this->exampleEnrichmentService->fetchAndStoreForWord(
                $word,
                $sourceLanguage,
                self::TARGET_LANGUAGE,
            );
        }

        return $word;
    }
}
