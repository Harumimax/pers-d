@extends('layouts.profile', ['activeNav' => 'remainder'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/remainder.css') }}">
@endpush

@section('content')
    <main class="remainder-main">
        <div class="container remainder-container">
            <section class="remainder-hero">
                <div class="remainder-copy">
                    <p class="remainder-eyebrow">Word practice</p>
                    <h1 class="remainder-title">Remainder</h1>
                    <p class="remainder-description">is a game for reminding words.</p>
                </div>
            </section>

            <section
                class="remainder-setup-card"
                x-data="{
                    defaultGameType: 'manual',
                    defaultDirection: 'foreign_to_russian',
                    defaultWordsCount: '10',
                    gameType: 'manual',
                    direction: 'foreign_to_russian',
                    selectedDictionaries: [],
                    selectedPartsOfSpeech: ['all'],
                    wordsCount: '10',
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
                <header class="remainder-setup-card__header">
                    <div>
                        <p class="remainder-setup-card__eyebrow">Remainder setup</p>
                        <h2 class="remainder-setup-card__title">Configure your next repetition session</h2>
                    </div>
                    <p class="remainder-setup-card__subtitle">
                        Choose how you want to practice, which dictionaries to include, and how focused the round should be.
                    </p>
                </header>

                <div class="remainder-setup-grid">
                    <section class="remainder-section" aria-labelledby="remainder-game-type-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-game-type-title" class="remainder-section__title">Game type</h3>
                            <p class="remainder-section__description">Pick the answer format that feels right for this practice round.</p>
                        </div>

                        <div class="remainder-option-grid remainder-option-grid--two">
                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': gameType === 'manual' }"
                                @click="gameType = 'manual'"
                            >
                                <span class="remainder-option-card__title">Manual translation input</span>
                                <span class="remainder-option-card__meta">Type the translation yourself and check your recall.</span>
                            </button>

                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': gameType === 'choice' }"
                                @click="gameType = 'choice'"
                            >
                                <span class="remainder-option-card__title">Choose from 6 options</span>
                                <span class="remainder-option-card__meta">Use multiple choice when you want a faster session.</span>
                            </button>
                        </div>
                    </section>

                    <section class="remainder-section" aria-labelledby="remainder-direction-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-direction-title" class="remainder-section__title">Translation direction</h3>
                            <p class="remainder-section__description">Decide which side of the vocabulary pair should appear first.</p>
                        </div>

                        <div class="remainder-option-grid remainder-option-grid--two">
                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': direction === 'foreign_to_russian' }"
                                @click="direction = 'foreign_to_russian'"
                            >
                                <span class="remainder-option-card__title">Foreign language to Russian</span>
                                <span class="remainder-option-card__meta">See the original word first and recall the Russian meaning.</span>
                            </button>

                            <button
                                type="button"
                                class="remainder-option-card"
                                :class="{ 'remainder-option-card--active': direction === 'russian_to_foreign' }"
                                @click="direction = 'russian_to_foreign'"
                            >
                                <span class="remainder-option-card__title">Russian to foreign language</span>
                                <span class="remainder-option-card__meta">Flip the direction and reproduce the foreign word yourself.</span>
                            </button>
                        </div>
                    </section>
                </div>

                <section class="remainder-section" aria-labelledby="remainder-dictionaries-title">
                    <div class="remainder-section__header">
                        <h3 id="remainder-dictionaries-title" class="remainder-section__title">Dictionaries</h3>
                        <p class="remainder-section__description">Select one or several dictionaries to combine into the same session.</p>
                    </div>

                    @if ($remainderDictionaries->isNotEmpty())
                        <div class="remainder-dictionary-list" role="list" aria-label="Available dictionaries">
                            @foreach ($remainderDictionaries as $dictionary)
                                <button
                                    type="button"
                                    class="remainder-dictionary-item"
                                    :class="{ 'remainder-dictionary-item--active': isDictionarySelected({{ $dictionary->id }}) }"
                                    @click="toggleDictionary({{ $dictionary->id }})"
                                >
                                    <span class="remainder-dictionary-item__main">
                                        <span class="remainder-dictionary-item__name">{{ $dictionary->name }}</span>
                                        <span class="remainder-dictionary-item__meta">
                                            {{ $dictionary->language }}
                                            <span aria-hidden="true">&middot;</span>
                                            {{ $dictionary->words_count }} {{ \Illuminate\Support\Str::plural('word', $dictionary->words_count) }}
                                        </span>
                                    </span>
                                    <span class="remainder-dictionary-item__status" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="remainder-empty-state">
                            <p class="remainder-empty-state__title">No dictionaries yet.</p>
                            <p class="remainder-empty-state__text">Create a dictionary first, then come back here to configure a repetition session.</p>
                        </div>
                    @endif
                </section>

                <div class="remainder-setup-grid">
                    <section class="remainder-section" aria-labelledby="remainder-parts-title">
                        <div class="remainder-section__header">
                            <h3 id="remainder-parts-title" class="remainder-section__title remainder-section__title--with-top-offset">Parts of speech</h3>
                            <p class="remainder-section__description">Focus the round on the categories you want to revisit.</p>
                        </div>

                        <div class="remainder-chip-list">
                            @foreach ([
                                'all' => 'All',
                                'noun' => 'Noun',
                                'verb' => 'Verb',
                                'adjective' => 'Adjective',
                                'adverb' => 'Adverb',
                                'pronoun' => 'Pronoun',
                                'preposition' => 'Preposition',
                                'conjunction' => 'Conjunction',
                                'interjection' => 'Interjection',
                                'stable_expression' => 'Stable expression',
                            ] as $value => $label)
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
                            <h3 id="remainder-count-title" class="remainder-section__title">Words count</h3>
                            <p class="remainder-section__description">Set the size of the training set for one reminder session. Up to 20 words.</p>
                        </div>

                        <div class="remainder-count-field">
                            <label for="remainder-words-count" class="remainder-count-label">Words count</label>
                            <input
                                id="remainder-words-count"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="2"
                                class="remainder-count-input"
                                x-model="wordsCount"
                                @input="sanitizeWordsCount($event.target.value)"
                                placeholder="10"
                                aria-describedby="remainder-words-count-hint"
                            >
                            <p id="remainder-words-count-hint" class="remainder-count-hint">Only digits are allowed. Maximum value: 20.</p>
                        </div>
                    </section>
                </div>

                <footer class="remainder-actions">
                    <button type="button" class="btn btn-primary remainder-actions__start">Start</button>
                    <button type="button" class="btn btn-secondary remainder-actions__reset" @click="resetSettings()">Reset</button>
                </footer>
            </section>
        </div>
    </main>
@endsection
