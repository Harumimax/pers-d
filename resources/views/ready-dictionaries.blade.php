@component('layouts.dictionaries', [
    'headerDictionaries' => $headerDictionaries,
    'headerReadyDictionaries' => $headerReadyDictionaries,
])
    <main class="dictionaries-main">
        <section class="dictionaries-container dictionaries-intro">
            <div class="dictionaries-intro__copy">
                <h1 class="dictionaries-title">{{ __('ready_dictionaries.title') }}</h1>
                <p class="dictionaries-subtitle">{{ __('ready_dictionaries.description') }}</p>
            </div>
        </section>

        <section class="dictionaries-container dictionaries-create-card" aria-label="{{ __('ready_dictionaries.filters.aria') }}">
            <form method="GET" action="{{ route('ready-dictionaries.index') }}" class="dictionaries-create-form dictionaries-filter-form">
                <div class="dictionaries-field">
                    <label for="ready-language" class="dictionaries-label">{{ __('ready_dictionaries.filters.language') }}</label>
                    <select id="ready-language" name="language" class="dictionaries-input">
                        <option value="">{{ __('ready_dictionaries.filters.all_languages') }}</option>
                        @foreach ($filterOptions['languages'] as $language)
                            <option value="{{ $language }}" @selected($selectedFilters['language'] === $language)>
                                {{ __('dictionaries.index.languages.' . strtolower($language)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dictionaries-field">
                    <label for="ready-level" class="dictionaries-label">{{ __('ready_dictionaries.filters.level') }}</label>
                    <select id="ready-level" name="level" class="dictionaries-input">
                        <option value="">{{ __('ready_dictionaries.filters.all_levels') }}</option>
                        @foreach ($filterOptions['levels'] as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFilters['level'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dictionaries-field">
                    <label for="ready-part-of-speech" class="dictionaries-label">{{ __('ready_dictionaries.filters.part_of_speech') }}</label>
                    <select id="ready-part-of-speech" name="part_of_speech" class="dictionaries-input">
                        <option value="">{{ __('ready_dictionaries.filters.all_parts_of_speech') }}</option>
                        @foreach ($filterOptions['parts_of_speech'] as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFilters['part_of_speech'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dictionaries-create-actions">
                    <button type="submit" class="btn btn-primary dictionaries-action-btn">
                        {{ __('ready_dictionaries.filters.apply') }}
                    </button>
                    <a href="{{ route('ready-dictionaries.index') }}" class="btn btn-secondary dictionaries-action-btn">
                        {{ __('ready_dictionaries.filters.reset') }}
                    </a>
                </div>
            </form>
        </section>

        <section class="dictionaries-container dictionaries-list" aria-label="{{ __('ready_dictionaries.list_aria') }}">
            @forelse ($readyDictionaries as $dictionary)
                @php
                    $meta = [
                        __('dictionaries.index.languages.' . strtolower($dictionary->language)),
                        trans_choice('dictionaries.index.words_count', $dictionary->words_count ?? 0, ['count' => $dictionary->words_count ?? 0]),
                        __('dictionaries.index.meta.created', ['date' => $dictionary->created_at?->translatedFormat('Y-m-d')]),
                    ];

                    if ($dictionary->level !== null && \App\Support\LanguageLevelCatalog::label($dictionary->level) !== null) {
                        $meta[] = \App\Support\LanguageLevelCatalog::label($dictionary->level);
                    }

                    if ($dictionary->part_of_speech !== null && \App\Support\PartOfSpeechCatalog::label($dictionary->part_of_speech) !== null) {
                        $meta[] = \App\Support\PartOfSpeechCatalog::label($dictionary->part_of_speech);
                    }
                @endphp

                <article class="dictionary-card dictionary-card--clickable">
                    <a
                        href="{{ route('ready-dictionaries.show', $dictionary) }}"
                        class="dictionary-card__overlay-link"
                        aria-label="{{ __('ready_dictionaries.card.open_aria', ['name' => $dictionary->name]) }}"
                    ></a>

                    <div class="dictionary-card__content">
                        <h2 class="dictionary-card__title">
                            <a href="{{ route('ready-dictionaries.show', $dictionary) }}">{{ $dictionary->name }}</a>
                        </h2>
                        <p class="dictionary-card__meta">
                            @foreach ($meta as $metaItem)
                                @if (! $loop->first)
                                    <span class="dictionary-card__dot">&middot;</span>
                                @endif
                                {{ $metaItem }}
                            @endforeach
                        </p>

                        @if ($dictionary->comment !== null && trim($dictionary->comment) !== '')
                            <p class="dictionary-card__meta dictionary-card__description">{{ $dictionary->comment }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <article class="dictionary-card dictionary-card--empty">
                    <h2 class="dictionary-card__title">{{ __('ready_dictionaries.empty.title') }}</h2>
                    <p class="dictionary-card__meta">{{ __('ready_dictionaries.empty.text') }}</p>
                </article>
            @endforelse
        </section>
    </main>
@endcomponent
