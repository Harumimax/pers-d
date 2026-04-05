<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dictionaries.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dictionary-show.css') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="dictionaries-shell">
        <x-site-header nav-class="dictionaries-header-nav" label="Dictionaries navigation">
            <a href="{{ route('dictionaries.index') }}" class="dictionaries-header-nav__link">
                Dictionaries
            </a>
            <a href="{{ route('profile.edit') }}" class="dictionaries-header-nav__link">
                Profile
            </a>

            <form method="POST" action="{{ route('logout') }}" class="dictionaries-header-nav__form">
                @csrf
                <button type="submit" class="btn btn-secondary">Log out</button>
            </form>
        </x-site-header>

        {{ $slot }}

        @livewireScripts
    </body>
</html>
