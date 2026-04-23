<?php

namespace App\Livewire\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Models\User;
use App\Models\UserDictionary;
use App\Services\Dictionaries\CopyWordToUserDictionaryService;
use App\Services\Navigation\HeaderNavigationService;
use App\Services\Remainder\GameEngineService;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

class Show extends Component
{
    public GameSession $gameSession;
    public string $answer = '';
    public string $selectedChoice = '';
    public bool $showFeedback = false;
    public ?bool $lastAnswerCorrect = null;
    public string $lastCorrectAnswer = '';
    public string $lastUserAnswer = '';
    public string $lastPromptText = '';
    public int $lastOrderIndex = 1;
    public ?string $transferBannerType = null;
    public ?string $transferBannerMessage = null;

    public function mount(GameSession $gameSession): void
    {
        if (! $gameSession->isDemo()) {
            abort_unless(auth()->check(), 401);
            abort_if($gameSession->user_id !== auth()->id(), 403);
        }

        $this->gameSession = $gameSession;
    }

    public function render(): View
    {
        $this->gameSession->refresh();

        $engine = app(GameEngineService::class);
        $user = Auth::user();
        $headerNavigation = app(HeaderNavigationService::class)->forUser($user);
        $currentItem = null;
        $currentPartOfSpeechLabel = null;
        $resultSummary = null;
        $progressLabel = null;
        $userDictionaries = $headerNavigation['headerDictionaries'];
        $sessionWarnings = collect($this->gameSession->config_snapshot['warnings'] ?? [])
            ->filter(static fn ($warning): bool => is_string($warning) && trim($warning) !== '')
            ->values();

        if ($this->showFeedback) {
            $progressLabel = __('remainder.game.progress', [
                'current' => $this->lastOrderIndex,
                'total' => $this->gameSession->total_words,
            ]);
        } elseif ($this->gameSession->status === GameSession::STATUS_FINISHED) {
            $resultSummary = $engine->resultSummary($this->gameSession);
        } else {
            $currentItem = $engine->currentItem($this->gameSession);
            $currentPartOfSpeechLabel = $this->partOfSpeechLabel($currentItem?->part_of_speech_snapshot);
            $progressLabel = $currentItem !== null
                ? __('remainder.game.progress', [
                    'current' => $currentItem->order_index,
                    'total' => $this->gameSession->total_words,
                ])
                : null;
        }

        return view('livewire.remainder.show', [
            'currentItem' => $currentItem,
            'currentPartOfSpeechLabel' => $currentPartOfSpeechLabel,
            'progressLabel' => $progressLabel,
            'resultSummary' => $resultSummary,
            'gameNotice' => session('gameNotice'),
            'sessionWarnings' => $sessionWarnings,
            'userDictionaries' => $userDictionaries,
        ]);
    }

    public function submitAnswer(GameEngineService $gameEngineService): void
    {
        if ($this->gameSession->mode === GameSession::MODE_CHOICE) {
            $validated = $this->validate([
                'selectedChoice' => ['required', 'string', 'max:255'],
            ]);

            $submittedAnswer = $validated['selectedChoice'];
        } else {
            $validated = $this->validate([
                'answer' => ['required', 'string', 'max:255'],
            ]);

            $submittedAnswer = $validated['answer'];
        }

        $result = $gameEngineService->submitAnswer($this->gameSession, $submittedAnswer);
        $answeredItem = $result['item'];

        $this->gameSession->refresh();
        $this->answer = '';
        $this->selectedChoice = '';

        $this->showFeedback = true;
        $this->lastAnswerCorrect = (bool) $answeredItem->is_correct;
        $this->lastCorrectAnswer = $answeredItem->correct_answer;
        $this->lastUserAnswer = (string) $answeredItem->user_answer;
        $this->lastPromptText = $answeredItem->prompt_text;
        $this->lastOrderIndex = (int) $answeredItem->order_index;
    }

    public function continueToNext(): void
    {
        $this->showFeedback = false;
        $this->lastAnswerCorrect = null;
        $this->lastCorrectAnswer = '';
        $this->lastUserAnswer = '';
        $this->lastPromptText = '';
        $this->answer = '';
        $this->selectedChoice = '';
    }

    public function transferIncorrectReadyWordToDictionary(int $gameSessionItemId, int $userDictionaryId): void
    {
        $this->resetTransferBanner();

        if ($this->gameSession->isDemo()) {
            $this->showTransferError();

            return;
        }

        $user = $this->currentUser();
        $userDictionary = $user->dictionaries()
            ->whereKey($userDictionaryId)
            ->first();
        $item = $this->gameSession->items()
            ->whereKey($gameSessionItemId)
            ->where('is_correct', false)
            ->first();

        if (
            ! $userDictionary instanceof UserDictionary
            || ! $item instanceof GameSessionItem
            || $item->source_type_snapshot !== 'ready'
        ) {
            $this->showTransferError();

            return;
        }

        try {
            app(CopyWordToUserDictionaryService::class)->copy(
                $userDictionary,
                $this->transferPayloadForReadyIncorrectItem($item),
            );
        } catch (Throwable) {
            $this->showTransferError();

            return;
        }

        $this->transferBannerType = 'success';
        $this->transferBannerMessage = __('remainder.game.result.transfer.success', [
            'word' => $this->snapshotWordValue($item),
            'dictionary' => $userDictionary->name,
        ]);
    }

    private function partOfSpeechLabel(?string $partOfSpeech): ?string
    {
        return PartOfSpeechCatalog::label($partOfSpeech);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * @return array{
     *     word:string,
     *     translation:string,
     *     part_of_speech:?string,
     *     comment:null,
     *     remainder_had_mistake:bool
     * }
     */
    private function transferPayloadForReadyIncorrectItem(GameSessionItem $item): array
    {
        return [
            'word' => $this->snapshotWordValue($item),
            'translation' => $this->snapshotTranslationValue($item),
            'part_of_speech' => $item->part_of_speech_snapshot,
            'comment' => null,
            'remainder_had_mistake' => true,
        ];
    }

    private function snapshotWordValue(GameSessionItem $item): string
    {
        return $this->gameSession->direction === GameSession::DIRECTION_FOREIGN_TO_RU
            ? $item->prompt_text
            : $item->correct_answer;
    }

    private function snapshotTranslationValue(GameSessionItem $item): string
    {
        return $this->gameSession->direction === GameSession::DIRECTION_FOREIGN_TO_RU
            ? $item->correct_answer
            : $item->prompt_text;
    }

    private function resetTransferBanner(): void
    {
        $this->transferBannerType = null;
        $this->transferBannerMessage = null;
    }

    private function showTransferError(): void
    {
        $this->transferBannerType = 'error';
        $this->transferBannerMessage = __('remainder.game.result.transfer.error');
    }
}
