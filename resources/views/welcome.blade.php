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

                <p class="hero-supporting-line">
                    {{ __('welcome.supporting_line') }}
                </p>
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

    <section class="product-showcase" aria-labelledby="product-showcase-title">
        <div class="container">
            <div class="showcase-heading">
                <p class="showcase-eyebrow">{{ __('welcome.showcase.eyebrow') }}</p>
                <h2 id="product-showcase-title" class="showcase-title-heading">{{ __('welcome.showcase.title') }}</h2>
                <p class="showcase-heading__description">{{ __('welcome.showcase.description') }}</p>
            </div>

            <div class="showcase-shell">
                <input type="radio" name="showcase-slide" id="slide-1" checked>
                <input type="radio" name="showcase-slide" id="slide-2">
                <input type="radio" name="showcase-slide" id="slide-3">
                <input type="radio" name="showcase-slide" id="slide-4">
                <input type="radio" name="showcase-slide" id="slide-5">

                <div class="showcase-stage">
                    @foreach (['add_word', 'session_setup', 'multiple_choice', 'feedback', 'results'] as $index => $slideKey)
                        <article class="showcase-slide slide-{{ $index + 1 }}">
                            <div class="showcase-browser">
                                <div class="browser-dots" aria-hidden="true">
                                    <span></span><span></span><span></span>
                                </div>
                                <div class="browser-bar">{{ __('welcome.showcase.items.' . $slideKey . '.bar') }}</div>
                            </div>

                            <div class="showcase-image-wrap">
                                <img
                                    src="{{ asset('images/screenshots/' . __('welcome.showcase.items.' . $slideKey . '.image')) }}"
                                    alt="{{ __('welcome.showcase.items.' . $slideKey . '.image_alt') }}"
                                >
                            </div>

                            <div class="showcase-caption">
                                <div>
                                    <p class="showcase-kicker">{{ __('welcome.showcase.items.' . $slideKey . '.step') }}</p>
                                    <h3 class="showcase-slide__title">{{ __('welcome.showcase.items.' . $slideKey . '.title') }}</h3>
                                </div>
                                <p class="showcase-slide__description">{{ __('welcome.showcase.items.' . $slideKey . '.description') }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="showcase-thumbs" aria-label="{{ __('welcome.showcase.navigation_label') }}">
                    @foreach (['add_word', 'session_setup', 'multiple_choice', 'feedback', 'results'] as $index => $slideKey)
                        <label for="slide-{{ $index + 1 }}" class="showcase-thumb thumb-{{ $index + 1 }}">
                            <span class="thumb-image">
                                <img
                                    src="{{ asset('images/screenshots/' . __('welcome.showcase.items.' . $slideKey . '.image')) }}"
                                    alt=""
                                >
                            </span>
                            <span class="thumb-copy">
                                <strong>{{ __('welcome.showcase.items.' . $slideKey . '.thumb_title') }}</strong>
                                <small>{{ __('welcome.showcase.items.' . $slideKey . '.thumb_subtitle') }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

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
