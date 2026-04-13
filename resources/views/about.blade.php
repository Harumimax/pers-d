@extends('layouts.profile', ['activeNav' => 'about'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush

@section('content')
    <main class="about-main" x-data="{ contactOpen: false, functionalityOpen: false }">
        <div class="container about-container">
            <section class="about-hero">
                <div class="about-hero__image-wrap">
                    <img
                        src="{{ asset('images/about-study-desk.jpg') }}"
                        alt="Open dictionary book"
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

                <div id="about-contact-panel" x-show="contactOpen" x-cloak>
                    <p class="about-contact-card__subtitle">{{ __('about.contact.subtitle') }}</p>

                    <form method="POST" action="{{ route('about.contact.store') }}" class="about-contact-form">
                    @csrf

                        <div class="about-contact-form__grid">
                            <div class="about-contact-field">
                                <label for="contact-email" class="about-contact-field__label">{{ __('about.contact.email') }}</label>
                                <input
                                    id="contact-email"
                                    name="contact_email"
                                    type="email"
                                    class="about-contact-field__input"
                                    placeholder="{{ __('about.contact.email_placeholder') }}"
                                    required
                                >
                            </div>

                            <div class="about-contact-field">
                                <label for="contact-subject" class="about-contact-field__label">{{ __('about.contact.subject') }}</label>
                                <input
                                    id="contact-subject"
                                    name="subject"
                                    type="text"
                                    class="about-contact-field__input"
                                    placeholder="{{ __('about.contact.subject_placeholder') }}"
                                    maxlength="128"
                                    required
                                >
                            </div>
                        </div>

                        <div class="about-contact-field">
                            <label for="contact-message" class="about-contact-field__label">{{ __('about.contact.message') }}</label>
                            <textarea
                                id="contact-message"
                                name="message"
                                class="about-contact-field__textarea"
                                placeholder="{{ __('about.contact.message_placeholder') }}"
                                maxlength="600"
                                required
                            ></textarea>
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
                                    <td>{{ __('about.status.items.placeholder_language_switcher') }}</td>
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
                                    <td>{{ __('about.status.items.real_localization') }}</td>
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
        </div>
    </main>
@endsection
