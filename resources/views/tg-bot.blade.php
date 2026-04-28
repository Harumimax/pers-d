@extends('layouts.profile', ['activeNav' => 'tg-bot'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dictionaries.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tg-bot.css') }}">
@endpush

@section('content')
    @php
        $initialTelegramSettings = [
            'timezone' => old('timezone', $telegramSettingsFormData['timezone']),
            'random_words_enabled' => filter_var(old('random_words_enabled', $telegramSettingsFormData['random_words_enabled']), FILTER_VALIDATE_BOOL),
            'sessions' => collect(old('sessions', $telegramSettingsFormData['sessions']))
                ->map(function ($session) {
                    $normalized = is_array($session) ? $session : [];

                    return [
                        'send_time' => (string) ($normalized['send_time'] ?? '09:00'),
                        'translation_direction' => (string) ($normalized['translation_direction'] ?? \App\Models\GameSession::DIRECTION_FOREIGN_TO_RU),
                        'part_of_speech' => collect($normalized['part_of_speech'] ?? [\App\Support\PartOfSpeechCatalog::ALL])
                            ->map(fn ($value) => (string) $value)
                            ->filter()
                            ->values()
                            ->all(),
                        'user_dictionary_ids' => collect($normalized['user_dictionary_ids'] ?? [])
                            ->map(fn ($value) => (int) $value)
                            ->filter()
                            ->values()
                            ->all(),
                        'ready_dictionary_ids' => collect($normalized['ready_dictionary_ids'] ?? [])
                            ->map(fn ($value) => (int) $value)
                            ->filter()
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    @endphp

    <main class="tg-bot-main">
        <div class="dictionaries-container tg-bot-container">
            <section class="dictionaries-intro tg-bot-intro">
                <div class="dictionaries-intro__copy">
                    <h1 class="dictionaries-title tg-bot-page-title">{{ __('tg-bot.title') }}</h1>
                    <p class="dictionaries-subtitle">{{ __('tg-bot.subtitle') }}</p>
                </div>
            </section>

            <section class="dictionaries-list tg-bot-stack" aria-label="{{ __('tg-bot.title') }}">
                <article class="dictionary-card tg-bot-card">
                    <div class="dictionary-card__content tg-bot-status-card">
                        <div>
                            <h2 class="dictionary-card__title">{{ __('tg-bot.connection.title') }}</h2>
                            <p class="dictionary-card__meta tg-bot-card__description">
                                {{ __('tg-bot.bot_address_text') }}
                                <a href="{{ $telegramBotUrl }}" class="tg-bot-link" target="_blank" rel="noopener noreferrer">{{ '@' . $telegramBotUsername }}</a>
                            </p>
                        </div>

                        <div class="tg-bot-connection-state">
                            <span class="tg-bot-badge {{ $telegramConnected ? 'tg-bot-badge--success' : 'tg-bot-badge--muted' }}">
                                {{ $telegramConnected ? __('tg-bot.connection.connected') : __('tg-bot.connection.not_connected') }}
                            </span>
                        </div>
                    </div>
                </article>

                @if (! $telegramConnected)
                    <article class="dictionary-card tg-bot-card tg-bot-card--notice">
                        <div class="dictionary-card__content">
                            <h2 class="dictionary-card__title">{{ __('tg-bot.connection.required_title') }}</h2>
                            <p class="dictionary-card__meta tg-bot-card__description">{{ __('tg-bot.connection.required_to_configure') }}</p>
                        </div>
                    </article>
                @else
                    <article class="dictionary-card tg-bot-card">
                        <div class="dictionary-card__content" x-data="{ settingsExpanded: true }">
                            @if (session('tgBotSettingsStatus'))
                                <div class="tg-bot-alert tg-bot-alert--success" role="status">
                                    {{ session('tgBotSettingsStatus') }}
                                </div>
                            @endif

                            @if (session('tgBotSettingsError'))
                                <div class="tg-bot-alert tg-bot-alert--error" role="alert">
                                    {{ session('tgBotSettingsError') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="tg-bot-alert tg-bot-alert--error" role="alert">
                                    <p class="tg-bot-alert__title">{{ __('tg-bot.form.errors_title') }}</p>
                                    <ul class="tg-bot-alert__list">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="tg-bot-card__header">
                                <div>
                                    <h2 class="dictionary-card__title">{{ __('tg-bot.form.random_words.title') }}</h2>
                                    <p class="dictionary-card__meta tg-bot-card__description">{{ __('tg-bot.form.random_words.description') }}</p>
                                </div>

                                <button
                                    type="button"
                                    class="tg-bot-card__toggle"
                                    @click="settingsExpanded = ! settingsExpanded"
                                    :aria-expanded="settingsExpanded.toString()"
                                >
                                    <span class="sr-only">Toggle section</span>
                                    <svg
                                        class="tg-bot-card__toggle-icon"
                                        :class="{ 'tg-bot-card__toggle-icon--expanded': settingsExpanded }"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        aria-hidden="true"
                                    >
                                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>

                            <div x-show="settingsExpanded" x-cloak>
                                <form
                                    method="POST"
                                    action="{{ route('tg-bot.update') }}"
                                    class="tg-bot-form"
                                    x-data="{
                                        partOfSpeechAllValue: @js(\App\Support\PartOfSpeechCatalog::ALL),
                                        maxSessions: 5,
                                        availableUserDictionaryIds: @js($userDictionaries->pluck('id')->map(fn ($id) => (int) $id)->values()->all()),
                                        availableReadyDictionaryIds: @js($readyDictionaries->pluck('id')->map(fn ($id) => (int) $id)->values()->all()),
                                        sessions: @js($initialTelegramSettings['sessions']),
                                        createSession() {
                                            return {
                                                send_time: '09:00',
                                                translation_direction: 'foreign_to_ru',
                                                part_of_speech: [this.partOfSpeechAllValue],
                                                user_dictionary_ids: [],
                                                ready_dictionary_ids: [],
                                            };
                                        },
                                        addSession() {
                                            if (this.sessions.length >= this.maxSessions) {
                                                return;
                                            }

                                            this.sessions.push(this.createSession());
                                        },
                                        removeSession(index) {
                                            if (index === 0 || this.sessions.length <= 1) {
                                                return;
                                            }

                                            this.sessions.splice(index, 1);
                                        },
                                        togglePartOfSpeech(session, value) {
                                            if (value === this.partOfSpeechAllValue) {
                                                session.part_of_speech = [this.partOfSpeechAllValue];
                                                return;
                                            }

                                            const nextValues = session.part_of_speech.filter(item => item !== this.partOfSpeechAllValue);

                                            if (nextValues.includes(value)) {
                                                const filtered = nextValues.filter(item => item !== value);
                                                session.part_of_speech = filtered.length ? filtered : [this.partOfSpeechAllValue];
                                                return;
                                            }

                                            session.part_of_speech = [...nextValues, value];
                                        },
                                        hasPartOfSpeech(session, value) {
                                            return session.part_of_speech.includes(value);
                                        },
                                        toggleSelection(session, key, id) {
                                            if (session[key].includes(id)) {
                                                session[key] = session[key].filter(value => value !== id);
                                                return;
                                            }

                                            session[key] = [...session[key], id];
                                        },
                                        hasSelection(session, key, id) {
                                            return session[key].includes(id);
                                        },
                                        areAllSelected(session, key, availableIds) {
                                            return availableIds.length > 0 && availableIds.every(id => session[key].includes(id));
                                        },
                                        toggleAllSelections(session, key, availableIds) {
                                            if (this.areAllSelected(session, key, availableIds)) {
                                                session[key] = session[key].filter(id => !availableIds.includes(id));
                                                return;
                                            }

                                            session[key] = [...new Set([...session[key], ...availableIds])];
                                        },
                                        sessionTitle(index) {
                                            return index === 0
                                                ? @js(__('tg-bot.form.sessions.first_title'))
                                                : @js(__('tg-bot.form.sessions.additional_title_prefix')) + ' ' + (index + 1);
                                        }
                                    }"
                                >
                                    @csrf
                                    @method('PUT')

                                    <section class="tg-bot-form__section">
                                        <div class="tg-bot-form__field">
                                            <label for="tg-bot-timezone" class="tg-bot-form__label">{{ __('tg-bot.form.timezone.label') }}</label>
                                            <select id="tg-bot-timezone" name="timezone" class="tg-bot-form__control">
                                                @foreach ($timezoneOptions as $timezone)
                                                    <option value="{{ $timezone['value'] }}" @selected($initialTelegramSettings['timezone'] === $timezone['value'])>
                                                        {{ $timezone['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="tg-bot-form__hint">{{ __('tg-bot.form.timezone.hint') }}</p>
                                        </div>

                                        <div class="tg-bot-form__switch-row">
                                            <div>
                                                <h3 class="tg-bot-form__switch-title">{{ __('tg-bot.form.random_words.enabled') }}</h3>
                                                <p class="tg-bot-form__hint">{{ __('tg-bot.form.random_words.choice_hint') }}</p>
                                            </div>

                                            <label class="tg-bot-switch">
                                                <input type="hidden" name="random_words_enabled" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="random_words_enabled"
                                                    value="1"
                                                    @checked($initialTelegramSettings['random_words_enabled'])
                                                >
                                                <span class="tg-bot-switch__track" aria-hidden="true"></span>
                                            </label>
                                        </div>
                                    </section>

                                    <section class="tg-bot-form__section">
                                        <div class="tg-bot-form__sessions-header">
                                            <div>
                                                <h3 class="tg-bot-form__section-title">{{ __('tg-bot.form.sessions.title') }}</h3>
                                                <p class="tg-bot-form__hint">{{ __('tg-bot.form.sessions.hint') }}</p>
                                            </div>

                                            <button
                                                type="button"
                                                class="tg-bot-session-add"
                                                @click="addSession()"
                                                :disabled="sessions.length >= maxSessions"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <div class="tg-bot-session-list">
                                            <template x-for="(session, index) in sessions" :key="index">
                                                <section class="tg-bot-session-card">
                                                    <div class="tg-bot-session-card__header">
                                                        <div>
                                                            <h4 class="tg-bot-session-card__title" x-text="sessionTitle(index)"></h4>
                                                            <p class="tg-bot-form__hint">{{ __('tg-bot.form.sessions.session_hint') }}</p>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            class="tg-bot-session-remove"
                                                            @click="removeSession(index)"
                                                            x-show="index > 0"
                                                            x-cloak
                                                        >
                                                            -
                                                        </button>
                                                    </div>

                                                    <div class="tg-bot-session-card__grid">
                                                        <div class="tg-bot-form__field">
                                                            <label class="tg-bot-form__label" :for="`session-time-${index}`">{{ __('tg-bot.form.sessions.fields.send_time') }}</label>
                                                            <input
                                                                :id="`session-time-${index}`"
                                                                :name="`sessions[${index}][send_time]`"
                                                                type="time"
                                                                class="tg-bot-form__control"
                                                                x-model="session.send_time"
                                                            >
                                                        </div>

                                                        <div class="tg-bot-form__field">
                                                            <label class="tg-bot-form__label" :for="`session-direction-${index}`">{{ __('tg-bot.form.sessions.fields.translation_direction') }}</label>
                                                            <select
                                                                :id="`session-direction-${index}`"
                                                                :name="`sessions[${index}][translation_direction]`"
                                                                class="tg-bot-form__control"
                                                                x-model="session.translation_direction"
                                                            >
                                                                @foreach ($directionOptions as $value => $label)
                                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="tg-bot-form__field">
                                                        <span class="tg-bot-form__label">{{ __('tg-bot.form.sessions.fields.part_of_speech') }}</span>
                                                        <div class="tg-bot-chip-list">
                                                            @foreach ($partOfSpeechOptions as $value => $label)
                                                                <label
                                                                    class="tg-bot-chip"
                                                                    :class="{ 'tg-bot-chip--active': hasPartOfSpeech(session, '{{ $value }}') }"
                                                                >
                                                                    <input
                                                                        type="checkbox"
                                                                        class="sr-only"
                                                                        :name="`sessions[${index}][part_of_speech][]`"
                                                                        value="{{ $value }}"
                                                                        :checked="hasPartOfSpeech(session, '{{ $value }}')"
                                                                        @change="togglePartOfSpeech(session, '{{ $value }}')"
                                                                    >
                                                                    <span>{{ $label }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <div class="tg-bot-dictionary-columns">
                                                        <div class="tg-bot-dictionary-column">
                                                            <span class="tg-bot-form__label">{{ __('tg-bot.form.sessions.fields.user_dictionaries') }}</span>

                                                            @if ($userDictionaries->isNotEmpty())
                                                                <label class="tg-bot-select-all">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="tg-bot-select-all__input"
                                                                        :checked="areAllSelected(session, 'user_dictionary_ids', availableUserDictionaryIds)"
                                                                        @change="toggleAllSelections(session, 'user_dictionary_ids', availableUserDictionaryIds)"
                                                                    >
                                                                    <span>{{ __('remainder.settings.dictionaries.select_all') }}</span>
                                                                </label>

                                                                <div class="tg-bot-select-list">
                                                                    @foreach ($userDictionaries as $dictionary)
                                                                        @php
                                                                            $dictionaryLanguageKey = $dictionary->language !== null
                                                                                ? 'dictionaries.index.languages.' . strtolower($dictionary->language)
                                                                                : 'dictionaries.index.languages.not_specified';
                                                                        @endphp
                                                                        <label
                                                                            class="tg-bot-select-card"
                                                                            :class="{ 'tg-bot-select-card--active': hasSelection(session, 'user_dictionary_ids', {{ $dictionary->id }}) }"
                                                                        >
                                                                            <input
                                                                                type="checkbox"
                                                                                class="sr-only"
                                                                                :name="`sessions[${index}][user_dictionary_ids][]`"
                                                                                value="{{ $dictionary->id }}"
                                                                                :checked="hasSelection(session, 'user_dictionary_ids', {{ $dictionary->id }})"
                                                                                @change="toggleSelection(session, 'user_dictionary_ids', {{ $dictionary->id }})"
                                                                            >
                                                                            <span class="tg-bot-select-card__name">{{ $dictionary->name }}</span>
                                                                            <span class="tg-bot-select-card__meta">
                                                                                {{ __($dictionaryLanguageKey) }}
                                                                                <span aria-hidden="true">&middot;</span>
                                                                                {{ trans_choice('remainder.settings.dictionaries.words_count', $dictionary->words_count, ['count' => $dictionary->words_count]) }}
                                                                            </span>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="tg-bot-form__hint">{{ __('tg-bot.form.sessions.empty_user_dictionaries') }}</p>
                                                            @endif
                                                        </div>

                                                        <div class="tg-bot-dictionary-column">
                                                            <span class="tg-bot-form__label">{{ __('tg-bot.form.sessions.fields.ready_dictionaries') }}</span>

                                                            @if ($readyDictionaries->isNotEmpty())
                                                                <label class="tg-bot-select-all">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="tg-bot-select-all__input"
                                                                        :checked="areAllSelected(session, 'ready_dictionary_ids', availableReadyDictionaryIds)"
                                                                        @change="toggleAllSelections(session, 'ready_dictionary_ids', availableReadyDictionaryIds)"
                                                                    >
                                                                    <span>{{ __('remainder.settings.dictionaries.select_all') }}</span>
                                                                </label>

                                                                <div class="tg-bot-select-list">
                                                                    @foreach ($readyDictionaries as $dictionary)
                                                                        @php
                                                                            $dictionaryLanguageKey = $dictionary->language !== null
                                                                                ? 'dictionaries.index.languages.' . strtolower($dictionary->language)
                                                                                : 'dictionaries.index.languages.not_specified';
                                                                        @endphp
                                                                        <label
                                                                            class="tg-bot-select-card"
                                                                            :class="{ 'tg-bot-select-card--active': hasSelection(session, 'ready_dictionary_ids', {{ $dictionary->id }}) }"
                                                                        >
                                                                            <input
                                                                                type="checkbox"
                                                                                class="sr-only"
                                                                                :name="`sessions[${index}][ready_dictionary_ids][]`"
                                                                                value="{{ $dictionary->id }}"
                                                                                :checked="hasSelection(session, 'ready_dictionary_ids', {{ $dictionary->id }})"
                                                                                @change="toggleSelection(session, 'ready_dictionary_ids', {{ $dictionary->id }})"
                                                                            >
                                                                            <span class="tg-bot-select-card__name">{{ $dictionary->name }}</span>
                                                                            <span class="tg-bot-select-card__meta">
                                                                                {{ __($dictionaryLanguageKey) }}
                                                                                <span aria-hidden="true">&middot;</span>
                                                                                {{ trans_choice('remainder.settings.dictionaries.words_count', $dictionary->words_count, ['count' => $dictionary->words_count]) }}
                                                                            </span>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="tg-bot-form__hint">{{ __('tg-bot.form.sessions.empty_ready_dictionaries') }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>
                                        </template>
                                    </div>
                                    </section>

                                    <div class="tg-bot-form__actions">
                                        <button type="submit" class="btn btn-primary tg-bot-form__submit">
                                            {{ __('tg-bot.form.save') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </article>
                @endif
            </section>
        </div>
    </main>
@endsection
