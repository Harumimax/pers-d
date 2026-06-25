<main class="dictionaries-main dictionary-show-main">
    <section class="dictionaries-container dictionary-show">
        @php
            $dictionaryLanguageLabel = __('dictionaries.index.languages.' . strtolower($readyDictionary->language));
            $createdDateLabel = $readyDictionary->created_at?->translatedFormat('Y-m-d') ?? __('dictionaries.show.unknown_date');
        @endphp

        <header class="dictionary-show__header">
            <div class="dictionary-show__title-row">
                <h1 class="dictionary-show__title">{{ $readyDictionary->name }}</h1>
            </div>

            <p class="dictionary-show__subtitle">
                {!! __('ready_dictionaries.show.subtitle', ['language' => '<b>' . e($dictionaryLanguageLabel) . '</b>', 'count' => '<b>' . e($totalWordsCount) . '</b>', 'date' => '<b>' . e($createdDateLabel) . '</b>']) !!}
            </p>
        </header>

        @if ($transferBannerMessage !== null)
            <div class="dictionary-show-transfer-alert dictionary-show-transfer-alert--{{ $transferBannerType }}" role="{{ $transferBannerType === 'success' ? 'status' : 'alert' }}">
                <span class="dictionary-show-transfer-alert__icon" aria-hidden="true">
                    @if ($transferBannerType === 'success')
                        ✓
                    @else
                        !
                    @endif
                </span>
                <span>{{ $transferBannerMessage }}</span>
            </div>
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
                                <th style="width: 28%;">{{ __('dictionaries.show.word_list.table.word') }}</th>
                                <th data-pronounce-heading style="width: 6%; text-align: center;">{{ __('dictionaries.show.word_list.table.pronounce') }}</th>
                                <th style="width: 22%;">{{ __('dictionaries.show.word_list.table.translation') }}</th>
                                <th style="width: 24%;">{{ __('dictionaries.show.word_list.table.comment') }}</th>
                                <th style="width: 12%;">{{ __('dictionaries.show.word_list.table.added') }}</th>
                                <th style="width: 8%; text-align: center;">{{ __('dictionaries.show.word_list.table.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($words as $wordItem)
                                @php
                                    $pronounceLocale = match (strtolower($readyDictionary->language ?? '')) {
                                        'english' => 'en-US',
                                        'spanish' => 'es-ES',
                                        'german' => 'de-DE',
                                        'italian' => 'it-IT',
                                        'portuguese' => 'pt-PT',
                                        default => null,
                                    };
                                @endphp
                                <tr wire:key="ready-word-row-{{ $wordItem->id }}">
                                    <td>
                                        <div class="word-list-main-row">
                                            <div class="word-list-main">{{ $wordItem->word }}</div>
                                            <x-word-example-hint :examples="$wordItem->examples" :word="$wordItem->word" />
                                        </div>
                                        <div class="word-list-meta">
                                            {{ $dictionaryLanguageLabel }}
                                            &middot;
                                            {{ $partOfSpeechDisplayMap[$wordItem->part_of_speech] ?? __('dictionaries.show.word_list.part_of_speech_not_specified') }}
                                        </div>
                                    </td>
                                    <td class="word-list-pronounce-cell">
                                        @if ($pronounceLocale !== null)
                                            <button
                                                type="button"
                                                class="word-list-pronounce-btn"
                                                title="{{ __('dictionaries.show.word_list.pronounce.tooltip') }}"
                                                aria-label="{{ __('dictionaries.show.word_list.pronounce.aria', ['word' => $wordItem->word]) }}"
                                                data-pronounce-button
                                                data-pronounce-word="{{ $wordItem->word }}"
                                                data-pronounce-lang="{{ $pronounceLocale }}"
                                            >
                                                <span class="word-list-pronounce-btn__icon" aria-hidden="true">🔊</span>
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="word-list-translation">{{ $wordItem->translation }}</div>
                                    </td>
                                    <td>
                                        <div class="word-list-comment">{{ $wordItem->comment ?: __('dictionaries.show.word_list.no_comment') }}</div>
                                    </td>
                                    <td>
                                        <span class="word-list-badge">{{ $wordItem->created_at?->translatedFormat('M d') ?? '-' }}</span>
                                    </td>
                                    <td class="word-list-action-cell">
                                        <div class="word-list-actions">
                                            @auth
                                                <button
                                                    type="button"
                                                    class="word-list-favorite-btn {{ ($favoriteWordMap[$wordItem->id] ?? false) ? 'word-list-favorite-btn--active' : '' }}"
                                                    wire:click="toggleFavoriteWord({{ $wordItem->id }})"
                                                    aria-label="{{ __('dictionaries.show.word_list.favorite.aria', ['word' => $wordItem->word]) }}"
                                                    title="{{ ($favoriteWordMap[$wordItem->id] ?? false) ? __('dictionaries.show.word_list.favorite.remove_title') : __('dictionaries.show.word_list.favorite.add_title') }}"
                                                >
                                                    <span aria-hidden="true">{{ ($favoriteWordMap[$wordItem->id] ?? false) ? '★' : '☆' }}</span>
                                                </button>
                                            @endauth

                                            <div class="word-list-transfer-picker">
                                                <button
                                                    type="button"
                                                    class="word-list-transfer-btn"
                                                    aria-label="{{ __('ready_dictionaries.show.transfer.aria', ['word' => $wordItem->word]) }}"
                                                >
                                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M5 12h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                        <path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M4 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                    </svg>
                                                </button>

                                                <div class="word-list-transfer-menu" role="menu">
                                                    @auth
                                                        @if ($userDictionaries->isNotEmpty())
                                                        <p class="word-list-transfer-menu__title">{{ __('ready_dictionaries.show.transfer.title') }}</p>
                                                        @foreach ($userDictionaries as $userDictionary)
                                                            <button
                                                                type="button"
                                                                class="word-list-transfer-menu__item"
                                                                role="menuitem"
                                                                wire:key="ready-word-{{ $wordItem->id }}-transfer-dictionary-{{ $userDictionary->id }}"
                                                                wire:click="transferWordToDictionary({{ $wordItem->id }}, {{ $userDictionary->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="transferWordToDictionary({{ $wordItem->id }}, {{ $userDictionary->id }})"
                                                            >
                                                                {{ $userDictionary->name }}
                                                            </button>
                                                        @endforeach
                                                        @else
                                                            <p class="word-list-transfer-menu__empty">{{ __('ready_dictionaries.show.transfer.empty') }}</p>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('register') }}" class="word-list-transfer-menu__auth-link" role="menuitem">
                                                            {{ __('ready_dictionaries.show.transfer.guest_empty') }}
                                                        </a>
                                                    @endauth
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="word-list-mobile-list">
                    @foreach ($words as $wordItem)
                        @php
                            $pronounceLocale = match (strtolower($readyDictionary->language ?? '')) {
                                'english' => 'en-US',
                                'spanish' => 'es-ES',
                                'german' => 'de-DE',
                                'italian' => 'it-IT',
                                'portuguese' => 'pt-PT',
                                default => null,
                            };
                        @endphp
                        <article class="word-list-mobile-card" wire:key="ready-word-mobile-card-{{ $wordItem->id }}">
                            <div class="word-list-mobile-card__header">
                                <div class="word-list-word-content">
                                    <div class="word-list-mobile-card__title-line">
                                        <span class="word-list-main">{{ $wordItem->word }}</span>
                                        <x-word-example-hint :examples="$wordItem->examples" :word="$wordItem->word" />
                                        @if ($pronounceLocale !== null)
                                            <button
                                                type="button"
                                                class="word-list-pronounce-btn word-list-pronounce-btn--inline"
                                                title="{{ __('dictionaries.show.word_list.pronounce.tooltip') }}"
                                                aria-label="{{ __('dictionaries.show.word_list.pronounce.aria', ['word' => $wordItem->word]) }}"
                                                data-pronounce-button
                                                data-pronounce-word="{{ $wordItem->word }}"
                                                data-pronounce-lang="{{ $pronounceLocale }}"
                                            >
                                                <span class="word-list-pronounce-btn__icon" aria-hidden="true">🔊</span>
                                            </button>
                                        @endif
                                        <span class="word-list-mobile-card__separator">&mdash;</span>
                                        <span class="word-list-translation">{{ $wordItem->translation }}</span>
                                    </div>
                                    <div class="word-list-meta">
                                        {{ $dictionaryLanguageLabel }}
                                        &middot;
                                        {{ $partOfSpeechDisplayMap[$wordItem->part_of_speech] ?? __('dictionaries.show.word_list.part_of_speech_not_specified') }}
                                    </div>
                                </div>
                            </div>

                            <div class="word-list-mobile-card__body">
                                <div class="word-list-mobile-card__section">
                                    <div class="word-list-comment">{{ $wordItem->comment ?: __('dictionaries.show.word_list.no_comment') }}</div>
                                </div>
                            </div>

                            <div class="word-list-mobile-card__actions">
                                <div class="word-list-actions">
                                    @auth
                                        <button
                                            type="button"
                                            class="word-list-favorite-btn {{ ($favoriteWordMap[$wordItem->id] ?? false) ? 'word-list-favorite-btn--active' : '' }}"
                                            wire:click="toggleFavoriteWord({{ $wordItem->id }})"
                                            aria-label="{{ __('dictionaries.show.word_list.favorite.aria', ['word' => $wordItem->word]) }}"
                                            title="{{ ($favoriteWordMap[$wordItem->id] ?? false) ? __('dictionaries.show.word_list.favorite.remove_title') : __('dictionaries.show.word_list.favorite.add_title') }}"
                                        >
                                            <span aria-hidden="true">{{ ($favoriteWordMap[$wordItem->id] ?? false) ? '★' : '☆' }}</span>
                                        </button>
                                    @endauth

                                    <div class="word-list-transfer-picker">
                                        <button
                                            type="button"
                                            class="word-list-transfer-btn"
                                            aria-label="{{ __('ready_dictionaries.show.transfer.aria', ['word' => $wordItem->word]) }}"
                                        >
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M5 12h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                <path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M4 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </button>

                                        <div class="word-list-transfer-menu" role="menu">
                                            @auth
                                                @if ($userDictionaries->isNotEmpty())
                                                <p class="word-list-transfer-menu__title">{{ __('ready_dictionaries.show.transfer.title') }}</p>
                                                @foreach ($userDictionaries as $userDictionary)
                                                    <button
                                                        type="button"
                                                        class="word-list-transfer-menu__item"
                                                        role="menuitem"
                                                        wire:key="ready-word-mobile-{{ $wordItem->id }}-transfer-dictionary-{{ $userDictionary->id }}"
                                                        wire:click="transferWordToDictionary({{ $wordItem->id }}, {{ $userDictionary->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="transferWordToDictionary({{ $wordItem->id }}, {{ $userDictionary->id }})"
                                                    >
                                                        {{ $userDictionary->name }}
                                                    </button>
                                                @endforeach
                                                @else
                                                    <p class="word-list-transfer-menu__empty">{{ __('ready_dictionaries.show.transfer.empty') }}</p>
                                                @endif
                                            @else
                                                <a href="{{ route('register') }}" class="word-list-transfer-menu__auth-link" role="menuitem">
                                                    {{ __('ready_dictionaries.show.transfer.guest_empty') }}
                                                </a>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

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

                        @php
                            $lastPage = $words->lastPage();
                            $currentPage = $words->currentPage();
                            $visiblePages = $lastPage > 6
                                ? [1, 2, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage - 1, $lastPage]
                                : range(1, $lastPage);

                            $visiblePages = array_values(array_unique(array_filter(
                                $visiblePages,
                                static fn (int $page): bool => $page >= 1 && $page <= $lastPage,
                            )));
                            sort($visiblePages);
                            $previousVisiblePage = null;
                        @endphp

                        @foreach ($visiblePages as $page)
                            @if ($previousVisiblePage !== null && $page - $previousVisiblePage > 1)
                                <span class="word-list-page-btn word-list-page-btn--ellipsis" aria-hidden="true">...</span>
                            @endif

                            <button
                                type="button"
                                class="word-list-page-btn {{ $words->currentPage() === $page ? 'is-active' : '' }}"
                                wire:click="gotoPage({{ $page }})"
                            >
                                {{ $page }}
                            </button>

                            @php
                                $previousVisiblePage = $page;
                            @endphp
                        @endforeach

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
    </section>
</main>
