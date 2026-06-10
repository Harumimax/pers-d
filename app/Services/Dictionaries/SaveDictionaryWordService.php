<?php

namespace App\Services\Dictionaries;

use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Examples\ExampleEnrichmentService;
use App\Support\DictionaryLanguageCode;
use Illuminate\Support\Facades\DB;

class SaveDictionaryWordService
{
    private const TARGET_LANGUAGE = 'ru';

    public function __construct(
        private readonly ExampleEnrichmentService $exampleEnrichmentService,
    ) {
    }

    public function save(
        UserDictionary $dictionary,
        string $wordValue,
        string $translationValue,
        string $partOfSpeechValue,
        ?string $commentValue = null,
    ): Word {
        /** @var Word $word */
        $word = DB::transaction(function () use (
            $dictionary,
            $wordValue,
            $translationValue,
            $partOfSpeechValue,
            $commentValue,
        ): Word {
            $word = Word::create([
                'word' => $wordValue,
                'translation' => $translationValue,
                'part_of_speech' => $partOfSpeechValue,
                'comment' => $commentValue,
            ]);

            $dictionary->words()->attach($word->id);

            return $word;
        });

        $sourceLanguage = DictionaryLanguageCode::fromDictionaryLanguage($dictionary->language);

        if ($sourceLanguage !== null) {
            $this->exampleEnrichmentService->fetchAndStoreForWord(
                $word,
                $sourceLanguage,
                self::TARGET_LANGUAGE,
            );
        }

        return $word;
    }
}
