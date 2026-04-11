<?php

namespace App\Livewire\Remainder;

use App\Models\GameSession;
use App\Models\GameSessionItem;
use App\Services\Remainder\GameEngineService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

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

    public function mount(GameSession $gameSession): void
    {
        abort_unless(auth()->check(), 401);
        abort_if($gameSession->user_id !== auth()->id(), 403);

        $this->gameSession = $gameSession;
    }

    public function render(): View
    {
        $this->gameSession->refresh();

        $engine = app(GameEngineService::class);
        $currentItem = null;
        $resultSummary = null;
        $progressLabel = null;
        $sessionWarnings = collect($this->gameSession->config_snapshot['warnings'] ?? [])
            ->map(static fn ($warning): string => (string) $warning)
            ->filter()
            ->values();
        $currentItemOptionsWarning = null;

        if ($this->gameSession->status === GameSession::STATUS_FINISHED) {
            $resultSummary = $engine->resultSummary($this->gameSession);
        } elseif ($this->showFeedback) {
            $progressLabel = sprintf('Word %d of %d', $this->lastOrderIndex, $this->gameSession->total_words);
        } else {
            $currentItem = $engine->currentItem($this->gameSession);
            $progressLabel = $currentItem !== null
                ? sprintf('Word %d of %d', $currentItem->order_index, $this->gameSession->total_words)
                : null;

            if (
                $this->gameSession->mode === GameSession::MODE_CHOICE
                && $currentItem !== null
                && is_array($currentItem->options_json)
                && count($currentItem->options_json) < 6
            ) {
                $currentItemOptionsWarning = sprintf(
                    'This question has %d answer %s available.',
                    count($currentItem->options_json),
                    count($currentItem->options_json) === 1 ? 'option' : 'options',
                );
            }
        }

        return view('livewire.remainder.show', [
            'currentItem' => $currentItem,
            'progressLabel' => $progressLabel,
            'resultSummary' => $resultSummary,
            'gameNotice' => session('gameNotice'),
            'sessionWarnings' => $sessionWarnings,
            'currentItemOptionsWarning' => $currentItemOptionsWarning,
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

        if ($result['finished']) {
            $this->showFeedback = false;
            $this->lastAnswerCorrect = null;
            $this->lastCorrectAnswer = '';
            $this->lastUserAnswer = '';
            $this->lastPromptText = '';

            return;
        }

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
}
