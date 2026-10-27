<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ __('welcome.meta_description') }}">
    <meta name="theme-color" content="#f3f3f3">
    <title>{{ __('welcome.meta_title') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="stylesheet" href="{{ \App\Support\VersionedAsset::url('css/welcome.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\VersionedAsset::url('css/footer.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="home-shell">
    <a href="#main-content" class="skip-link">{{ __('welcome.skip_to_content') }}</a>

    <x-site-header :label="__('common.navigation.auth')" :mobile-label="__('common.navigation.auth')">
        @auth
            <a href="{{ route('dictionaries.index') }}" class="header-actions__item header-actions__link">{{ __('common.links.dictionaries') }}</a>
            <a href="{{ route('remainder') }}" class="header-actions__item header-actions__link">{{ __('common.links.remainder') }}</a>
            <a href="{{ route('ready-dictionaries.index') }}" class="header-actions__item header-actions__link">{{ __('common.links.ready_dictionaries') }}</a>
            <a href="{{ route('tg-bot') }}" class="header-actions__item header-actions__link">{{ __('common.links.tg_bot') }}</a>
            <a href="{{ route('translator.index') }}" class="header-actions__item header-actions__link">{{ __('translator.menu') }}</a>
            <a href="{{ route('profile.edit') }}" class="header-actions__item header-actions__link">{{ __('common.links.profile') }}</a>
            <form method="POST" action="{{ route('logout') }}" class="header-actions__item header-actions__form">
                @csrf
                <button type="submit" class="header-actions__link header-actions__logout">{{ __('common.links.logout') }}</button>
            </form>
        @else
            <a href="{{ route('ready-dictionaries.index') }}" class="header-actions__item header-actions__link">{{ __('common.links.ready_dictionaries') }}</a>
            <a href="{{ route('login') }}" class="btn btn-secondary">{{ __('common.links.login') }}</a>
            <a href="{{ route('register') }}" class="btn btn-primary">{{ __('common.links.signup') }}</a>
        @endauth

        <x-slot:utility>
            <x-language-switcher />
        </x-slot:utility>

        <x-slot:mobile>
            @auth
                <a href="{{ route('dictionaries.index') }}" class="site-header__mobile-link">{{ __('common.links.dictionaries') }}</a>
                <a href="{{ route('remainder') }}" class="site-header__mobile-link">{{ __('common.links.remainder') }}</a>
                <a href="{{ route('ready-dictionaries.index') }}" class="site-header__mobile-link">{{ __('common.links.ready_dictionaries') }}</a>
                <a href="{{ route('tg-bot') }}" class="site-header__mobile-link">{{ __('common.links.tg_bot') }}</a>
                <a href="{{ route('translator.index') }}" class="site-header__mobile-link">{{ __('translator.menu') }}</a>
                <a href="{{ route('profile.edit') }}" class="site-header__mobile-link">{{ __('common.links.profile') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="site-header__mobile-form">
                    @csrf
                    <button type="submit" class="site-header__mobile-link site-header__mobile-link--button">{{ __('common.links.logout') }}</button>
                </form>
            @else
                <a href="{{ route('ready-dictionaries.index') }}" class="site-header__mobile-link">{{ __('common.links.ready_dictionaries') }}</a>
                <a href="{{ route('remainder') }}" class="site-header__mobile-link">{{ __('common.links.remainder') }}</a>
                <a href="{{ route('login') }}" class="site-header__mobile-link">{{ __('common.links.login') }}</a>
                <a href="{{ route('register') }}" class="site-header__mobile-link">{{ __('common.links.signup') }}</a>
            @endauth
        </x-slot:mobile>
    </x-site-header>

    <main id="main-content" class="home-main">
        <section class="home-intro" aria-labelledby="home-intro-title">
            <div class="container home-intro__inner">
                <div class="home-intro__copy">
                    <p class="home-kicker">{{ __('welcome.intro.kicker') }}</p>
                    <h1 id="home-intro-title" class="home-intro__title">{{ __('welcome.intro.title') }}</h1>
                    <p class="home-intro__text">{{ __('welcome.intro.text') }}</p>
                    <nav class="home-intro__links" aria-label="{{ __('welcome.intro.navigation_label') }}">
                        @auth
                            <a href="{{ route('dictionaries.index') }}" class="btn btn-primary btn-large">{{ __('welcome.actions.open_dictionaries') }}</a>
                        @else
                            <a href="{{ route('ready-dictionaries.index') }}" class="btn btn-primary btn-large">{{ __('welcome.actions.explore_ready') }}</a>
                        @endauth
                        <a href="{{ route('about') }}" class="home-text-link">{{ __('welcome.actions.about') }}</a>
                    </nav>
                    <p class="home-intro__note">{{ __('welcome.intro.note') }}</p>
                </div>

                <div class="lexicon-scene" aria-label="{{ __('welcome.entry.aria_label') }}">
                    <div class="lexicon-scene__index" aria-hidden="true">
                        <span>EN</span>
                        <span>RU</span>
                    </div>
                    <article class="lexicon-entry">
                        <div class="lexicon-entry__topline">
                            <span>{{ __('welcome.entry.language') }}</span>
                            <span>{{ __('welcome.entry.part_of_speech') }}</span>
                        </div>
                        <p class="lexicon-entry__word" lang="en">curiosity</p>
                        <p class="lexicon-entry__translation">{{ __('welcome.entry.translation') }}</p>
                        <div class="lexicon-entry__rule" aria-hidden="true"></div>
                        <blockquote class="lexicon-entry__example">
                            <p lang="en">Curiosity turns a new word into a familiar one.</p>
                            <footer>{{ __('welcome.entry.example_translation') }}</footer>
                        </blockquote>
                        <div class="lexicon-entry__tags" aria-label="{{ __('welcome.entry.tags_label') }}">
                            <span>{{ __('welcome.entry.tag_dictionary') }}</span>
                            <span>{{ __('welcome.entry.tag_example') }}</span>
                            <span>{{ __('welcome.entry.tag_favorite') }}</span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="home-capabilities" aria-labelledby="home-capabilities-title">
            <div class="container">
                <header class="home-section-heading">
                    <p class="home-kicker">{{ __('welcome.capabilities.kicker') }}</p>
                    <h2 id="home-capabilities-title">{{ __('welcome.capabilities.title') }}</h2>
                    <p>{{ __('welcome.capabilities.text') }}</p>
                </header>

                <div class="capability-map">
                    <article class="capability-item capability-item--primary">
                        <span class="capability-item__mark" aria-hidden="true">Aa</span>
                        <h3>{{ __('welcome.capabilities.items.personal.title') }}</h3>
                        <p>{{ __('welcome.capabilities.items.personal.text') }}</p>
                        @auth
                            <a href="{{ route('dictionaries.index') }}">{{ __('welcome.capabilities.items.personal.link') }}</a>
                        @else
                            <span class="capability-item__availability">{{ __('welcome.capabilities.account_required') }}</span>
                        @endauth
                    </article>

                    <article class="capability-item capability-item--prepared">
                        <span class="capability-item__mark" aria-hidden="true">100</span>
                        <h3>{{ __('welcome.capabilities.items.prepared.title') }}</h3>
                        <p>{{ __('welcome.capabilities.items.prepared.text') }}</p>
                        <a href="{{ route('ready-dictionaries.index') }}">{{ __('welcome.capabilities.items.prepared.link') }}</a>
                    </article>

                    <article class="capability-item capability-item--practice">
                        <span class="capability-item__mark" aria-hidden="true">6×</span>
                        <h3>{{ __('welcome.capabilities.items.practice.title') }}</h3>
                        <p>{{ __('welcome.capabilities.items.practice.text') }}</p>
                        <a href="{{ route('remainder') }}">{{ __('welcome.capabilities.items.practice.link') }}</a>
                    </article>

                    <article class="capability-item">
                        <h3>{{ __('welcome.capabilities.items.translation.title') }}</h3>
                        <p>{{ __('welcome.capabilities.items.translation.text') }}</p>
                    </article>

                    <article class="capability-item">
                        <h3>{{ __('welcome.capabilities.items.telegram.title') }}</h3>
                        <p>{{ __('welcome.capabilities.items.telegram.text') }}</p>
                    </article>

                    <article class="capability-item">
                        <h3>{{ __('welcome.capabilities.items.languages.title') }}</h3>
                        <p>{{ __('welcome.capabilities.items.languages.text') }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="home-workflow" aria-labelledby="home-workflow-title">
            <div class="container home-workflow__inner">
                <div class="home-workflow__copy">
                    <header class="home-section-heading home-section-heading--compact">
                        <p class="home-kicker">{{ __('welcome.workflow.kicker') }}</p>
                        <h2 id="home-workflow-title">{{ __('welcome.workflow.title') }}</h2>
                        <p>{{ __('welcome.workflow.text') }}</p>
                    </header>

                    <ol class="workflow-list">
                        @foreach (['discover', 'organize', 'practice'] as $step)
                            <li>
                                <span class="workflow-list__number">{{ $loop->iteration }}</span>
                                <div>
                                    <h3>{{ __('welcome.workflow.steps.' . $step . '.title') }}</h3>
                                    <p>{{ __('welcome.workflow.steps.' . $step . '.text') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <figure class="home-workflow__visual">
                    <div class="product-window">
                        <div class="product-window__bar" aria-hidden="true">
                            <span></span><span></span><span></span>
                            <b>wordkeeper.space / dictionary</b>
                        </div>
                        <img
                            src="{{ asset('images/screenshots/dictionary-interface.png') }}"
                            width="1896"
                            height="909"
                            loading="lazy"
                            alt="{{ __('welcome.workflow.image_alt') }}"
                        >
                    </div>
                    <figcaption>{{ __('welcome.workflow.caption') }}</figcaption>
                </figure>
            </div>
        </section>

        <section class="home-ready" aria-labelledby="home-ready-title">
            <div class="container">
                <div class="home-ready__heading-row">
                    <header class="home-section-heading home-section-heading--compact">
                        <p class="home-kicker">{{ __('welcome.ready.kicker') }}</p>
                        <h2 id="home-ready-title">{{ __('welcome.ready.title') }}</h2>
                        <p>{{ __('welcome.ready.text') }}</p>
                    </header>
                    <a href="{{ route('ready-dictionaries.index') }}" class="home-text-link home-ready__all">{{ __('welcome.ready.view_all') }}</a>
                </div>

                <div class="home-ready__grid">
                    @forelse ($featuredReadyDictionaries as $dictionary)
                        <article class="home-dictionary-card">
                            <a href="{{ route('ready-dictionaries.show', $dictionary) }}" class="home-dictionary-card__link" aria-label="{{ __('ready_dictionaries.card.open_aria', ['name' => $dictionary->name]) }}"></a>
                            <div class="home-dictionary-card__meta">
                                <span>{{ __('dictionaries.index.languages.' . strtolower($dictionary->language)) }}</span>
                                @if ($dictionary->level)
                                    <span>{{ $dictionary->level }}</span>
                                @endif
                            </div>
                            <h3>{{ $dictionary->name }}</h3>
                            <p>{{ trans_choice('dictionaries.index.words_count', $dictionary->words_count, ['count' => $dictionary->words_count]) }}</p>
                        </article>
                    @empty
                        <div class="home-ready__empty">
                            <h3>{{ __('welcome.ready.empty_title') }}</h3>
                            <p>{{ __('welcome.ready.empty_text') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    <x-site-footer :link-href="route('about')" :link-label="__('common.links.about')" />
</body>
</html>
