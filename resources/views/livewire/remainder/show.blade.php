<div class="remainder-game-shell">
    @if ($gameNotice)
        <div class="remainder-game-banner remainder-game-banner--info">
            {{ $gameNotice }}
        </div>
    @endif

    @if (! $showFeedback && $gameSession->status !== \App\Models\GameSession::STATUS_FINISHED && $gameSession->mode === \App\Models\GameSession::MODE_CHOICE && $sessionWarnings->isNotEmpty())
        @foreach ($sessionWarnings as $warning)
            <div class="remainder-game-banner remainder-game-banner--info">
                {{ $warning }}
            </div>
        @endforeach
    @endif

    @if ($showFeedback)
        <section class="remainder-game-card">
            <header class="remainder-game-card__header">
                <div>
                    <p class="remainder-game-eyebrow">
                        {{ $gameSession->mode === \App\Models\GameSession::MODE_CHOICE ? __('remainder.game.mode.choice') : __('remainder.game.mode.manual') }}
                    </p>
                    <h1 class="remainder-game-title">{{ __('remainder.settings.title') }}</h1>
                </div>
                @if ($progressLabel)
                    <p class="remainder-game-progress">{{ $progressLabel }}</p>
                @endif
            </header>

            <div class="remainder-game-feedback-card {{ $lastAnswerCorrect ? 'remainder-game-feedback-card--correct' : 'remainder-game-feedback-card--incorrect' }}">
                <h2 class="remainder-game-feedback-card__title">
                    {{ $lastAnswerCorrect ? __('remainder.game.feedback.correct') : __('remainder.game.feedback.incorrect') }}
                </h2>
                <p class="remainder-game-feedback-card__text">{{ __('remainder.game.feedback.prompt', ['value' => $lastPromptText]) }}</p>
                <p class="remainder-game-feedback-card__text">{{ __('remainder.game.feedback.your_answer', ['value' => $lastUserAnswer]) }}</p>

                @unless ($lastAnswerCorrect)
                    <p class="remainder-game-feedback-card__text">
                        {{ __('remainder.game.feedback.correct_answer') }}
                        <span class="remainder-game-reveal-answer">{{ $lastCorrectAnswer }}</span>
                    </p>
                @endunless

                <button type="button" class="btn btn-primary remainder-game-action-btn" wire:click="continueToNext">
                    {{ __('remainder.game.feedback.continue') }}
                </button>
            </div>
        </section>
    @elseif ($gameSession->status === \App\Models\GameSession::STATUS_FINISHED)
        <section class="remainder-game-summary-card">
            <div class="remainder-game-summary-card__header">
                <p class="remainder-game-eyebrow">{{ __('remainder.game.result.finished') }}</p>
                <h1 class="remainder-game-title">{{ __('remainder.game.result.title') }}</h1>
                <p class="remainder-game-description">
                    {{ __('remainder.game.result.summary', ['correct' => $resultSummary['correct_answers'], 'total' => $resultSummary['total_words']]) }}
                </p>
            </div>

            @if ($resultSummary['incorrect_items']->isNotEmpty())
                <div class="remainder-game-errors">
                    <h2 class="remainder-game-errors__title">{{ __('remainder.game.result.incorrect_answers') }}</h2>

                    <div class="remainder-game-errors__list">
                        @foreach ($resultSummary['incorrect_items'] as $item)
                            <article class="remainder-game-error-item">
                                <p class="remainder-game-error-item__prompt">{{ __('remainder.game.feedback.prompt', ['value' => $item->prompt_text]) }}</p>
                                <p class="remainder-game-error-item__answer">{{ __('remainder.game.feedback.your_answer', ['value' => $item->user_answer]) }}</p>
                                <p class="remainder-game-error-item__answer">
                                    {{ __('remainder.game.feedback.correct_answer') }}
                                    <span class="remainder-game-reveal-answer">{{ $item->correct_answer }}</span>
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="remainder-game-banner remainder-game-banner--success">
                    {{ __('remainder.game.result.all_correct') }}
                </div>
            @endif

            <div class="remainder-game-summary-card__actions">
                @if ($gameSession->isDemo())
                    <x-demo-result-cta :correct="$resultSummary['correct_answers']" :total="$resultSummary['total_words']" />
                @else
                    <a href="{{ route('remainder') }}" class="btn btn-primary remainder-game-action-btn">{{ __('remainder.game.result.back_to_settings') }}</a>
                @endif
            </div>
        </section>
    @else
        <section class="remainder-game-card">
            <header class="remainder-game-card__header">
                <div>
                    <p class="remainder-game-eyebrow">
                        {{ $gameSession->mode === \App\Models\GameSession::MODE_CHOICE ? __('remainder.game.mode.choice') : __('remainder.game.mode.manual') }}
                    </p>
                    <h1 class="remainder-game-title">{{ __('remainder.settings.title') }}</h1>
                </div>
                @if ($progressLabel)
                    <p class="remainder-game-progress">{{ $progressLabel }}</p>
                @endif
            </header>

            @if ($currentItem)
                <div class="remainder-game-prompt-card">
                    <div class="remainder-game-prompt-card__body">
                        <p class="remainder-game-prompt-card__label">{{ __('remainder.game.prompt.translate_this') }}</p>
                        <div class="remainder-game-prompt-card__word-row">
                            <p class="remainder-game-prompt-card__word">{{ $currentItem->prompt_text }}</p>
                            @if ($currentPartOfSpeechLabel)
                                <span class="remainder-game-prompt-card__meta">({{ $currentPartOfSpeechLabel }})</span>
                            @endif
                        </div>
                    </div>
                </div>

                <form wire:submit="submitAnswer" class="remainder-game-form">
                    @if ($gameSession->mode === \App\Models\GameSession::MODE_CHOICE)
                        <div class="remainder-game-field">
                            <span class="remainder-game-label">{{ __('remainder.game.prompt.choose_correct_answer') }}</span>

                            <div class="remainder-game-choice-grid">
                                @foreach (($currentItem->options_json ?? []) as $option)
                                    <button
                                        type="button"
                                        class="remainder-game-choice-option {{ $selectedChoice === $option ? 'remainder-game-choice-option--active' : '' }}"
                                        wire:click="$set('selectedChoice', @js($option))"
                                    >
                                        {{ $option }}
                                    </button>
                                @endforeach
                            </div>

                            @error('selectedChoice')
                                <p class="remainder-game-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="remainder-game-field">
                            <label for="manual-answer" class="remainder-game-label">{{ __('remainder.game.prompt.your_translation') }}</label>
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
                    @endif

                    <div class="remainder-game-form__actions">
                        <button type="submit" class="btn btn-primary remainder-game-action-btn">{{ __('remainder.game.prompt.submit') }}</button>
                    </div>
                </form>
            @else
                <div class="remainder-game-banner remainder-game-banner--info">
                    {{ __('remainder.game.prompt.no_active_item') }}
                </div>
            @endif
        </section>
    @endif
</div>
