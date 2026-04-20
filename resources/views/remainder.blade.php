@extends('layouts.profile', ['activeNav' => 'remainder'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/remainder.css') }}">
@endpush

@section('content')
    @php
        $partOfSpeechLabels = \App\Support\PartOfSpeechCatalog::labelsWithAll();
        $initialDictionaryIds = collect(old('dictionary_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $initialPartsOfSpeech = collect(old('parts_of_speech', ['all']))
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->values();
        $initialPartsOfSpeech = $initialPartsOfSpeech->isEmpty() ? collect(['all']) : $initialPartsOfSpeech;
    @endphp

    <main class="remainder-main">
        <div class="container remainder-container">
            <section class="remainder-hero">
                <div class="remainder-copy">
                    <div class="remainder-heading-line">
                        <h1 class="remainder-title">{{ __('remainder.settings.title') }}</h1>
                        <p class="remainder-description">{{ __('remainder.settings.description') }}</p>
                    </div>
                </div>
            </section>

            <form
                method="POST"
                action="{{ route('remainder.sessions.store') }}"
                class="remainder-setup-card"
                x-data="{
                    defaultGameType: 'manual',
                    defaultDirection: 'foreign_to_ru',
                    defaultWordsCount: '10',
                    gameType: @js(old('mode', 'manual')),
                    direction: @js(old('direction', 'foreign_to_ru')),
                    selectedDictionaries: @js($initialDictionaryIds->all()),
                    selectedPartsOfSpeech: @js($initialPartsOfSpeech->all()),
                    wordsCount: @js((string) old('words_count', '10')),
                    toggleDictionary(id) {
                        if (this.selectedDictionaries.includes(id)) {
                            this.selectedDictionaries = this.selectedDictionaries.filter(dictionaryId => dictionaryId !== id);
                            return;
                        }

                        this.selectedDictionaries = [...this.selectedDictionaries, id];
                    },
                    isDictionarySelected(id) {
                        return this.selectedDictionaries.includes(id);
                    },
                    togglePartOfSpeech(value) {
                        if (value === 'all') {
                            this.selectedPartsOfSpeech = ['all'];
                            return;
                        }

                        const nextValues = this.selectedPartsOfSpeech.filter(item => item !== 'all');

                        if (nextValues.includes(value)) {
                            const filteredValues = nextValues.filter(item => item !== value);
                            this.selectedPartsOfSpeech = filteredValues.length ? filteredValues : ['all'];
                            return;
                        }

                        this.selectedPartsOfSpeech = [...nextValues, value];
                    },
                    isPartOfSpeechSelected(value) {
                        return this.selectedPartsOfSpeech.includes(value);
                    },
                    sanitizeWordsCount(value) {
                        const digitsOnly = value.replace(/\D/g, '').slice(0, 2);

                        if (digitsOnly === '') {
                            this.wordsCount = '';
                            return;
                        }

                        this.wordsCount = String(Math.min(20, Number(digitsOnly)));
                    },
                    resetSettings() {
                        this.gameType = this.defaultGameType;
                        this.direction = this.defaultDirection;
                        this.selectedDictionaries = [];
                        this.selectedPartsOfSpeech = ['all'];
                        this.wordsCount = this.defaultWordsCount;
                    }
                }"
            >
                @csrf

                <input type="hidden" name="mode" :value="gameType">
                <input type="hidden" name="direction" :value="direction">

                <template x-for="dictionaryId in selectedDictionaries" :key="`dictionary-${dictionaryId}`">
                    <input type="hidden" name="dictionary_ids[]" :value="dictionaryId">
                </template>

                <template x-for="part in selectedPartsOfSpeech" :key="`part-${part}`">
                    <input type="hidden" name="parts_of_speech[]" :value="part">
                </template>

                <header class="remainder-setup-card__header">
                    <div>
                        <p class="remainder-setup-card__eyebrow">{{ __('remainder.settings.setup_eyebrow') }}</p>
                        <h2 class="remainder-setup-card__title">{{ __('remainder.settings.setup_title') }}</h2>
                    </div>
                    <p class="remainder-setup-card__subtitle">
                        {{ __('remainder.settings.setup_subtitle') }}
                    </p>
                </header>

                @if ($errors->any())
                    <div class="remainder-errors" role="alert">
                        <p class="remainder-errors__title">{{ __('remainder.settings.errors_title') }}</p>
                        <ul class="remainder-errors__list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="remainder-setup-grid">
                    <section class="remainder-section" aria-labelledby="remainder-game-type-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-game-type-title" class="remainder-section__title">{{ __('remainder.settings.game_type.title') }}</h3>
                            <p class="remainder-section__description">{{ __('remainder.settings.game_type.description') }}</p>
                        </div>

                        <div class="remainder-option-grid remainder-option-grid--two">
                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': gameType === 'manual' }"
                                @click="gameType = 'manual'"
                            >
                                <span class="remainder-option-card__title">{{ __('remainder.settings.game_type.manual_title') }}</span>
                                <span class="remainder-option-card__meta">{{ __('remainder.settings.game_type.manual_meta') }}</span>
                            </button>

                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': gameType === 'choice' }"
                                @click="gameType = 'choice'"
                            >
                                <span class="remainder-option-card__title">{{ __('remainder.settings.game_type.choice_title') }}</span>
                                <span class="remainder-option-card__meta">{{ __('remainder.settings.game_type.choice_meta') }}</span>
                            </button>
                        </div>

                    </section>

                    <section class="remainder-section" aria-labelledby="remainder-direction-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-direction-title" class="remainder-section__title">{{ __('remainder.settings.direction.title') }}</h3>
                            <p class="remainder-section__description">{{ __('remainder.settings.direction.description') }}</p>
                        </div>

                        <div class="remainder-option-grid remainder-option-grid--two">
                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': direction === 'foreign_to_ru' }"
                                @click="direction = 'foreign_to_ru'"
                            >
                                <span class="remainder-option-card__title">{{ __('remainder.settings.direction.foreign_to_ru_title') }}</span>
                                <span class="remainder-option-card__meta">{{ __('remainder.settings.direction.foreign_to_ru_meta') }}</span>
                            </button>

                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': direction === 'ru_to_foreign' }"
                                @click="direction = 'ru_to_foreign'"
                            >
                                <span class="remainder-option-card__title">{{ __('remainder.settings.direction.ru_to_foreign_title') }}</span>
                                <span class="remainder-option-card__meta">{{ __('remainder.settings.direction.ru_to_foreign_meta') }}</span>
                            </button>
                        </div>
                    </section>
                </div>

                <section class="remainder-section" aria-labelledby="remainder-dictionaries-title">
                    <div class="remainder-section__header">
                        <h3 id="remainder-dictionaries-title" class="remainder-section__title">{{ __('remainder.settings.dictionaries.title') }}</h3>
                        <p class="remainder-section__description">{{ __('remainder.settings.dictionaries.description') }}</p>
                    </div>

                    @if ($remainderDictionaries->isNotEmpty())
                        <div class="remainder-dictionary-list" role="list" aria-label="{{ __('remainder.settings.dictionaries.available_aria') }}">
                            @foreach ($remainderDictionaries as $dictionary)
                                @php
                                    $dictionaryLanguageKey = $dictionary->language !== null
                                        ? 'dictionaries.index.languages.' . strtolower($dictionary->language)
                                        : 'dictionaries.index.languages.not_specified';
                                @endphp
                                <button
                                    type="button"
                                    class="remainder-dictionary-item"
                                    :class="{ 'remainder-dictionary-item--active': isDictionarySelected({{ $dictionary->id }}) }"
                                    @click="toggleDictionary({{ $dictionary->id }})"
                                >
                                    <span class="remainder-dictionary-item__main">
                                        <span class="remainder-dictionary-item__name">{{ $dictionary->name }}</span>
                                        <span class="remainder-dictionary-item__meta">
                                            {{ __($dictionaryLanguageKey) }}
                                            <span aria-hidden="true">&middot;</span>
                                            {{ trans_choice('remainder.settings.dictionaries.words_count', $dictionary->words_count, ['count' => $dictionary->words_count]) }}
                                        </span>
                                    </span>
                                    <span class="remainder-dictionary-item__status" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="remainder-empty-state">
                            <p class="remainder-empty-state__title">{{ __('remainder.settings.dictionaries.empty_title') }}</p>
                            <p class="remainder-empty-state__text">{{ __('remainder.settings.dictionaries.empty_text') }}</p>
                        </div>
                    @endif
                </section>

                <div class="remainder-setup-grid remainder-setup-grid--secondary">
                    <section class="remainder-section" aria-labelledby="remainder-parts-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-parts-title" class="remainder-section__title">{{ __('remainder.settings.parts_of_speech.title') }}</h3>
                            <p class="remainder-section__description">{{ __('remainder.settings.parts_of_speech.description') }}</p>
                        </div>

                        <div class="remainder-chip-list">
                            @foreach ($partOfSpeechLabels as $value => $label)
                                <button
                                    type="button"
                                    class="remainder-chip"
                                    :class="{ 'remainder-chip--active': isPartOfSpeechSelected('{{ $value }}') }"
                                    @click="togglePartOfSpeech('{{ $value }}')"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <section class="remainder-section" aria-labelledby="remainder-count-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-count-title" class="remainder-section__title">{{ __('remainder.settings.words_count.title') }}</h3>
                            <p class="remainder-section__description">{{ __('remainder.settings.words_count.description') }}</p>
                        </div>

                        <div class="remainder-count-field">
                            <label for="remainder-words-count" class="remainder-count-label">{{ __('remainder.settings.words_count.label') }}</label>
                            <input
                                id="remainder-words-count"
                                name="words_count"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="2"
                                class="remainder-count-input"
                                x-model="wordsCount"
                                @input="sanitizeWordsCount($event.target.value)"
                                placeholder="{{ __('remainder.settings.words_count.placeholder') }}"
                                aria-describedby="remainder-words-count-hint"
                            >
                            <p id="remainder-words-count-hint" class="remainder-count-hint">{{ __('remainder.settings.words_count.hint') }}</p>
                        </div>
                    </section>
                </div>

                <footer class="remainder-actions">
                    <button type="submit" class="btn btn-primary remainder-actions__start">
                        {{ __('remainder.settings.actions.start') }}
                    </button>
                    <button type="button" class="btn btn-secondary remainder-actions__reset" @click="resetSettings()">{{ __('remainder.settings.actions.reset') }}</button>
                </footer>
            </form>
        </div>
    </main>
@endsection
