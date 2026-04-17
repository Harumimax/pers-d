@extends('layouts.profile', ['activeNav' => 'about'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush

@section('content')
    @php
        $contactOpenDefault = $errors->any()
            || session()->has('aboutContactError');
    @endphp

    <main class="about-main" x-data="{ contactOpen: @js($contactOpenDefault), generalStatisticsOpen: false, functionalityOpen: false, privacyPolicyOpen: false, cookiePolicyOpen: false }">
        <div class="container about-container">
            <section class="about-hero">
                <div class="about-hero__image-wrap">
                    <img
                        src="{{ asset('images/about-study-desk.jpg') }}"
                        alt="{{ __('about.hero.image_alt') }}"
                        class="about-hero__image"
                    >
                </div>

                <div class="about-hero__content">
                    <p class="about-hero__eyebrow">{{ __('about.hero.eyebrow') }}</p>
                    <h1 class="about-hero__title">{{ __('about.hero.title') }}</h1>
                    <p class="about-hero__description">
                        {{ __('about.hero.description_1') }}
                    </p>
                    <p class="about-hero__description">
                        {{ __('about.hero.description_2') }}
                    </p>
                </div>
            </section>

            <section class="about-status-card" aria-label="General statistics">
                <header class="about-status-card__header">
                    <button
                        type="button"
                        class="about-section-toggle"
                        @click="generalStatisticsOpen = !generalStatisticsOpen"
                        :aria-expanded="generalStatisticsOpen.toString()"
                        aria-controls="about-general-statistics-panel"
                    >
                        <span class="about-status-card__title">{{ __('about.general_statistics.title') }}</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': generalStatisticsOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                <div id="about-general-statistics-panel" x-show="generalStatisticsOpen" x-cloak>
                    <p class="about-status-card__subtitle">{{ __('about.general_statistics.subtitle') }}</p>

                    <div class="about-status-table-wrap">
                        <table class="about-status-table">
                            <tbody>
                                <tr>
                                    <th scope="row">{{ __('about.general_statistics.rows.dictionaries_count') }}</th>
                                    <td>{{ $globalStatistics['dictionaries_count'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('about.general_statistics.rows.word_entries_count') }}</th>
                                    <td>{{ $globalStatistics['word_entries_count'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('about.general_statistics.rows.sessions_count') }}</th>
                                    <td>{{ $globalStatistics['sessions_count'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('about.general_statistics.rows.accuracy_percentage') }}</th>
                                    <td>
                                        {{ $globalStatistics['accuracy_percentage'] !== null ? rtrim(rtrim(number_format($globalStatistics['accuracy_percentage'], 1), '0'), '.') . '%' : __('about.general_statistics.fallbacks.no_answers') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="about-contact-card" aria-label="Contact form">
                <header class="about-contact-card__header">
                    <button
                        type="button"
                        class="about-section-toggle"
                        @click="contactOpen = !contactOpen"
                        :aria-expanded="contactOpen.toString()"
                        aria-controls="about-contact-panel"
                    >
                        <span class="about-contact-card__title">{{ __('about.contact.title') }}</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': contactOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                @if (session('aboutContactStatus'))
                    <div class="about-contact-alert about-contact-alert--success" role="status">
                        <span class="about-contact-alert__icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7 7.06a1 1 0 0 1-1.42.004L3.3 8.79a1 1 0 1 1 1.4-1.428l4.28 4.2 6.31-6.265a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span>{{ session('aboutContactStatus') }}</span>
                    </div>
                @endif

                <div id="about-contact-panel" x-show="contactOpen" x-cloak>
                    <p class="about-contact-card__subtitle">{{ __('about.contact.subtitle') }}</p>

                    @if (session('aboutContactError'))
                        <div class="about-contact-alert about-contact-alert--error" role="alert">
                            <span class="about-contact-alert__icon" aria-hidden="true">
                                <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm0-10.75a.75.75 0 0 1 .75.75v3.25a.75.75 0 0 1-1.5 0V8a.75.75 0 0 1 .75-.75Zm0 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>{{ session('aboutContactError') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('about.contact.store') }}" class="about-contact-form">
                    @csrf

                        <div class="about-contact-form__grid">
                            <div class="about-contact-field">
                                <label for="contact-email" class="about-contact-field__label">{{ __('about.contact.email') }}</label>
                                <input
                                    id="contact-email"
                                    name="contact_email"
                                    type="email"
                                    class="about-contact-field__input @error('contact_email') about-contact-field__input--error @enderror"
                                    placeholder="{{ __('about.contact.email_placeholder') }}"
                                    value="{{ old('contact_email') }}"
                                    aria-invalid="{{ $errors->has('contact_email') ? 'true' : 'false' }}"
                                    required
                                >
                                @error('contact_email')
                                    <p class="about-contact-field__error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="about-contact-field">
                                <label for="contact-subject" class="about-contact-field__label">{{ __('about.contact.subject') }}</label>
                                <input
                                    id="contact-subject"
                                    name="subject"
                                    type="text"
                                    class="about-contact-field__input @error('subject') about-contact-field__input--error @enderror"
                                    placeholder="{{ __('about.contact.subject_placeholder') }}"
                                    value="{{ old('subject') }}"
                                    maxlength="128"
                                    aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}"
                                    required
                                >
                                @error('subject')
                                    <p class="about-contact-field__error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="about-contact-field">
                            <label for="contact-message" class="about-contact-field__label">{{ __('about.contact.message') }}</label>
                            <textarea
                                id="contact-message"
                                name="message"
                                class="about-contact-field__textarea @error('message') about-contact-field__input--error @enderror"
                                placeholder="{{ __('about.contact.message_placeholder') }}"
                                maxlength="600"
                                aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                                required
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="about-contact-field__error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="about-contact-form__actions">
                            <button type="submit" class="btn btn-primary about-contact-form__button">{{ __('about.contact.send') }}</button>
                            <button type="reset" class="btn btn-secondary about-contact-form__button">{{ __('about.contact.clear') }}</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="about-status-card" aria-label="Project functionality status">
                <header class="about-status-card__header">
                    <button
                        type="button"
                        class="about-section-toggle"
                        @click="functionalityOpen = !functionalityOpen"
                        :aria-expanded="functionalityOpen.toString()"
                        aria-controls="about-functionality-panel"
                    >
                        <span class="about-status-card__title">{{ __('about.status.title') }}</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': functionalityOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                <div id="about-functionality-panel" x-show="functionalityOpen" x-cloak>
                    <p class="about-status-card__subtitle">{{ __('about.status.subtitle') }}</p>

                    <div class="about-status-table-wrap">
                        <table class="about-status-table">
                            <thead>
                                <tr>
                                    <th>{{ __('about.status.columns.functionality') }}</th>
                                    <th>{{ __('about.status.columns.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ __('about.status.items.manage_dictionaries') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.manual_words') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.search_filter_sort') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.translation_suggestions') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.delete_confirmations') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.manual_remainder') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.choice_remainder') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.profile_statistics') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.snapshot_part_of_speech') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.about_contact_form') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.language_switcher') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.preferred_locale') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.localized_flows') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.telegram_bot') }}</td>
                                    <td><span class="about-status-badge about-status-badge--planning">{{ __('about.status.badges.planning') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.telegram_integration') }}</td>
                                    <td><span class="about-status-badge about-status-badge--planning">{{ __('about.status.badges.planning') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.telegram_send_mode') }}</td>
                                    <td><span class="about-status-badge about-status-badge--planning">{{ __('about.status.badges.planning') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.local_translation_provider') }}</td>
                                    <td><span class="about-status-badge about-status-badge--planning">{{ __('about.status.badges.planning') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.real_contact_delivery') }}</td>
                                    <td><span class="about-status-badge about-status-badge--planning">{{ __('about.status.badges.planning') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.game_visuals') }}</td>
                                    <td><span class="about-status-badge about-status-badge--planning">{{ __('about.status.badges.planning') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('about.status.items.logo') }}</td>
                                    <td><span class="about-status-badge about-status-badge--done">{{ __('about.status.badges.done') }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="about-status-card" aria-label="Privacy policy">
                <header class="about-status-card__header">
                    <button
                        type="button"
                        class="about-section-toggle"
                        @click="privacyPolicyOpen = !privacyPolicyOpen"
                        :aria-expanded="privacyPolicyOpen.toString()"
                        aria-controls="about-privacy-policy-panel"
                    >
                        <span class="about-status-card__title">{{ __('about.legal.privacy.title') }}</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': privacyPolicyOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                <div id="about-privacy-policy-panel" x-show="privacyPolicyOpen" x-cloak>
                    <div class="about-legal-copy">
                        @include(app()->getLocale() === 'en' ? 'about.partials.privacy-policy-en' : 'about.partials.privacy-policy')
                    </div>
                </div>
            </section>

            <section class="about-status-card" aria-label="Cookie policy">
                <header class="about-status-card__header">
                    <button
                        type="button"
                        class="about-section-toggle"
                        @click="cookiePolicyOpen = !cookiePolicyOpen"
                        :aria-expanded="cookiePolicyOpen.toString()"
                        aria-controls="about-cookie-policy-panel"
                    >
                        <span class="about-status-card__title">{{ __('about.legal.cookies.title') }}</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': cookiePolicyOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                <div id="about-cookie-policy-panel" x-show="cookiePolicyOpen" x-cloak>
                    <div class="about-legal-copy">
                        @include(app()->getLocale() === 'en' ? 'about.partials.cookie-policy-en' : 'about.partials.cookie-policy')
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection
