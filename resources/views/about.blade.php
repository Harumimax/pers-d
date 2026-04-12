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
                    <p class="about-hero__eyebrow">About WordKeeper</p>
                    <h1 class="about-hero__title">A calm workspace for building your personal foreign-word dictionaries.</h1>
                    <p class="about-hero__description">
                        WordKeeper is a personal vocabulary site for people who want one neat place to collect foreign words, keep translations close at hand,
                        and organize learning by separate dictionaries. The product is intentionally focused: instead of spreading words across notes, chats, and
                        browser tabs, you keep them in one structured space with clean navigation and a lightweight workflow.
                    </p>
                    <p class="about-hero__description">
                        Right now the site already supports the full core loop: authenticated users can create personal dictionaries, add words manually or through
                        assisted translation, assign part of speech, keep comments, and quickly search, sort, and filter entries inside each dictionary.
                        The next product step is turning stored vocabulary into active practice, starting with a dedicated repetition mode for words.
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
                        <span class="about-contact-card__title">Contact form</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': contactOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                <div id="about-contact-panel" x-show="contactOpen" x-cloak>
                    <p class="about-contact-card__subtitle">Share a question, suggestion, or bug report. The form design is ready, and the mail backend will be connected later.</p>

                    <form method="POST" action="{{ route('about.contact.store') }}" class="about-contact-form">
                    @csrf

                        <div class="about-contact-form__grid">
                            <div class="about-contact-field">
                                <label for="contact-email" class="about-contact-field__label">Contact email</label>
                                <input
                                    id="contact-email"
                                    name="contact_email"
                                    type="email"
                                    class="about-contact-field__input"
                                    placeholder="you@example.com"
                                    required
                                >
                            </div>

                            <div class="about-contact-field">
                                <label for="contact-subject" class="about-contact-field__label">Subject</label>
                                <input
                                    id="contact-subject"
                                    name="subject"
                                    type="text"
                                    class="about-contact-field__input"
                                    placeholder="What would you like to discuss?"
                                    maxlength="128"
                                    required
                                >
                            </div>
                        </div>

                        <div class="about-contact-field">
                            <label for="contact-message" class="about-contact-field__label">Message</label>
                            <textarea
                                id="contact-message"
                                name="message"
                                class="about-contact-field__textarea"
                                placeholder="Write your message here..."
                                maxlength="600"
                                required
                            ></textarea>
                        </div>

                        <div class="about-contact-form__actions">
                            <button type="submit" class="btn btn-primary about-contact-form__button">Send</button>
                            <button type="reset" class="btn btn-secondary about-contact-form__button">Clear all</button>
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
                        <span class="about-status-card__title">Current functionality</span>
                        <span class="about-section-toggle__icon" :class="{ 'about-section-toggle__icon--open': functionalityOpen }" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </button>
                </header>

                <div id="about-functionality-panel" x-show="functionalityOpen" x-cloak>
                    <p class="about-status-card__subtitle">What is already available in the product and what is being built next.</p>

                    <div class="about-status-table-wrap">
                        <table class="about-status-table">
                            <thead>
                                <tr>
                                    <th>Functionality</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Create and manage personal dictionaries</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Add words manually with translation, part of speech, and comment</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Search, filter, sort, and paginate words inside a dictionary</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Automatic translation suggestions during word creation</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Delete dictionaries and words with confirmation dialogs</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Play Remainder sessions with manual translation input</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Play Remainder sessions in multiple choice mode</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Track personal Remainder statistics on the profile page</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Create a word repetition mode</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                                <tr>
                                    <td>Create a Telegram bot</td>
                                    <td><span class="about-status-badge about-status-badge--planning">planning</span></td>
                                </tr>
                                <tr>
                                    <td>Connect site functionality to the Telegram bot</td>
                                    <td><span class="about-status-badge about-status-badge--planning">planning</span></td>
                                </tr>
                                <tr>
                                    <td>Create a mode for sending words to the Telegram bot</td>
                                    <td><span class="about-status-badge about-status-badge--planning">planning</span></td>
                                </tr>
                                <tr>
                                    <td>Switch to another local translation provider</td>
                                    <td><span class="about-status-badge about-status-badge--planning">planning</span></td>
                                </tr>
                                <tr>
                                    <td>Add an interface language switcher for Russian and English</td>
                                    <td><span class="about-status-badge about-status-badge--planning">planning</span></td>
                                </tr>
                                <tr>
                                    <td>Make the game interface more varied with alternate progress images and memes</td>
                                    <td><span class="about-status-badge about-status-badge--planning">planning</span></td>
                                </tr>
                                <tr>
                                    <td>Create a WordKeeper logo</td>
                                    <td><span class="about-status-badge about-status-badge--done">done</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection
