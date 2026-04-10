<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dictionaries.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dictionary-show.css') }}">
        <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    @php($dictionariesNavActive = request()->routeIs('dictionaries.index') || request()->routeIs('dictionaries.show'))
    <body class="dictionaries-shell">
        <x-site-header nav-class="dictionaries-header-nav" label="Dictionaries navigation">
            <a href="{{ route('remainder') }}" class="dictionaries-header-nav__link">
                Remainder
            </a>
            <div class="dictionaries-header-nav__dropdown">
                <a
                    href="{{ route('dictionaries.index') }}"
                    class="dictionaries-header-nav__link {{ $dictionariesNavActive ? 'dictionaries-header-nav__link--active' : '' }}"
                >
                    Dictionaries
                </a>

                <div class="dictionaries-header-nav__menu" aria-label="Your dictionaries">
                    @foreach (($headerDictionaries ?? collect()) as $headerDictionary)
                        <a
                            href="{{ route('dictionaries.show', $headerDictionary) }}"
                            class="dictionaries-header-nav__menu-link"
                        >
                            {{ $headerDictionary->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="dictionaries-header-nav__link">
                Profile
            </a>

            <form method="POST" action="{{ route('logout') }}" class="dictionaries-header-nav__form">
                @csrf
                <button type="submit" class="btn btn-secondary">Log out</button>
            </form>
        </x-site-header>

        <div class="dictionaries-page">
            {{ $slot }}
        </div>

        <x-site-footer :link-href="route('about')" />

        @livewireScripts
    </body>
</html>
