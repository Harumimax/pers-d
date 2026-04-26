@extends('layouts.profile', ['activeNav' => 'tg-bot'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dictionaries.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tg-bot.css') }}">
@endpush

@section('content')
    <main class="tg-bot-main">
        <section class="dictionaries-container dictionaries-intro tg-bot-intro">
            <div class="dictionaries-intro__copy">
                <h1 class="dictionaries-title tg-bot-page-title">{{ __('tg-bot.title') }}</h1>
                <p class="dictionaries-subtitle">{{ __('tg-bot.subtitle') }}</p>
            </div>
        </section>

        <section class="dictionaries-container dictionaries-list" aria-label="{{ __('tg-bot.title') }}">
            <article class="dictionary-card tg-bot-card">
                <div class="dictionary-card__content">
                    <h2 class="dictionary-card__title">{{ __('tg-bot.bot_address_title') }}</h2>
                    <p class="dictionary-card__meta tg-bot-card__description">
                        {{ __('tg-bot.bot_address_text') }}
                        <a href="https://t.me/WordKeeperBot_bot" class="tg-bot-link" target="_blank" rel="noopener noreferrer">@WordKeeperBot_bot</a>
                    </p>
                </div>
            </article>

            <article class="dictionary-card tg-bot-card">
                <div class="dictionary-card__content">
                    <h2 class="dictionary-card__title">{{ __('tg-bot.features_title') }}</h2>
                    <ul class="tg-bot-feature-list" aria-label="{{ __('tg-bot.features_title') }}">
                        <li>{{ __('tg-bot.features.auth') }}</li>
                        <li>{{ __('tg-bot.features.dictionaries') }}</li>
                        <li>{{ __('tg-bot.features.words') }}</li>
                        <li>{{ __('tg-bot.features.remainder') }}</li>
                        <li>{{ __('tg-bot.features.notifications') }}</li>
                    </ul>
                </div>
            </article>

            <article class="dictionary-card tg-bot-card">
                <div class="dictionary-card__content">
                    <h2 class="dictionary-card__title">{{ __('tg-bot.connect_title') }}</h2>
                    <p class="dictionary-card__meta tg-bot-card__description">{{ __('tg-bot.connect_text') }}</p>
                    <p class="dictionary-card__meta tg-bot-card__description">{{ __('tg-bot.connect_note') }}</p>
                </div>
            </article>
        </section>
    </main>
@endsection
