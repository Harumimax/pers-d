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
        <link rel="stylesheet" href="{{ asset('css/profile.css') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="profile-shell">
        <x-site-header nav-class="profile-header-nav" label="Profile navigation">
            <a href="{{ route('dictionaries.index') }}" class="profile-header-nav__link">
                Dictionaries
            </a>
            <a href="{{ route('profile.edit') }}" class="profile-header-nav__link profile-header-nav__link--active">
                Profile
            </a>

            <form method="POST" action="{{ route('logout') }}" class="profile-header-nav__form">
                @csrf
                <button type="submit" class="btn btn-secondary">Log out</button>
            </form>
        </x-site-header>

        @yield('content')
    </body>
</html>
