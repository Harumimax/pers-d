<main class="dictionaries-main">
    @if (session('status'))
        <section class="dictionaries-container dictionaries-search-card" aria-label="Status message">
            <p class="dictionary-show-transfer-alert dictionary-show-transfer-alert--success" role="status">
                {{ session('status') }}
            </p>
        </section>
    @endif

    <section class="dictionaries-container dictionaries-intro">
        <div class="dictionaries-intro__copy">
            <h1 class="dictionaries-title">{{ __('dictionaries.index.title') }}</h1>
            <p class="dictionaries-subtitle">{{ __('dictionaries.index.subtitle') }}</p>
        </div>

        @if (! $showCreateForm)
            <button type="button" class="btn btn-primary dictionaries-new-btn" wire:click="openCreateForm">
                <span class="dictionaries-new-btn__plus">+</span>
                <span>{{ __('dictionaries.index.new_dictionary') }}</span>
            </button>
        @endif
    </section>

    @if ($showCreateForm)
        <section class="dictionaries-container dictionaries-create-card" aria-label="{{ __('dictionaries.index.create_form_aria') }}">
            <form class="dictionaries-create-form" wire:submit="createDictionary" wire:key="dictionary-create-form-{{ $formRenderKey }}">
                <div class="dictionaries-field">
                    <label for="dictionary-name" class="dictionaries-label">{{ __('dictionaries.index.fields.name') }}</label>
                    <input
                        id="dictionary-name"
                        type="text"
                        class="dictionaries-input"
                        placeholder="{{ __('dictionaries.index.placeholders.name') }}"
                        wire:model.defer="name"
                    >
                    @error('name')
                        <p class="dictionaries-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dictionaries-field">
                    <label for="dictionary-language" class="dictionaries-label">{{ __('dictionaries.index.fields.language') }}</label>
                    <select
                        id="dictionary-language"
                        class="dictionaries-input"
                        wire:model.defer="language"
                    >
                        <option value="" disabled>{{ __('dictionaries.index.language_prompt') }}</option>
                        <option value="English">{{ __('dictionaries.index.languages.english') }}</option>
                        <option value="Spanish">{{ __('dictionaries.index.languages.spanish') }}</option>
                    </select>
                    @error('language')
                        <p class="dictionaries-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dictionaries-create-actions">
                    <button type="submit" class="btn btn-primary dictionaries-action-btn">
                        {{ __('dictionaries.index.actions.create') }}
                    </button>
                    <button type="button" class="btn btn-secondary dictionaries-action-btn" wire:click="cancelCreate">
                        {{ __('dictionaries.index.actions.cancel') }}
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="dictionaries-container dictionaries-search-card" aria-label="{{ __('dictionaries.index.search.aria') }}">
        <div class="dictionaries-search-card__header">
            <div>
                <h2 class="dictionaries-search-card__title">{{ __('dictionaries.index.search.title') }}</h2>
            </div>
        </div>

        <form class="dictionaries-search-form" wire:submit="searchWords">
            <div class="dictionaries-field dictionaries-search-form__field">
                <input
                    id="dictionary-global-search"
                    type="text"
                    class="dictionaries-input"
                    placeholder="{{ __('dictionaries.index.search.placeholder') }}"
                    wire:model.defer="searchQuery"
                >
                @error('searchQuery')
                    <p class="dictionaries-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dictionaries-create-actions dictionaries-search-form__actions">
                <button type="submit" class="btn btn-primary dictionaries-action-btn">
                    {{ __('dictionaries.index.search.actions.find') }}
                </button>

                @if ($searchQuery !== '' || $searchSubmitted)
                    <button type="button" class="btn btn-secondary dictionaries-action-btn" wire:click="clearSearch">
                        {{ __('dictionaries.index.search.actions.clear') }}
                    </button>
                @endif
            </div>
        </form>

        @if ($searchSubmitted && trim($searchQuery) !== '' && $searchResults->isEmpty())
            <p class="dictionary-show-list__empty dictionaries-search-card__empty">{{ __('dictionaries.index.search.results.empty') }}</p>
        @endif
    </section>

    @if ($searchSubmitted && trim($searchQuery) !== '' && $searchResults->isNotEmpty())
        <section class="dictionaries-container dictionary-show-card dictionaries-search-results" aria-label="{{ __('dictionaries.index.search.results.title') }}">
            <div class="word-list-header dictionaries-search-results__header">
                <div>
                    <h2 class="dictionary-show-card__title">{{ __('dictionaries.index.search.results.title') }}</h2>
                    <p class="word-list-subtitle">
                        {{ trans_choice('dictionaries.index.search.results.subtitle', $searchResults->count(), ['count' => $searchResults->count()]) }}
                    </p>
                </div>
            </div>

            <div class="word-list-table-wrap">
                <table class="word-list-table">
                    <thead>
                        <tr>
                            <th class="word-list-table__word-heading" style="width: 30%;">{{ __('dictionaries.index.search.results.table.word') }}</th>
                            <th data-pronounce-heading style="width: 6%; text-align: center;">{{ __('dictionaries.index.search.results.table.pronounce') }}</th>
                            <th style="width: 22%;">{{ __('dictionaries.index.search.results.table.translation') }}</th>
                            <th style="width: 22%;">{{ __('dictionaries.index.search.results.table.comment') }}</th>
                            <th style="width: 12%;">{{ __('dictionaries.index.search.results.table.added') }}</th>
                            <th style="width: 8%; text-align: center;">{{ __('dictionaries.index.search.results.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($searchResults as $searchResult)
                            @php
                                $languageKey = $searchResult->dictionary_language !== null
                                    ? 'dictionaries.index.languages.' . strtolower($searchResult->dictionary_language)
                                    : 'dictionaries.index.languages.not_specified';
                                $pronounceLocale = match (strtolower($searchResult->dictionary_language ?? '')) {
                                    'english' => 'en-US',
                                    'spanish' => 'es-ES',
                                    default => null,
                                };
                            @endphp
                            <tr wire:key="dictionary-search-result-{{ $searchResult->dictionary_id }}-{{ $searchResult->word_id }}">
                                <td>
                                    <div class="word-list-word-cell">
                                        <span class="word-list-mistake-marker-slot">
                                            @if ($searchResult->remainder_had_mistake)
                                                <span
                                                    class="word-list-mistake-marker"
                                                    aria-label="{{ __('dictionaries.index.search.results.remainder_mistake_marker_aria') }}"
                                                ></span>
                                            @endif
                                        </span>

                                        <div class="word-list-word-content">
                                            <div class="word-list-main">{{ $searchResult->word }}</div>
                                            <div class="word-list-meta">
                                                {{ $searchResult->dictionary_name }}
                                                &middot;
                                                {{ __($languageKey) }}
                                                &middot;
                                                {{ $partOfSpeechDisplayMap[$searchResult->part_of_speech] ?? __('dictionaries.index.search.results.part_of_speech_not_specified') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="word-list-pronounce-cell">
                                    @if ($pronounceLocale !== null)
                                        <button
                                            type="button"
                                            class="word-list-pronounce-btn"
                                            title="{{ __('dictionaries.show.word_list.pronounce.tooltip') }}"
                                            aria-label="{{ __('dictionaries.show.word_list.pronounce.aria', ['word' => $searchResult->word]) }}"
                                            data-pronounce-button
                                            data-pronounce-word="{{ $searchResult->word }}"
                                            data-pronounce-lang="{{ $pronounceLocale }}"
                                        >
                                            <span class="word-list-pronounce-btn__icon" aria-hidden="true">🔊</span>
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <div class="word-list-translation">{{ $searchResult->translation }}</div>
                                </td>
                                <td>
                                    <div class="word-list-comment">{{ $searchResult->comment ?: __('dictionaries.index.search.results.no_comment') }}</div>
                                </td>
                                <td>
                                    <span class="word-list-badge">{{ $searchResult->attached_at?->translatedFormat('M d') ?? '-' }}</span>
                                </td>
                                <td class="word-list-action-cell">
                                    <div class="word-list-actions">
                                        <a
                                            href="{{ route('dictionaries.show', $searchResult->dictionary_id) }}"
                                            class="word-list-edit-btn dictionaries-search-results__open-link"
                                            aria-label="{{ __('dictionaries.index.search.results.open_aria', ['word' => $searchResult->word, 'dictionary' => $searchResult->dictionary_name]) }}"
                                        >
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M14 5h5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M10 14 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M19 13v4a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="word-list-mobile-list">
                @foreach ($searchResults as $searchResult)
                    @php
                        $languageKey = $searchResult->dictionary_language !== null
                            ? 'dictionaries.index.languages.' . strtolower($searchResult->dictionary_language)
                            : 'dictionaries.index.languages.not_specified';
                        $pronounceLocale = match (strtolower($searchResult->dictionary_language ?? '')) {
                            'english' => 'en-US',
                            'spanish' => 'es-ES',
                            default => null,
                        };
                    @endphp
                    <article class="word-list-mobile-card" wire:key="dictionary-search-mobile-result-{{ $searchResult->dictionary_id }}-{{ $searchResult->word_id }}">
                        <div class="word-list-mobile-card__header">
                            <div class="word-list-word-cell">
                                <span class="word-list-mistake-marker-slot">
                                    @if ($searchResult->remainder_had_mistake)
                                        <span
                                            class="word-list-mistake-marker"
                                            aria-label="{{ __('dictionaries.index.search.results.remainder_mistake_marker_aria') }}"
                                        ></span>
                                    @endif
                                </span>

                                <div class="word-list-word-content">
                                    <div class="word-list-mobile-card__title-line">
                                        <span class="word-list-main">{{ $searchResult->word }}</span>
                                        @if ($pronounceLocale !== null)
                                            <button
                                                type="button"
                                                class="word-list-pronounce-btn word-list-pronounce-btn--inline"
                                                title="{{ __('dictionaries.show.word_list.pronounce.tooltip') }}"
                                                aria-label="{{ __('dictionaries.show.word_list.pronounce.aria', ['word' => $searchResult->word]) }}"
                                                data-pronounce-button
                                                data-pronounce-word="{{ $searchResult->word }}"
                                                data-pronounce-lang="{{ $pronounceLocale }}"
                                            >
                                                <span class="word-list-pronounce-btn__icon" aria-hidden="true">🔊</span>
                                            </button>
                                        @endif
                                        <span class="word-list-mobile-card__separator">&mdash;</span>
                                        <span class="word-list-translation">{{ $searchResult->translation }}</span>
                                    </div>
                                    <div class="word-list-meta">
                                        {{ $searchResult->dictionary_name }}
                                        &middot;
                                        {{ __($languageKey) }}
                                        &middot;
                                        {{ $partOfSpeechDisplayMap[$searchResult->part_of_speech] ?? __('dictionaries.index.search.results.part_of_speech_not_specified') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="word-list-mobile-card__body">
                            <div class="word-list-mobile-card__section">
                                <div class="word-list-comment">{{ $searchResult->comment ?: __('dictionaries.index.search.results.no_comment') }}</div>
                            </div>
                        </div>

                        <div class="word-list-mobile-card__actions">
                            <div class="word-list-actions">
                                <a
                                    href="{{ route('dictionaries.show', $searchResult->dictionary_id) }}"
                                    class="word-list-edit-btn dictionaries-search-results__open-link"
                                    aria-label="{{ __('dictionaries.index.search.results.open_aria', ['word' => $searchResult->word, 'dictionary' => $searchResult->dictionary_name]) }}"
                                >
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M14 5h5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M10 14 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M19 13v4a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="word-list-mistake-legend">
                <span class="word-list-mistake-marker" aria-hidden="true"></span>
                <span>{{ __('dictionaries.index.search.results.remainder_mistake_legend') }}</span>
            </p>
        </section>
    @endif

    <section class="dictionaries-container dictionaries-list" aria-label="{{ __('dictionaries.index.title') }}">
        @php
            $hasAnyDictionaries = true;
        @endphp

        <article class="dictionary-card {{ $favoriteDictionary['is_clickable'] ? 'dictionary-card--clickable dictionary-card--favorites' : 'dictionary-card--favorites dictionary-card--disabled' }}" wire:key="favorite-dictionary-card">
            @if ($favoriteDictionary['is_clickable'])
                <a
                    href="{{ route('dictionaries.favorites') }}"
                    class="dictionary-card__overlay-link"
                    aria-label="{{ __('dictionaries.index.favorites.open_aria') }}"
                ></a>
            @endif

            <div class="dictionary-card__content">
                <div class="dictionary-card__badge-row">
                    <span class="dictionary-card__badge dictionary-card__badge--favorites">{{ __('dictionaries.index.favorites.badge') }}</span>
                </div>

                <h2 class="dictionary-card__title">
                    @if ($favoriteDictionary['is_clickable'])
                        <a href="{{ route('dictionaries.favorites') }}">{{ $favoriteDictionary['name'] }}</a>
                    @else
                        {{ $favoriteDictionary['name'] }}
                    @endif
                </h2>

                <p class="dictionary-card__meta">
                    @if ($favoriteDictionary['count'] > 0)
                        {{ trans_choice('dictionaries.index.favorites.count', $favoriteDictionary['count'], ['count' => $favoriteDictionary['count']]) }}
                    @else
                        {{ __('dictionaries.index.favorites.empty_count') }}
                    @endif
                </p>
            </div>
        </article>

        @foreach ($ownedDictionaries as $dictionary)
            @php
                $languageKey = $dictionary->language !== null ? 'dictionaries.index.languages.' . strtolower($dictionary->language) : 'dictionaries.index.languages.not_specified';
            @endphp
            <article class="dictionary-card {{ $editingDictionaryId === $dictionary->id ? '' : 'dictionary-card--clickable' }}" wire:key="dictionary-{{ $dictionary->id }}">
                @if ($editingDictionaryId !== $dictionary->id)
                    <a
                        href="{{ route('dictionaries.show', $dictionary) }}"
                        class="dictionary-card__overlay-link"
                        aria-label="{{ __('dictionaries.index.card.open_aria', ['name' => $dictionary->name]) }}"
                    ></a>
                @endif

                <div class="dictionary-card__content">
                    <div class="dictionary-card__badge-row">
                        <span class="dictionary-card__badge dictionary-card__badge--owned">{{ __('dictionaries.index.card.badges.owned') }}</span>
                    </div>

                    @if ($editingDictionaryId === $dictionary->id)
                        <div class="dictionary-card__edit-form">
                            <div class="dictionary-card__edit-field">
                                <input
                                    type="text"
                                    class="dictionary-card__edit-input"
                                    wire:model.defer="editingDictionaryName"
                                    aria-label="{{ __('dictionaries.index.edit.field_aria', ['name' => $dictionary->name]) }}"
                                    wire:keydown.enter.prevent="updateEditingDictionaryName"
                                    wire:keydown.escape.prevent="cancelEditingDictionary"
                                >

                                @error('editingDictionaryName')
                                    <p class="dictionaries-error dictionary-card__edit-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="dictionary-card__edit-actions">
                                <button
                                    type="button"
                                    class="dictionary-card__edit-submit"
                                    wire:click="updateEditingDictionaryName"
                                >
                                    {{ __('dictionaries.index.edit.accept') }}
                                </button>

                                <button
                                    type="button"
                                    class="dictionary-card__edit-cancel"
                                    wire:click="cancelEditingDictionary"
                                >
                                    {{ __('dictionaries.index.edit.cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <h2 class="dictionary-card__title">
                            <a href="{{ route('dictionaries.show', $dictionary) }}">{{ $dictionary->name }}</a>
                        </h2>
                    @endif

                    <p class="dictionary-card__meta">
                        {{ __($languageKey) }}
                        <span class="dictionary-card__dot">&middot;</span>
                        {{ trans_choice('dictionaries.index.words_count', $dictionary->words_count ?? 0, ['count' => $dictionary->words_count ?? 0]) }}
                        <span class="dictionary-card__dot">&middot;</span>
                        {{ __('dictionaries.index.meta.created', ['date' => $dictionary->created_at?->translatedFormat('Y-m-d')]) }}
                    </p>
                </div>

                <div class="dictionary-card__actions">
                    <button
                        type="button"
                        class="dictionary-card__share"
                        wire:click="openShareDictionaryModal({{ $dictionary->id }})"
                        aria-label="{{ __('dictionaries.index.share.aria', ['name' => $dictionary->name]) }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 8a3 3 0 1 0-2.83-4H13a3 3 0 0 0 .17 1L7.91 8.09a3 3 0 0 0-3.74.42A3 3 0 1 0 5 13a3 3 0 0 0 2.91-.91l5.26 3.09A3 3 0 0 0 13 16a3 3 0 1 0 3-3c-.65 0-1.25.2-1.75.54l-5.22-3.07c.03-.15.05-.31.05-.47s-.02-.32-.05-.47l5.22-3.07C14.75 7.8 15.35 8 16 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="dictionary-card__edit"
                        wire:click="startEditingDictionary({{ $dictionary->id }})"
                        aria-label="{{ __('dictionaries.index.edit.aria', ['name' => $dictionary->name]) }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m4 20 4.25-1.06a2 2 0 0 0 .9-.52L19 8.57a2.12 2.12 0 0 0-3-3L6.15 15.42a2 2 0 0 0-.52.9L4 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="m14.5 7.5 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="dictionary-card__delete"
                        wire:click="confirmDeleteDictionary({{ $dictionary->id }})"
                        aria-label="{{ __('dictionaries.index.delete.aria', ['name' => $dictionary->name]) }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 3h6m-8 3h10m-1 0-.7 11.2A2 2 0 0 1 13.3 19h-2.6a2 2 0 0 1-1.99-1.8L8 6m3 4v5m2-5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </article>
        @endforeach

        @foreach ($subscribedDictionaries as $dictionary)
            @php
                $languageKey = $dictionary->language !== null ? 'dictionaries.index.languages.' . strtolower($dictionary->language) : 'dictionaries.index.languages.not_specified';
            @endphp
            <article class="dictionary-card dictionary-card--clickable dictionary-card--subscription" wire:key="subscribed-dictionary-{{ $dictionary->id }}">
                <a
                    href="{{ route('dictionaries.show', $dictionary) }}"
                    class="dictionary-card__overlay-link"
                    aria-label="{{ __('dictionaries.index.card.open_aria', ['name' => $dictionary->name]) }}"
                ></a>

                <div class="dictionary-card__content">
                    <div class="dictionary-card__badge-row">
                        <span class="dictionary-card__badge dictionary-card__badge--subscription">{{ __('dictionaries.index.card.badges.subscription') }}</span>
                    </div>

                    <h2 class="dictionary-card__title">
                        <a href="{{ route('dictionaries.show', $dictionary) }}">{{ $dictionary->name }}</a>
                    </h2>

                    <p class="dictionary-card__meta">
                        {{ __($languageKey) }}
                        <span class="dictionary-card__dot">&middot;</span>
                        {{ trans_choice('dictionaries.index.words_count', $dictionary->words_count ?? 0, ['count' => $dictionary->words_count ?? 0]) }}
                    </p>

                    <p class="dictionary-card__subscription-owner">
                        {{ __('dictionaries.index.card.subscription_owner', ['email' => $dictionary->owner_email ?? __('dictionaries.index.card.owner_unknown')]) }}
                    </p>
                </div>

                <div class="dictionary-card__actions dictionary-card__actions--subscription">
                    <button
                        type="button"
                        class="dictionary-card__unsubscribe"
                        wire:click="confirmUnsubscribeDictionary({{ $dictionary->id }})"
                    >
                        {{ __('dictionaries.index.unsubscribe.button') }}
                    </button>
                </div>
            </article>
        @endforeach

        @if (! $hasAnyDictionaries)
            <article class="dictionary-card dictionary-card--empty">
                <h2 class="dictionary-card__title">{{ __('dictionaries.index.empty.title') }}</h2>
                <p class="dictionary-card__meta">{{ __('dictionaries.index.empty.text') }}</p>
            </article>
        @endif
    </section>

    @if ($sharingDictionaryId !== null)
        <div class="dictionary-delete-overlay" wire:click="cancelShareDictionary">
            <div class="dictionary-delete-dialog" wire:click.stop>
                <div class="dictionary-delete-modal dictionary-share-modal">
                    <h2 class="dictionary-delete-modal__title">{{ __('dictionaries.index.share.title') }}</h2>

                    <p class="dictionary-delete-modal__text">
                        {{ __('dictionaries.index.share.text', ['name' => $sharingDictionaryLabel]) }}
                    </p>

                    <form class="dictionary-share-modal__form" wire:submit="sendShareInvitation">
                        <div class="dictionaries-field">
                            <label for="share-target-email" class="dictionaries-label">{{ __('dictionaries.index.share.fields.email') }}</label>
                            <input
                                id="share-target-email"
                                type="email"
                                class="dictionaries-input"
                                placeholder="{{ __('dictionaries.index.share.placeholders.email') }}"
                                wire:model.defer="sharingTargetEmail"
                            >
                            @error('sharingTargetEmail')
                                <p class="dictionaries-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dictionary-delete-modal__actions">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                wire:click="cancelShareDictionary"
                            >
                                {{ __('dictionaries.index.actions.cancel') }}
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                {{ __('dictionaries.index.share.submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingDeleteDictionaryId !== null)
        <div class="dictionary-delete-overlay" wire:click="cancelDeleteDictionary">
            <div class="dictionary-delete-dialog" wire:click.stop>
                <div class="dictionary-delete-modal">
                    <h2 class="dictionary-delete-modal__title">{{ __('dictionaries.index.delete.title') }}</h2>

                    <p class="dictionary-delete-modal__text">
                        {{ __('dictionaries.index.delete.text', ['name' => $pendingDeleteDictionaryLabel]) }}
                    </p>

                    <div class="dictionary-delete-modal__actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            wire:click="cancelDeleteDictionary"
                        >
                            {{ __('dictionaries.index.delete.no') }}
                        </button>

                        <button
                            type="button"
                            class="dictionary-delete-modal__danger-btn"
                            wire:click="deleteConfirmedDictionary"
                        >
                            {{ __('dictionaries.index.delete.yes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingUnsubscribeDictionaryId !== null)
        <div class="dictionary-delete-overlay" wire:click="cancelUnsubscribeDictionary">
            <div class="dictionary-delete-dialog" wire:click.stop>
                <div class="dictionary-delete-modal">
                    <h2 class="dictionary-delete-modal__title">{{ __('dictionaries.index.unsubscribe.title') }}</h2>

                    <p class="dictionary-delete-modal__text">
                        {{ __('dictionaries.index.unsubscribe.text', ['name' => $pendingUnsubscribeDictionaryLabel]) }}
                    </p>

                    <div class="dictionary-delete-modal__actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            wire:click="cancelUnsubscribeDictionary"
                        >
                            {{ __('dictionaries.index.unsubscribe.no') }}
                        </button>

                        <button
                            type="button"
                            class="dictionary-delete-modal__danger-btn"
                            wire:click="unsubscribeConfirmedDictionary"
                        >
                            {{ __('dictionaries.index.unsubscribe.yes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</main>
