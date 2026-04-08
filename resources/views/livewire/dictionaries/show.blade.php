<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show">
        <header class="dictionary-show__header">
            <div class="dictionary-show__title-row">
                <h1 class="dictionary-show__title">{{ $dictionary->name }}</h1>
                @if (! $showCreateForm)
                    <button
                        type="button"
                        class="btn btn-primary dictionaries-new-btn dictionary-show__add-btn"
                        wire:click="openCreateForm"
                    >
                        <span class="dictionaries-new-btn__plus">+</span>
                        <span>Add Word</span>
                    </button>
                @endif
            </div>

            <p class="dictionary-show__subtitle">
                Language <b>{{ $dictionary->language ?? 'not specified' }}</b> &middot; Total words: <b> {{ $totalWordsCount }} </b> &middot; Created on <b> {{ $dictionary->created_at?->format('Y-m-d') ?? 'unknown date' }} </b>
            </p>
        </header>

        @if ($showCreateForm)
            <section
                class="dictionaries-create-card dictionary-show__create-card"
                aria-label="Add word form"
                wire:key="dictionary-add-card-{{ $formRenderKey }}"
                x-data="{
                    mode: 'manual',
                }"
                x-on:reset-auto-add-word-form.window="mode = 'automatic'"
            >
                <div class="dictionary-show__mode-switch" role="tablist" aria-label="Add word mode">
                    <button
                        type="button"
                        class="dictionary-show__mode-chip"
                        :class="{ 'dictionary-show__mode-chip--active': mode === 'automatic' }"
                        x-on:click="mode = 'automatic'"
                    >
                        Translate automatically
                    </button>
                    <button
                        type="button"
                        class="dictionary-show__mode-chip"
                        :class="{ 'dictionary-show__mode-chip--active': mode === 'manual' }"
                        x-on:click="mode = 'manual'"
                    >
                        Enter manually
                    </button>
                </div>

                <div class="dictionary-show__mode-panel" x-show="mode === 'manual'" x-cloak>
                    <form
                        class="dictionaries-create-form dictionary-show__create-form"
                        wire:submit.prevent="addWord"
                        wire:key="add-word-form-{{ $formRenderKey }}"
                    >
                        <div class="dictionaries-field">
                            <label for="word-name" class="dictionaries-label">Word</label>
                            <input
                                id="word-name"
                                type="text"
                                class="dictionaries-input"
                                placeholder="e.g., buongiorno"
                                wire:model.defer="word"
                            >
                            @error('word')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-field">
                            <label for="word-part-of-speech" class="dictionaries-label">Part of speech</label>
                            <select
                                id="word-part-of-speech"
                                class="dictionaries-input"
                                wire:model.defer="partOfSpeech"
                            >
                                <option value="">Select part of speech</option>
                                @foreach ($partOfSpeechOptions as $partOfSpeechValue => $partOfSpeechLabel)
                                    <option value="{{ $partOfSpeechValue }}">{!! $partOfSpeechLabel !!}</option>
                                @endforeach
                            </select>
                            @error('partOfSpeech')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-field">
                            <label for="word-translation" class="dictionaries-label">Translation</label>
                            <input
                                id="word-translation"
                                type="text"
                                class="dictionaries-input"
                                placeholder="e.g., good morning"
                                wire:model.defer="translation"
                            >
                            @error('translation')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-field dictionary-show__comment-field">
                            <label for="word-comment" class="dictionaries-label">Comment</label>
                            <input
                                id="word-comment"
                                type="text"
                                class="dictionaries-input"
                                placeholder="e.g., formal greeting"
                                wire:model.defer="comment"
                            >
                            @error('comment')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-create-actions dictionary-show__create-actions">
                            <button type="submit" class="btn btn-primary dictionaries-action-btn">
                                Add
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary dictionaries-action-btn"
                                wire:click="cancelCreate"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="dictionary-show__mode-panel" x-show="mode === 'automatic'" x-cloak>
                    <form class="dictionary-show__translate-panel" wire:submit.prevent="addTranslatedWord">
                        <div class="dictionary-show__translate-grid">
                            <div class="dictionaries-field">
                                <label for="auto-translate-word" class="dictionaries-label">Word</label>
                                <input
                                    id="auto-translate-word"
                                    type="text"
                                    class="dictionaries-input"
                                    placeholder="e.g., buongiorno"
                                    wire:model.defer="autoWord"
                                >
                                @error('autoWord')
                                    <p class="dictionaries-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="dictionary-show__translate-button-wrap">
                                <label class="dictionaries-label dictionary-show__translate-button-label">Action</label>
                                <button
                                    type="button"
                                    class="btn btn-primary dictionaries-action-btn dictionary-show__translate-btn"
                                    wire:click="translateAutomatically"
                                    wire:loading.attr="disabled"
                                    wire:target="translateAutomatically"
                                >
                                    <span wire:loading.remove wire:target="translateAutomatically">Translate</span>
                                    <span wire:loading wire:target="translateAutomatically">Translating...</span>
                                </button>
                            </div>
                        </div>

                        @if ($autoTranslationError !== '')
                            <div class="dictionary-show__translation-error" role="alert">
                                <p class="dictionary-show__translation-error-text">{{ $autoTranslationUnavailableMessage }}</p>
                                <button
                                    type="button"
                                    class="dictionary-show__translation-error-link"
                                    x-on:click="mode = 'manual'"
                                >
                                    Switch to Enter manually
                                </button>
                            </div>
                        @endif

                        @if ($autoTranslated && $autoSuggestions !== [])
                            <div class="dictionary-show__translation-suggestions">
                            <div class="dictionary-show__translation-suggestions-header">
                                <h3 class="dictionary-show__translation-suggestions-title">Suggested translations</h3>
                                <p class="dictionary-show__translation-suggestions-subtitle">
                                    Choose the most suitable translation for this dictionary
                                </p>
                            </div>

                            <div class="dictionary-show__translation-chip-list">
                                @foreach ($autoSuggestions as $suggestionIndex => $suggestion)
                                    <button
                                        type="button"
                                        wire:click="selectAutoTranslationByIndex({{ $suggestionIndex }})"
                                        @class([
                                            'dictionary-show__translation-chip',
                                            'dictionary-show__translation-chip--active' => $autoTranslation === $suggestion['text'],
                                        ])
                                    >
                                        <span class="dictionary-show__translation-chip-main">{{ $suggestion['text'] }}</span>
                                        <span class="dictionary-show__translation-chip-meta">{{ $suggestion['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if ($autoTranslated)
                            <div class="dictionary-show__translate-result">
                            <div class="dictionaries-field">
                                <label class="dictionaries-label">Selected translation</label>
                                <div class="dictionary-show__selected-translation">{{ $autoTranslation !== '' ? $autoTranslation : 'Choose a translation from the suggestions above' }}</div>
                                @error('autoTranslation')
                                    <p class="dictionaries-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="dictionaries-field">
                                <label for="auto-part-of-speech" class="dictionaries-label">Part of speech</label>
                                <select
                                    id="auto-part-of-speech"
                                    class="dictionaries-input"
                                    wire:model.defer="autoPartOfSpeech"
                                >
                                    <option value="">Select part of speech</option>
                                    @foreach ($partOfSpeechOptions as $partOfSpeechValue => $partOfSpeechLabel)
                                        <option value="{{ $partOfSpeechValue }}">{!! $partOfSpeechLabel !!}</option>
                                    @endforeach
                                </select>
                                @error('autoPartOfSpeech')
                                    <p class="dictionaries-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @endif

                        <div
                            class="dictionaries-field dictionary-show__auto-comment-field"
                            @class([
                                'dictionaries-field',
                                'dictionary-show__auto-comment-field',
                                'dictionary-show__auto-comment-field--visible' => $autoTranslated,
                            ])
                        >
                            <label for="auto-comment" class="dictionaries-label">Comment</label>
                            <input
                                id="auto-comment"
                                type="text"
                                class="dictionaries-input"
                                placeholder="e.g., formal greeting"
                                wire:model.defer="autoComment"
                            >
                            @error('autoComment')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-create-actions dictionary-show__create-actions dictionary-show__auto-actions">
                            <button
                                type="submit"
                                class="btn btn-primary dictionaries-action-btn"
                            >
                                Add
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary dictionaries-action-btn"
                                wire:click="cancelCreate"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        @endif

        <article class="dictionary-show-card" aria-label="Dictionary details">
            <div class="word-list-header">
                <div>
                    <h2 class="dictionary-show-card__title">Word List</h2>
                    <p class="word-list-subtitle">{{ $totalWordsCount }} words in this dictionary</p>
                </div>

                <div class="word-list-controls">
                    <div class="word-list-search-group">
                        <form class="word-list-search" wire:submit.prevent="applySearch">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                            <input
                                type="text"
                                placeholder="Search word or translation..."
                                aria-label="Search words"
                                wire:model.defer="search"
                            >
                        </form>
                        <p class="word-list-search-hint">Press Enter to search</p>
                    </div>

                    <select class="word-list-select" aria-label="Filter words by part of speech" wire:model.live="partOfSpeechFilter">
                        @foreach ($partOfSpeechFilterOptions as $partOfSpeechFilterValue => $partOfSpeechFilterLabel)
                            <option value="{{ $partOfSpeechFilterValue }}">{!! $partOfSpeechFilterLabel !!}</option>
                        @endforeach
                    </select>

                    <select class="word-list-select" aria-label="Sort words" wire:model.live="sort">
                        <option value="newest">Newest first</option>
                        <option value="a-z">A-Z</option>
                        <option value="oldest">Oldest first</option>
                    </select>
                </div>
            </div>

            @if ($words->isEmpty())
                <p class="dictionary-show-list__empty">No words yet. Add your first word using the form above.</p>
            @else
                <div class="word-list-table-wrap">
                    <table class="word-list-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Word</th>
                                <th style="width: 22%;">Translation</th>
                                <th style="width: 30%;">Comment</th>
                                <th style="width: 12%;">Added</th>
                                <th style="width: 8%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($words as $wordItem)
                                <tr wire:key="word-row-{{ $wordItem->id }}-{{ $wordItem->pivot->created_at?->timestamp ?? 'na' }}">
                                    <td>
                                        <div class="word-list-main">{{ $wordItem->word }}</div>
                                        <div class="word-list-meta">
                                            {{ $dictionary->language ?? 'Language not specified' }}
                                            &middot;
                                            {{ $partOfSpeechDisplayMap[$wordItem->part_of_speech] ?? 'Part of speech not specified' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="word-list-translation">{{ $wordItem->translation }}</div>
                                    </td>
                                    <td>
                                        <div class="word-list-comment">{{ $wordItem->comment ?: 'No comment' }}</div>
                                    </td>
                                    <td>
                                        <span class="word-list-badge">{{ $wordItem->pivot->created_at?->format('M d') ?? '-' }}</span>
                                    </td>
                                    <td class="word-list-action-cell">
                                        <button
                                            type="button"
                                            class="word-list-delete-btn"
                                            wire:key="word-delete-btn-{{ $wordItem->id }}"
                                            wire:click="confirmDeleteWord({{ $wordItem->id }})"
                                            aria-label="Delete word {{ $wordItem->word }}"
                                        >
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M9 3h6m-8 3h10m-1 0-.7 11.2A2 2 0 0 1 13.3 19h-2.6a2 2 0 0 1-1.99-1.8L8 6m3 4v5m2-5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="word-list-pagination">
                    <p class="word-list-pagination__info">
                        Showing {{ $words->firstItem() }}-{{ $words->lastItem() }} of {{ $words->total() }} words
                    </p>

                    <div class="word-list-pagination__nav">
                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="previousPage"
                            @disabled($words->onFirstPage())
                        >
                            Prev
                        </button>

                        @for ($page = 1; $page <= $words->lastPage(); $page++)
                            <button
                                type="button"
                                class="word-list-page-btn {{ $words->currentPage() === $page ? 'is-active' : '' }}"
                                wire:click="gotoPage({{ $page }})"
                            >
                                {{ $page }}
                            </button>
                        @endfor

                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="nextPage"
                            @disabled(! $words->hasMorePages())
                        >
                            Next
                        </button>
                    </div>
                </div>
            @endif
        </article>

        @if ($pendingDeleteWordId !== null)
            <div class="dictionary-delete-overlay" wire:key="delete-overlay-{{ $pendingDeleteWordId }}" wire:click="cancelDeleteWord">
                <div class="dictionary-delete-dialog" wire:click.stop>
                    <div class="dictionary-delete-modal">
                        <h2 class="dictionary-delete-modal__title">Delete Word</h2>

                        <p class="dictionary-delete-modal__text">
                            Are you sure you want to delete "{{ $pendingDeleteWordLabel }}"?
                        </p>

                        <div class="dictionary-delete-modal__actions">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                wire:key="delete-cancel-{{ $pendingDeleteWordId }}"
                                wire:click="cancelDeleteWord"
                            >
                                No
                            </button>

                            <button
                                type="button"
                                class="dictionary-delete-modal__danger-btn"
                                wire:key="delete-confirm-{{ $pendingDeleteWordId }}"
                                wire:click="deleteConfirmedWord"
                            >
                                Yes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
</main>
