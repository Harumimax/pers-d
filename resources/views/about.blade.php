@extends('layouts.profile', ['activeNav' => 'about'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush

@section('content')
    <main class="about-main">
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

            <section class="about-status-card" aria-label="Project functionality status">
                <header class="about-status-card__header">
                    <h2 class="about-status-card__title">Current functionality</h2>
                    <p class="about-status-card__subtitle">What is already available in the product and what is being built next.</p>
                </header>

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
                                <td>Create a word repetition mode</td>
                                <td><span class="about-status-badge about-status-badge--progress">in progress</span></td>
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
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
@endsection
