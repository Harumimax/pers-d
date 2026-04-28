<?php

namespace App\Services\Remainder;

use App\Models\GameSession;
use App\Models\User;
use App\Services\Remainder\Core\GameItemSnapshotFactory;
use App\Services\Remainder\Core\GameSessionConfigData;
use App\Services\Remainder\Core\GameSessionFactory;
use App\Services\Remainder\Core\GameWordSelectionService;
use Illuminate\Validation\ValidationException;

class PrepareGameService
{
    public function __construct(
        private readonly GameWordSelectionService $gameWordSelectionService,
        private readonly GameItemSnapshotFactory $gameItemSnapshotFactory,
        private readonly ChoiceOptionsBuilder $choiceOptionsBuilder,
        private readonly GameSessionFactory $gameSessionFactory,
    ) {
    }

    /**
     * @param array{mode:string,direction:string,dictionary_ids:array<int,int|string>,ready_dictionary_ids?:array<int,int|string>,parts_of_speech:array<int,string>,words_count:int} $config
     * @return array{gameSession: GameSession, notice: string|null}
     */
    public function prepare(?User $user, array $config): array
    {
        $isDemo = $user === null;
        $sessionConfig = GameSessionConfigData::fromArray($config);
        $availableWords = $this->gameWordSelectionService->availableWordCandidates($user, $sessionConfig);

        if ($availableWords->isEmpty()) {
            throw ValidationException::withMessages([
                'dictionary_ids' => __('remainder.messages.start.no_words'),
            ]);
        }

        $targetWordsCount = min($sessionConfig->requestedWordsCount, $availableWords->count());
        $selectedWords = $this->gameWordSelectionService->selectWordsForSession($availableWords, $targetWordsCount);

        $notice = null;
        if ($selectedWords->count() < $sessionConfig->requestedWordsCount) {
            $notice = __('remainder.messages.start.partial_notice', [
                'count' => $selectedWords->count(),
                'word_label' => $selectedWords->count() === 1
                    ? __('remainder.messages.start.word_label_singular')
                    : __('remainder.messages.start.word_label_plural'),
            ]);
        }

        $itemPayloads = $this->gameItemSnapshotFactory->build($sessionConfig, $selectedWords);
        $warnings = [];

        if ($sessionConfig->usesChoiceMode()) {
            $availableAnswers = $availableWords
                ->map(function (array $word) use ($sessionConfig): string {
                    return $sessionConfig->direction === GameSession::DIRECTION_FOREIGN_TO_RU
                        ? $word['translation']
                        : $word['word'];
                })
                ->values();

            $choicePayload = $this->choiceOptionsBuilder->build($itemPayloads, $availableAnswers);
            $itemPayloads = $choicePayload['items'];
            $warnings = $choicePayload['warnings'];
        }

        $gameSession = $this->gameSessionFactory->create(
            $user,
            $sessionConfig,
            $sessionConfig->requestedWordsCount,
            $selectedWords,
            $warnings,
            $itemPayloads,
            $isDemo,
        );

        return [
            'gameSession' => $gameSession,
            'notice' => $notice,
        ];
    }
}
