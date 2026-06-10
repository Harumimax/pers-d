<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show">
        <header class="dictionary-show__header">
            <div class="dictionary-show__title-row">
                <div class="dictionary-show__heading">
                    <h1 class="dictionary-show__title">{{ __('dictionaries.index.favorites.name') }}</h1>
                </div>
            </div>

            <p class="dictionary-show__subtitle">
                {{ trans_choice('dictionaries.index.favorites.subtitle', $totalWordsCount, ['count' => $totalWordsCount]) }}
            </p>
        </header>

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

            @if ($favoriteWords->isEmpty())
                <p class="dictionary-show-list__empty">{{ __('dictionaries.index.favorites.empty') }}</p>
            @else
                <div class="word-list-table-wrap">
                    <table class="word-list-table">
                        <thead>
                            <tr>
                                <th style="width: 26%;">{{ __('dictionaries.show.word_list.table.word') }}</th>
                                <th data-pronounce-heading style="width: 6%; text-align: center;">{{ __('dictionaries.show.word_list.table.pronounce') }}</th>
                                <th style="width: 22%;">{{ __('dictionaries.show.word_list.table.translation') }}</th>
                                <th style="width: 20%;">{{ __('dictionaries.show.word_list.table.comment') }}</th>
                                <th style="width: 12%;">{{ __('dictionaries.show.word_list.table.added') }}</th>
                                <th style="width: 14%; text-align: center;">{{ __('dictionaries.show.word_list.table.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($favoriteWords as $favoriteWord)
                                @php
                                    $pronounceLocale = match (strtolower($favoriteWord->source_dictionary_language ?? '')) {
                                        'english' => 'en-US',
                                        'spanish' => 'es-ES',
                                        default => null,
                                    };
                                    $languageKey = $favoriteWord->source_dictionary_language !== null
                                        ? 'dictionaries.index.languages.' . strtolower($favoriteWord->source_dictionary_language)
                                        : 'dictionaries.index.languages.not_specified';
                                    $sourceUrl = $this->sourceDictionaryRoute($favoriteWord->source_dictionary_type, (int) $favoriteWord->source_dictionary_id);
                                @endphp
                                <tr wire:key="favorite-word-row-{{ $favoriteWord->favorite_id }}">
                                    <td>
                                        <div class="word-list-main-row">
                                            <div class="word-list-main">{{ $favoriteWord->word }}</div>
                                            <x-word-example-hint :examples="$favoriteWord->examples ?? collect()" :word="$favoriteWord->word" />
                                        </div>
                                        <div class="word-list-meta">
                                            {{ $favoriteWord->source_dictionary_name }}
                                            &middot;
                                            {{ __($languageKey) }}
                                            &middot;
                                            {{ $partOfSpeechDisplayMap[$favoriteWord->part_of_speech] ?? __('dictionaries.show.word_list.part_of_speech_not_specified') }}
                                        </div>
                                    </td>
                                    <td class="word-list-pronounce-cell">
                                        @if ($pronounceLocale !== null)
                                            <button
                                                type="button"
                                                class="word-list-pronounce-btn"
                                                title="{{ __('dictionaries.show.word_list.pronounce.tooltip') }}"
                                                aria-label="{{ __('dictionaries.show.word_list.pronounce.aria', ['word' => $favoriteWord->word]) }}"
                                                data-pronounce-button
                                                data-pronounce-word="{{ $favoriteWord->word }}"
                                                data-pronounce-lang="{{ $pronounceLocale }}"
                                            >
                                                <span class="word-list-pronounce-btn__icon" aria-hidden="true">🔊</span>
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="word-list-translation">{{ $favoriteWord->translation }}</div>
                                    </td>
                                    <td>
                                        <div class="word-list-comment">{{ $favoriteWord->comment ?: __('dictionaries.show.word_list.no_comment') }}</div>
                                    </td>
                                    <td>
                                        <span class="word-list-badge">{{ \Illuminate\Support\Carbon::parse($favoriteWord->favorite_created_at)->translatedFormat('M d') }}</span>
                                    </td>
                                    <td class="word-list-action-cell">
                                        <div class="word-list-actions">
                                            <a
                                                href="{{ $sourceUrl }}"
                                                class="word-list-edit-btn"
                                                aria-label="{{ __('dictionaries.index.favorites.open_source_aria', ['word' => $favoriteWord->word, 'dictionary' => $favoriteWord->source_dictionary_name]) }}"
                                            >
                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M14 5h5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M10 14 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M19 13v4a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </a>

                                            <button
                                                type="button"
                                                class="word-list-favorite-btn word-list-favorite-btn--active"
                                                wire:click="removeFavorite({{ $favoriteWord->favorite_id }})"
                                                aria-label="{{ __('dictionaries.index.favorites.remove_aria', ['word' => $favoriteWord->word]) }}"
                                                title="{{ __('dictionaries.index.favorites.remove_title') }}"
                                            >
                                                <span aria-hidden="true">★</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="word-list-mobile-list">
                    @foreach ($favoriteWords as $favoriteWord)
                        @php
                            $pronounceLocale = match (strtolower($favoriteWord->source_dictionary_language ?? '')) {
                                'english' => 'en-US',
                                'spanish' => 'es-ES',
                                default => null,
                            };
                            $languageKey = $favoriteWord->source_dictionary_language !== null
                                ? 'dictionaries.index.languages.' . strtolower($favoriteWord->source_dictionary_language)
                                : 'dictionaries.index.languages.not_specified';
                            $sourceUrl = $this->sourceDictionaryRoute($favoriteWord->source_dictionary_type, (int) $favoriteWord->source_dictionary_id);
                        @endphp
                        <article class="word-list-mobile-card" wire:key="favorite-word-mobile-card-{{ $favoriteWord->favorite_id }}">
                            <div class="word-list-mobile-card__header">
                                <div class="word-list-word-content">
                                    <div class="word-list-mobile-card__title-line">
                                        <span class="word-list-main">{{ $favoriteWord->word }}</span>
                                        <x-word-example-hint :examples="$favoriteWord->examples ?? collect()" :word="$favoriteWord->word" />
                                        @if ($pronounceLocale !== null)
                                            <button
                                                type="button"
                                                class="word-list-pronounce-btn word-list-pronounce-btn--inline"
                                                title="{{ __('dictionaries.show.word_list.pronounce.tooltip') }}"
                                                aria-label="{{ __('dictionaries.show.word_list.pronounce.aria', ['word' => $favoriteWord->word]) }}"
                                                data-pronounce-button
                                                data-pronounce-word="{{ $favoriteWord->word }}"
                                                data-pronounce-lang="{{ $pronounceLocale }}"
                                            >
                                                <span class="word-list-pronounce-btn__icon" aria-hidden="true">🔊</span>
                                            </button>
                                        @endif
                                        <span class="word-list-mobile-card__separator">&mdash;</span>
                                        <span class="word-list-translation">{{ $favoriteWord->translation }}</span>
                                    </div>
                                    <div class="word-list-meta">
                                        {{ $favoriteWord->source_dictionary_name }}
                                        &middot;
                                        {{ __($languageKey) }}
                                        &middot;
                                        {{ $partOfSpeechDisplayMap[$favoriteWord->part_of_speech] ?? __('dictionaries.show.word_list.part_of_speech_not_specified') }}
                                    </div>
                                </div>
                            </div>

                            <div class="word-list-mobile-card__body">
                                <div class="word-list-mobile-card__section">
                                    <div class="word-list-comment">{{ $favoriteWord->comment ?: __('dictionaries.show.word_list.no_comment') }}</div>
                                </div>
                            </div>

                            <div class="word-list-mobile-card__actions">
                                <div class="word-list-actions">
                                    <a
                                        href="{{ $sourceUrl }}"
                                        class="word-list-edit-btn"
                                        aria-label="{{ __('dictionaries.index.favorites.open_source_aria', ['word' => $favoriteWord->word, 'dictionary' => $favoriteWord->source_dictionary_name]) }}"
                                    >
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M14 5h5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10 14 19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M19 13v4a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>

                                    <button
                                        type="button"
                                        class="word-list-favorite-btn word-list-favorite-btn--active"
                                        wire:click="removeFavorite({{ $favoriteWord->favorite_id }})"
                                        aria-label="{{ __('dictionaries.index.favorites.remove_aria', ['word' => $favoriteWord->word]) }}"
                                        title="{{ __('dictionaries.index.favorites.remove_title') }}"
                                    >
                                        <span aria-hidden="true">★</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="word-list-pagination">
                    <p class="word-list-pagination__info">
                        {{ __('dictionaries.show.word_list.pagination.showing', ['from' => $favoriteWords->firstItem(), 'to' => $favoriteWords->lastItem(), 'total' => $favoriteWords->total()]) }}
                    </p>

                    <div class="word-list-pagination__nav">
                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="previousPage"
                            @disabled($favoriteWords->onFirstPage())
                        >
                            {{ __('dictionaries.show.word_list.pagination.prev') }}
                        </button>

                        @for ($page = 1; $page <= $favoriteWords->lastPage(); $page++)
                            <button
                                type="button"
                                class="word-list-page-btn {{ $favoriteWords->currentPage() === $page ? 'is-active' : '' }}"
                                wire:click="gotoPage({{ $page }})"
                            >
                                {{ $page }}
                            </button>
                        @endfor

                        <button
                            type="button"
                            class="word-list-page-btn"
                            wire:click="nextPage"
                            @disabled(! $favoriteWords->hasMorePages())
                        >
                            {{ __('dictionaries.show.word_list.pagination.next') }}
                        </button>
                    </div>
                </div>
            @endif
        </article>
    </section>
</main>
