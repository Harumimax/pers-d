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
        <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
        <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
        @stack('styles')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php($activeNav = $activeNav ?? null)
    <body class="profile-shell">
        <x-site-header nav-class="profile-header-nav" :label="__('common.navigation.profile')">
            <div class="profile-header-nav__dropdown">
                <a href="{{ route('dictionaries.index') }}" class="profile-header-nav__link">
                    {{ __('common.links.dictionaries') }}
                </a>

                @if (($headerDictionaries ?? collect())->isNotEmpty())
                    <div class="profile-header-nav__menu" aria-label="Your dictionaries">
                        @foreach ($headerDictionaries as $headerDictionary)
                        <a
                            href="{{ route('dictionaries.show', $headerDictionary) }}"
                            class="profile-header-nav__menu-link"
                        >
                            {{ $headerDictionary->name }}
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>
            <a
                href="{{ route('remainder') }}"
                class="profile-header-nav__link {{ $activeNav === 'remainder' ? 'profile-header-nav__link--active' : '' }}"
            >
                {{ __('common.links.remainder') }}
            </a>
            <div class="profile-header-nav__dropdown">
                <a
                    href="{{ route('ready-dictionaries.index') }}"
                    class="profile-header-nav__link {{ $activeNav === 'ready-dictionaries' ? 'profile-header-nav__link--active' : '' }}"
                >
                    {{ __('common.links.ready_dictionaries') }}
                </a>

                @if (($headerReadyDictionaries ?? collect())->isNotEmpty())
                    <div class="profile-header-nav__menu" aria-label="{{ __('common.links.ready_dictionaries') }}">
                        @foreach ($headerReadyDictionaries as $headerReadyDictionary)
                            <a
                                href="{{ route('ready-dictionaries.show', $headerReadyDictionary) }}"
                                class="profile-header-nav__menu-link"
                            >
                                {{ $headerReadyDictionary->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            <a
                href="{{ route('profile.edit') }}"
                class="profile-header-nav__link {{ $activeNav === 'profile' ? 'profile-header-nav__link--active' : '' }}"
            >
                {{ __('common.links.profile') }}
            </a>

            <form method="POST" action="{{ route('logout') }}" class="profile-header-nav__form">
                @csrf
                <button type="submit" class="btn btn-secondary">{{ __('common.links.logout') }}</button>
            </form>
            <x-language-switcher />
        </x-site-header>

        <div class="profile-page">
            @yield('content')
        </div>

        <x-site-footer :link-href="route('about')" :link-label="__('common.links.about')" />
    </body>
</html>
