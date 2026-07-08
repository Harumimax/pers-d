@component('layouts.dictionaries-v2', [
    'headerDictionaries' => $headerDictionaries,
    'headerReadyDictionaries' => $headerReadyDictionaries,
    'additionalStyles' => [\App\Support\VersionedAsset::url('css/ready-dictionaries-v2.css')],
])
    <main class="dictionaries-main ready-page">
        <section class="dictionaries-container ready-hero">
            <div class="ready-hero__content">
                <p class="ready-hero__eyebrow">{{ __('common.links.ready_dictionaries') }}</p>

                <h1 class="ready-hero__title">{{ __('ready_dictionaries.title') }}</h1>

                <p class="ready-hero__text">{{ __('ready_dictionaries.description') }}</p>

                <div class="ready-hero__meta">
                    <span class="ready-hero__pill">Prepared vocabulary</span>
                    <span class="ready-hero__pill">Practice-ready</span>
                    <span class="ready-hero__pill">No setup needed</span>
                </div>
            </div>

            <div class="ready-hero__visual" aria-hidden="true">
                <div class="ready-preview-card ready-preview-card--main">
                    <span class="ready-preview-card__label">Today's set</span>
                    <strong>English C1 Verbs</strong>
                    <small>100 words, multiple choice, examples</small>
                </div>

                <div class="ready-preview-card ready-preview-card--floating">
                    <span>Start practice</span>
                    <strong>12 min</strong>
                </div>
            </div>
        </section>

        <section class="dictionaries-container ready-filter-panel" aria-label="{{ __('ready_dictionaries.filters.aria') }}">
            <div class="ready-filter-panel__header">
                <div>
                    <h2 class="ready-filter-panel__title">Find the right dictionary</h2>
                    <p class="ready-filter-panel__text">
                        Filter prepared dictionaries by language, level, and part of speech.
                    </p>
                </div>
            </div>

            <form method="GET" action="{{ route('ready-dictionaries-v2.index') }}" class="ready-filter-form">
                <div class="ready-field">
                    <label for="ready-language" class="ready-label">{{ __('ready_dictionaries.filters.language') }}</label>
                    <select id="ready-language" name="language" class="ready-input">
                        <option value="">{{ __('ready_dictionaries.filters.all_languages') }}</option>
                        @foreach ($filterOptions['languages'] as $language)
                            <option value="{{ $language }}" @selected($selectedFilters['language'] === $language)>
                                {{ __('dictionaries.index.languages.' . strtolower($language)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ready-field">
                    <label for="ready-level" class="ready-label">{{ __('ready_dictionaries.filters.level') }}</label>
                    <select id="ready-level" name="level" class="ready-input">
                        <option value="">{{ __('ready_dictionaries.filters.all_levels') }}</option>
                        @foreach ($filterOptions['levels'] as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFilters['level'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ready-field">
                    <label for="ready-part-of-speech" class="ready-label">{{ __('ready_dictionaries.filters.part_of_speech') }}</label>
                    <select id="ready-part-of-speech" name="part_of_speech" class="ready-input">
                        <option value="">{{ __('ready_dictionaries.filters.all_parts_of_speech') }}</option>
                        @foreach ($filterOptions['parts_of_speech'] as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFilters['part_of_speech'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ready-filter-form__actions">
                    <button type="submit" class="btn btn-primary ready-action-btn">
                        {{ __('ready_dictionaries.filters.apply') }}
                    </button>

                    <a href="{{ route('ready-dictionaries-v2.index') }}" class="btn btn-secondary ready-action-btn">
                        {{ __('ready_dictionaries.filters.reset') }}
                    </a>
                </div>
            </form>
        </section>

        <section class="dictionaries-container ready-list-section" aria-label="{{ __('ready_dictionaries.list_aria') }}">
            <div class="ready-list-section__header">
                <div>
                    <p class="ready-list-section__eyebrow">Library</p>
                    <h2 class="ready-list-section__title">Prepared dictionaries</h2>
                </div>
            </div>

            <div class="ready-dictionary-grid">
                @forelse ($readyDictionaries as $dictionary)
                    @php
                        $languageLabel = __('dictionaries.index.languages.' . strtolower($dictionary->language));
                        $wordsCount = trans_choice('dictionaries.index.words_count', $dictionary->words_count ?? 0, ['count' => $dictionary->words_count ?? 0]);
                        $createdDate = __('dictionaries.index.meta.created', ['date' => $dictionary->created_at?->translatedFormat('Y-m-d')]);
                        $languageChip = strtoupper(substr((string) $dictionary->language, 0, 2));

                        $levelLabel = null;
                        if ($dictionary->level !== null && \App\Support\LanguageLevelCatalog::label($dictionary->level) !== null) {
                            $levelLabel = \App\Support\LanguageLevelCatalog::label($dictionary->level);
                        }

                        $partOfSpeechLabel = null;
                        if ($dictionary->part_of_speech !== null && \App\Support\PartOfSpeechCatalog::label($dictionary->part_of_speech) !== null) {
                            $partOfSpeechLabel = \App\Support\PartOfSpeechCatalog::label($dictionary->part_of_speech);
                        }
                    @endphp

                    <article class="ready-dictionary-card">
                        <a
                            href="{{ route('ready-dictionaries.show-v2', $dictionary) }}"
                            class="ready-dictionary-card__link"
                            aria-label="{{ __('ready_dictionaries.card.open_aria', ['name' => $dictionary->name]) }}"
                        ></a>

                        <div class="ready-dictionary-card__top">
                            <div class="ready-dictionary-card__icon" aria-hidden="true">
                                {{ $languageChip }}
                            </div>

                            <div class="ready-dictionary-card__badges">
                                <span class="ready-badge">{{ $languageLabel }}</span>

                                @if ($levelLabel !== null)
                                    <span class="ready-badge ready-badge--accent">{{ $levelLabel }}</span>
                                @endif

                                @if ($partOfSpeechLabel !== null)
                                    <span class="ready-badge ready-badge--soft">{{ $partOfSpeechLabel }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="ready-dictionary-card__body">
                            <h3 class="ready-dictionary-card__title">{{ $dictionary->name }}</h3>

                            @if ($dictionary->comment !== null && trim($dictionary->comment) !== '')
                                <p class="ready-dictionary-card__description">{{ $dictionary->comment }}</p>
                            @else
                                <p class="ready-dictionary-card__description">
                                    A prepared vocabulary set for fast practice and review.
                                </p>
                            @endif
                        </div>

                        <div class="ready-dictionary-card__footer">
                            <span>{{ $wordsCount }}</span>
                            <span>{{ $createdDate }}</span>
                        </div>

                        <div class="ready-dictionary-card__cta">
                            <span>Open dictionary</span>
                            <span aria-hidden="true">&rarr;</span>
                        </div>
                    </article>
                @empty
                    <article class="ready-empty-card">
                        <h2 class="ready-empty-card__title">{{ __('ready_dictionaries.empty.title') }}</h2>
                        <p class="ready-empty-card__text">{{ __('ready_dictionaries.empty.text') }}</p>
                    </article>
                @endforelse
            </div>
        </section>
    </main>
@endcomponent
