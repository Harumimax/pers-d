<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show">
        @php
            $dictionaryLanguageKey = $dictionary->language !== null ? 'dictionaries.index.languages.' . strtolower($dictionary->language) : 'dictionaries.show.not_specified';
            $dictionaryLanguageLabel = $dictionary->language !== null ? __($dictionaryLanguageKey) : __('dictionaries.show.not_specified');
            $createdDateLabel = $dictionary->created_at?->translatedFormat('Y-m-d') ?? __('dictionaries.show.unknown_date');
        @endphp

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
                        <span>{{ __('dictionaries.show.add_word') }}</span>
                    </button>
                @endif
            </div>

            <p class="dictionary-show__subtitle">
                {!! __('dictionaries.show.subtitle', ['language' => '<b>' . e($dictionaryLanguageLabel) . '</b>', 'count' => '<b>' . e($totalWordsCount) . '</b>', 'date' => '<b>' . e($createdDateLabel) . '</b>']) !!}
            </p>
        </header>

        @if ($showCreateForm)
            <section
                class="dictionaries-create-card dictionary-show__create-card"
                aria-label="{{ __('dictionaries.show.add_word_form_aria') }}"
                wire:key="dictionary-add-card-{{ $formRenderKey }}"
                x-data="{
                    mode: 'manual',
                }"
                x-on:reset-auto-add-word-form.window="mode = 'automatic'"
            >
                <div class="dictionary-show__mode-switch" role="tablist" aria-label="{{ __('dictionaries.show.add_word_mode') }}">
                    <button
                        type="button"
                        class="dictionary-show__mode-chip"
                        :class="{ 'dictionary-show__mode-chip--active': mode === 'automatic' }"
                        x-on:click="mode = 'automatic'"
                    >
                        {{ __('dictionaries.show.modes.automatic') }}
                    </button>
                    <button
                        type="button"
                        class="dictionary-show__mode-chip"
                        :class="{ 'dictionary-show__mode-chip--active': mode === 'manual' }"
                        x-on:click="mode = 'manual'"
                    >
                        {{ __('dictionaries.show.modes.manual') }}
                    </button>
                </div>

                <div class="dictionary-show__mode-panel" x-show="mode === 'manual'" x-cloak>
                    <form
                        class="dictionaries-create-form dictionary-show__create-form"
                        wire:submit.prevent="addWord"
                        wire:key="add-word-form-{{ $formRenderKey }}"
                    >
                        <div class="dictionaries-field">
                            <label for="word-name" class="dictionaries-label">{{ __('dictionaries.show.fields.word') }}</label>
                            <input
                                id="word-name"
                                type="text"
                                class="dictionaries-input"
                                placeholder="{{ __('dictionaries.show.placeholders.word') }}"
                                wire:model.defer="word"
                            >
                            @error('word')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-field">
                            <label for="word-part-of-speech" class="dictionaries-label">{{ __('dictionaries.show.fields.part_of_speech') }}</label>
                            <select
                                id="word-part-of-speech"
                                class="dictionaries-input"
                                wire:model.defer="partOfSpeech"
                            >
                                <option value="">{{ __('dictionaries.show.placeholders.part_of_speech') }}</option>
                                @foreach ($partOfSpeechOptions as $partOfSpeechValue => $partOfSpeechLabel)
                                    <option value="{{ $partOfSpeechValue }}">{!! $partOfSpeechLabel !!}</option>
                                @endforeach
                            </select>
                            @error('partOfSpeech')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-field">
                            <label for="word-translation" class="dictionaries-label">{{ __('dictionaries.show.fields.translation') }}</label>
                            <input
                                id="word-translation"
                                type="text"
                                class="dictionaries-input"
                                placeholder="{{ __('dictionaries.show.placeholders.translation') }}"
                                wire:model.defer="translation"
                            >
                            @error('translation')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-field dictionary-show__comment-field">
                            <label for="word-comment" class="dictionaries-label">{{ __('dictionaries.show.fields.comment') }}</label>
                            <input
                                id="word-comment"
                                type="text"
                                class="dictionaries-input"
                                placeholder="{{ __('dictionaries.show.placeholders.comment') }}"
                                wire:model.defer="comment"
                            >
                            @error('comment')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionaries-create-actions dictionary-show__create-actions">
                            <button type="submit" class="btn btn-primary dictionaries-action-btn">
                                {{ __('dictionaries.show.actions.add') }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary dictionaries-action-btn"
                                wire:click="cancelCreate"
                            >
                                {{ __('dictionaries.show.actions.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="dictionary-show__mode-panel" x-show="mode === 'automatic'" x-cloak>
                    <form class="dictionary-show__translate-panel" wire:submit.prevent="addTranslatedWord">
                        <div class="dictionary-show__translate-grid">
                            <div class="dictionaries-field">
                                <label for="auto-translate-word" class="dictionaries-label">{{ __('dictionaries.show.fields.word') }}</label>
                                <input
                                    id="auto-translate-word"
                                    type="text"
                                    class="dictionaries-input"
                                    placeholder="{{ __('dictionaries.show.placeholders.word') }}"
                                    wire:model.defer="autoWord"
                                >
                                @error('autoWord')
                                    <p class="dictionaries-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="dictionary-show__translate-button-wrap">
                                <label class="dictionaries-label dictionary-show__translate-button-label">{{ __('dictionaries.show.fields.action') }}</label>
                                <button
                                    type="button"
                                    class="btn btn-primary dictionaries-action-btn dictionary-show__translate-btn"
                                    wire:click="translateAutomatically"
                                    wire:loading.attr="disabled"
                                    wire:target="translateAutomatically"
                                >
                                    <span wire:loading.remove wire:target="translateAutomatically">{{ __('dictionaries.show.actions.translate') }}</span>
                                    <span wire:loading wire:target="translateAutomatically">{{ __('dictionaries.show.actions.translating') }}</span>
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
                                    {{ __('dictionaries.show.actions.switch_to_manual') }}
                                </button>
                            </div>
                        @endif

                        @if ($autoTranslated && $autoSuggestions !== [])
                            <div class="dictionary-show__translation-suggestions">
                            <div class="dictionary-show__translation-suggestions-header">
                                <h3 class="dictionary-show__translation-suggestions-title">{{ __('dictionaries.show.translation.suggested_title') }}</h3>
                                <p class="dictionary-show__translation-suggestions-subtitle">
                                    {{ __('dictionaries.show.translation.suggested_subtitle') }}
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
                                <label class="dictionaries-label">{{ __('dictionaries.show.fields.selected_translation') }}</label>
                                <div class="dictionary-show__selected-translation">{{ $autoTranslation !== '' ? $autoTranslation : __('dictionaries.show.translation.selected_translation_empty') }}</div>
                                @error('autoTranslation')
                                    <p class="dictionaries-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="dictionaries-field">
                                <label for="auto-part-of-speech" class="dictionaries-label">{{ __('dictionaries.show.fields.part_of_speech') }}</label>
                                <select
                                    id="auto-part-of-speech"
                                    class="dictionaries-input"
                                    wire:model.defer="autoPartOfSpeech"
                                >
                                    <option value="">{{ __('dictionaries.show.placeholders.part_of_speech') }}</option>
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

                        @if ($autoTranslated)
                            <div class="dictionaries-field dictionary-show__auto-comment-field">
                                <label for="auto-comment" class="dictionaries-label">{{ __('dictionaries.show.fields.comment') }}</label>
                                <input
                                    id="auto-comment"
                                    type="text"
                                    class="dictionaries-input"
                                    placeholder="{{ __('dictionaries.show.placeholders.comment') }}"
                                    wire:model.defer="autoComment"
                                >
                                @error('autoComment')
                                    <p class="dictionaries-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="dictionaries-create-actions dictionary-show__create-actions dictionary-show__auto-actions">
                            <button
                                type="submit"
                                class="btn btn-primary dictionaries-action-btn"
                            >
                                {{ __('dictionaries.show.actions.add') }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary dictionaries-action-btn"
                                wire:click="cancelCreate"
                            >
                                {{ __('dictionaries.show.actions.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        @endif

        <article class="dictionary-show-card" aria-label="{{ __('dictionaries.show.word_list.title') }}">
            <div class="word-list-header">
                <div>
                    <h2 class="dictionary-show-card__title">{{ __('dictionaries.show.word_list.title') }}</h2>
                    <p class="word-list-subtitle">{{ trans_choice('dictionaries.show.word_list.subtitle', $totalWordsCount, ['count' => $totalWordsCount]) }}</p>
                </div>

                <div class="word-list-controls">
                    <div class="word-list-search-group">
                        <form class="word-list-search" wire:submit.prevent="applySearch">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                            <input
                                type="text"
                                placeholder="{{ __('dictionaries.show.placeholders.search') }}"
                                aria-label="{{ __('dictionaries.show.placeholders.search') }}"
                                wire:model.defer="search"
                            >
                        </form>
                        <p class="word-list-search-hint">{{ __('dictionaries.show.word_list.search_hint') }}</p>
                    </div>

                    <select class="word-list-select" aria-label="{{ __('dictionaries.show.word_list.filter_aria') }}" wire:model.live="partOfSpeechFilter">
                        @foreach ($partOfSpeechFilterOptions as $partOfSpeechFilterValue => $partOfSpeechFilterLabel)
                            <option value="{{ $partOfSpeechFilterValue }}">{!! $partOfSpeechFilterLabel !!}</option>
                        @endforeach
                    </select>

                    <select class="word-list-select" aria-label="{{ __('dictionaries.show.word_list.sort_aria') }}" wire:model.live="sort">
                        <option value="newest">{{ __('dictionaries.show.word_list.sort.newest') }}</option>
                        <option value="a-z">{{ __('dictionaries.show.word_list.sort.a_z') }}</option>
                        <option value="oldest">{{ __('dictionaries.show.word_list.sort.oldest') }}</option>
                    </select>
                </div>
            </div>

            @if ($words->isEmpty())
                <p class="dictionary-show-list__empty">{{ __('dictionaries.show.word_list.empty') }}</p>
            @else
                <div class="word-list-table-wrap">
                    <table class="word-list-table">
                        <thead>
                            <tr>
                                <th class="word-list-table__word-heading" style="width: 28%;">{{ __('dictionaries.show.word_list.table.word') }}</th>
                                <th style="width: 22%;">{{ __('dictionaries.show.word_list.table.translation') }}</th>
                                <th style="width: 30%;">{{ __('dictionaries.show.word_list.table.comment') }}</th>
                                <th style="width: 12%;">{{ __('dictionaries.show.word_list.table.added') }}</th>
                                <th style="width: 8%; text-align: center;">{{ __('dictionaries.show.word_list.table.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($words as $wordItem)
                                @php
                                    $wordLanguageKey = $dictionary->language !== null ? 'dictionaries.index.languages.' . strtolower($dictionary->language) : 'dictionaries.index.languages.not_specified';
                                @endphp
                                <tr wire:key="word-row-{{ $wordItem->id }}-{{ $wordItem->pivot->created_at?->timestamp ?? 'na' }}">
                                    <td>
                                        <div class="word-list-word-cell">
                                            <span class="word-list-mistake-marker-slot">
                                                @if ($wordItem->remainder_had_mistake)
                                                    <span
                                                        class="word-list-mistake-marker"
                                                        aria-label="{{ __('dictionaries.show.word_list.remainder_mistake_marker_aria') }}"
                                                    ></span>
                                                @endif
                                            </span>

                                            <div class="word-list-word-content">
                                                <div class="word-list-main">{{ $wordItem->word }}</div>
                                                @if ($editingWordId === $wordItem->id)
                                                    <div class="word-list-edit-panel">
                                                        <select
                                                            id="word-edit-part-of-speech-{{ $wordItem->id }}"
                                                            class="word-list-edit-select"
                                                            wire:model.defer="editingWordPartOfSpeech"
                                                            aria-label="{{ __('dictionaries.show.fields.part_of_speech') }}"
                                                        >
                                                            @foreach ($partOfSpeechOptions as $partOfSpeechValue => $partOfSpeechLabel)
                                                                <option value="{{ $partOfSpeechValue }}">{!! $partOfSpeechLabel !!}</option>
                                                            @endforeach
                                                        </select>

                                                        @error('editingWordPartOfSpeech')
                                                            <p class="dictionaries-error word-list-edit-error">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                @else
                                                    <div class="word-list-meta">
                                                        {{ __($wordLanguageKey) }}
                                                        &middot;
                                                        {{ $partOfSpeechDisplayMap[$wordItem->part_of_speech] ?? __('dictionaries.show.word_list.part_of_speech_not_specified') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($editingWordId === $wordItem->id)
                                            <div class="word-list-edit-panel">
                                                <input
                                                    id="word-edit-translation-{{ $wordItem->id }}"
                                                    type="text"
                                                    class="word-list-edit-input"
                                                    wire:model.defer="editingWordTranslation"
                                                    aria-label="{{ __('dictionaries.show.fields.translation') }}"
                                                    wire:keydown.enter.prevent="updateEditingWord"
                                                    wire:keydown.escape.prevent="cancelEditingWord"
                                                >

                                                @error('editingWordTranslation')
                                                    <p class="dictionaries-error word-list-edit-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="word-list-translation">{{ $wordItem->translation }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingWordId === $wordItem->id)
                                            <div class="word-list-edit-panel">
                                                <input
                                                    id="word-edit-comment-{{ $wordItem->id }}"
                                                    type="text"
                                                    class="word-list-edit-input"
                                                    wire:model.defer="editingWordComment"
                                                    aria-label="{{ __('dictionaries.show.fields.comment') }}"
                                                    wire:keydown.enter.prevent="updateEditingWord"
                                                    wire:keydown.escape.prevent="cancelEditingWord"
                                                >

                                                @error('editingWordComment')
                                                    <p class="dictionaries-error word-list-edit-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="word-list-comment">{{ $wordItem->comment ?: __('dictionaries.show.word_list.no_comment') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="word-list-badge">{{ $wordItem->pivot->created_at?->translatedFormat('M d') ?? '-' }}</span>
                                    </td>
                                    <td class="word-list-action-cell">
                                        <div class="word-list-actions">
                                            @if ($editingWordId === $wordItem->id)
                                                <div class="word-list-edit-actions">
                                                    <button
                                                        type="button"
                                                        class="word-list-edit-accept-btn"
                                                        wire:click="updateEditingWord"
                                                    >
                                                        {{ __('dictionaries.show.word_list.edit.accept') }}
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="word-list-edit-cancel-btn"
                                                        wire:click="cancelEditingWord"
                                                    >
                                                        {{ __('dictionaries.show.word_list.edit.cancel') }}
                                                    </button>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    class="word-list-edit-btn"
                                                    wire:click="startEditingWord({{ $wordItem->id }})"
                                                    aria-label="{{ __('dictionaries.show.word_list.edit.aria', ['name' => $wordItem->word]) }}"
                                                >
                                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="m4 20 4.25-1.06a2 2 0 0 0 .9-.52L19 8.57a2.12 2.12 0 0 0-3-3L6.15 15.42a2 2 0 0 0-.52.9L4 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="m14.5 7.5 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    </svg>
                                                </button>
                                            @endif

                                            <button
                                                type="button"
                                                class="word-list-delete-btn"
                                                wire:key="word-delete-btn-{{ $wordItem->id }}"
                                                wire:click="confirmDeleteWord({{ $wordItem->id }})"
                                                aria-label="{{ __('dictionaries.show.word_list.delete.aria', ['name' => $wordItem->word]) }}"
                                            >
                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M9 3h6m-8 3h10m-1 0-.7 11.2A2 2 0 0 1 13.3 19h-2.6a2 2 0 0 1-1.99-1.8L8 6m3 4v5m2-5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="word-list-mistake-legend">
                    <span class="word-list-mistake-marker" aria-hidden="true"></span>
                    <span>{{ __('dictionaries.show.word_list.remainder_mistake_legend') }}</span>
                </p>

                <div class="word-list-pagination">
                    <p class="word-list-pagination__info">
                        {{ __('dictionaries.show.word_list.pagination.showing', ['from' => $words->firstItem(), 'to' => $words->lastItem(), 'total' => $words->total()]) }}
                    </p>

                    <div class="word-list-pagination__nav">
                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="previousPage"
                            @disabled($words->onFirstPage())
                        >
                            {{ __('dictionaries.show.word_list.pagination.prev') }}
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
                            {{ __('dictionaries.show.word_list.pagination.next') }}
                        </button>
                    </div>
                </div>
            @endif
        </article>

        @if ($pendingDeleteWordId !== null)
            <div class="dictionary-delete-overlay" wire:key="delete-overlay-{{ $pendingDeleteWordId }}" wire:click="cancelDeleteWord">
                <div class="dictionary-delete-dialog" wire:click.stop>
                    <div class="dictionary-delete-modal">
                        <h2 class="dictionary-delete-modal__title">{{ __('dictionaries.show.word_list.delete.title') }}</h2>

                        <p class="dictionary-delete-modal__text">
                            {{ __('dictionaries.show.word_list.delete.text', ['name' => $pendingDeleteWordLabel]) }}
                        </p>

                        <div class="dictionary-delete-modal__actions">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                wire:key="delete-cancel-{{ $pendingDeleteWordId }}"
                                wire:click="cancelDeleteWord"
                            >
                                {{ __('dictionaries.show.word_list.delete.no') }}
                            </button>

                            <button
                                type="button"
                                class="dictionary-delete-modal__danger-btn"
                                wire:key="delete-confirm-{{ $pendingDeleteWordId }}"
                                wire:click="deleteConfirmedWord"
                            >
                                {{ __('dictionaries.show.word_list.delete.yes') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
</main>


