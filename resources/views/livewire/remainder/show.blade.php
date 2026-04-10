<div class="remainder-game-shell">
    @if ($gameNotice)
        <div class="remainder-game-banner remainder-game-banner--info">
            {{ $gameNotice }}
        </div>
    @endif

    @if ($gameSession->status === \App\Models\GameSession::STATUS_FINISHED)
        <section class="remainder-game-summary-card">
            <div class="remainder-game-summary-card__header">
                <p class="remainder-game-eyebrow">Session finished</p>
                <h1 class="remainder-game-title">Remainder results</h1>
                <p class="remainder-game-description">
                    Correct {{ $resultSummary['correct_answers'] }} of {{ $resultSummary['total_words'] }}.
                </p>
            </div>

            @if ($resultSummary['incorrect_items']->isNotEmpty())
                <div class="remainder-game-errors">
                    <h2 class="remainder-game-errors__title">Incorrect answers</h2>

                    <div class="remainder-game-errors__list">
                        @foreach ($resultSummary['incorrect_items'] as $item)
                            <article class="remainder-game-error-item">
                                <p class="remainder-game-error-item__prompt">Prompt: {{ $item->prompt_text }}</p>
                                <p class="remainder-game-error-item__answer">Your answer: {{ $item->user_answer }}</p>
                                <p class="remainder-game-error-item__answer">
                                    Correct answer:
                                    <span class="remainder-game-reveal-answer">{{ $item->correct_answer }}</span>
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="remainder-game-banner remainder-game-banner--success">
                    Great work. All answers in this session were correct.
                </div>
            @endif

            <div class="remainder-game-summary-card__actions">
                <a href="{{ route('remainder') }}" class="btn btn-primary remainder-game-action-btn">Back to settings</a>
            </div>
        </section>
    @else
        <section class="remainder-game-card">
            <header class="remainder-game-card__header">
                <div>
                    <p class="remainder-game-eyebrow">Manual translation input</p>
                    <h1 class="remainder-game-title">Remainder</h1>
                </div>
                @if ($progressLabel)
                    <p class="remainder-game-progress">{{ $progressLabel }}</p>
                @endif
            </header>

            @if ($showFeedback)
                <div class="remainder-game-feedback-card {{ $lastAnswerCorrect ? 'remainder-game-feedback-card--correct' : 'remainder-game-feedback-card--incorrect' }}">
                    <h2 class="remainder-game-feedback-card__title">
                        {{ $lastAnswerCorrect ? 'Correct' : 'Incorrect' }}
                    </h2>
                    <p class="remainder-game-feedback-card__text">Prompt: {{ $lastPromptText }}</p>
                    <p class="remainder-game-feedback-card__text">Your answer: {{ $lastUserAnswer }}</p>

                    @unless ($lastAnswerCorrect)
                        <p class="remainder-game-feedback-card__text">
                            Correct answer:
                            <span class="remainder-game-reveal-answer">{{ $lastCorrectAnswer }}</span>
                        </p>
                    @endunless

                    <button type="button" class="btn btn-primary remainder-game-action-btn" wire:click="continueToNext">
                        Continue
                    </button>
                </div>
            @elseif ($currentItem)
                <div class="remainder-game-prompt-card">
                    <div class="remainder-game-prompt-card__body">
                        <p class="remainder-game-prompt-card__label">Translate this</p>
                        <p class="remainder-game-prompt-card__word">{{ $currentItem->prompt_text }}</p>
                    </div>
                </div>

                <form wire:submit="submitAnswer" class="remainder-game-form">
                    <div class="remainder-game-field">
                        <label for="manual-answer" class="remainder-game-label">Your translation</label>
                        <input
                            id="manual-answer"
                            type="text"
                            wire:model.defer="answer"
                            class="remainder-game-input"
                            autocomplete="off"
                        >
                        @error('answer')
                            <p class="remainder-game-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="remainder-game-form__actions">
                        <button type="submit" class="btn btn-primary remainder-game-action-btn">Submit</button>
                    </div>
                </form>
            @else
                <div class="remainder-game-banner remainder-game-banner--info">
                    No active item is available for this session.
                </div>
            @endif
        </section>
    @endif
</div>
