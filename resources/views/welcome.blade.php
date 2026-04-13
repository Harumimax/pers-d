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
                    Your Personal Foreign<br>
                    Word Dictionary
                </h1>

                <p class="hero-description">
                    Build and organize your vocabulary across multiple languages.
                    Track words, meanings, and examples in one simple place.
                </p>

                <div class="hero-actions">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-large">Dictionaries</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary btn-large">Sign up</a>
                        <a href="{{ route('login') }}" class="btn btn-secondary btn-large">Log in</a>
                    @endauth
                </div>
            </section>

            <section class="hero-image-wrapper">
                <img
                    src="{{ asset('images/welcome-book.jpg') }}"
                    alt="Open dictionary book"
                    class="hero-image"
                >
            </section>
        </div>
    </main>
</body>
</html>
