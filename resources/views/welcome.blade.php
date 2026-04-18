<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">

    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body>


        <x-site-header :label="__('common.navigation.auth')">
                @auth
                    <a href="{{ route('remainder') }}" class="btn btn-secondary">{{ __('common.links.remainder') }}</a>
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('common.links.dictionaries') }}</a>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary">{{ __('common.links.logout') }}</button>
                    </form>
                    <x-language-switcher />
                @else
                    <a href="{{ route('login') }}" class="btn btn-secondary">{{ __('common.links.login') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">{{ __('common.links.signup') }}</a>
                    <x-language-switcher />
                @endauth
        </x-site-header>

    <main class="hero">
        <div class="container hero-inner">
            <section class="hero-content">
                <h1 class="hero-title">
                    {{ __('welcome.title_line_1') }}<br>
                    {{ __('welcome.title_line_2') }}
                </h1>

                <p class="hero-description">
                    {{ __('welcome.description') }}
                </p>

                <p class="hero-description">
                    {{ __('welcome.description_secondary') }}
                </p>

                <div class="hero-actions">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-large">{{ __('welcome.actions.dictionaries') }}</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary btn-large">{{ __('welcome.actions.signup') }}</a>
                        <a href="{{ route('login') }}" class="btn btn-secondary btn-large">{{ __('welcome.actions.login') }}</a>
                    @endauth
                </div>
            </section>

            <section class="hero-image-wrapper">
                <img
                    src="{{ asset('images/welcome-book.jpg') }}"
                    alt="{{ __('welcome.image_alt') }}"
                    class="hero-image"
                >
            </section>
        </div>
    </main>

    <section class="use-cases" aria-labelledby="use-cases-title">
        <div class="container">
            <div class="use-cases__heading">
                <p class="use-cases__eyebrow">{{ __('welcome.use_cases.eyebrow') }}</p>
                <h2 id="use-cases-title" class="use-cases__title">{{ __('welcome.use_cases.title') }}</h2>
                <p class="use-cases__description">{{ __('welcome.use_cases.description') }}</p>
            </div>

            <div class="use-cases__grid">
                <article class="use-case-card">
                    <div class="use-case-card__icon" aria-hidden="true">{{ __('welcome.use_cases.items.reading.icon') }}</div>
                    <h3 class="use-case-card__title">{{ __('welcome.use_cases.items.reading.title') }}</h3>
                    <p class="use-case-card__text">{{ __('welcome.use_cases.items.reading.text') }}</p>
                </article>

                <article class="use-case-card">
                    <div class="use-case-card__icon" aria-hidden="true">{{ __('welcome.use_cases.items.studying.icon') }}</div>
                    <h3 class="use-case-card__title">{{ __('welcome.use_cases.items.studying.title') }}</h3>
                    <p class="use-case-card__text">{{ __('welcome.use_cases.items.studying.text') }}</p>
                </article>

                <article class="use-case-card">
                    <div class="use-case-card__icon" aria-hidden="true">{{ __('welcome.use_cases.items.speaking.icon') }}</div>
                    <h3 class="use-case-card__title">{{ __('welcome.use_cases.items.speaking.title') }}</h3>
                    <p class="use-case-card__text">{{ __('welcome.use_cases.items.speaking.text') }}</p>
                </article>
            </div>
        </div>
    </section>
</body>
</html>
