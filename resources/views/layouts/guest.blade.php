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

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-shell">
        <x-site-header label="Authentication links">
            @if (Route::has('login'))
                <a
                    href="{{ route('login') }}"
                    class="btn {{ request()->routeIs('login') ? 'btn-primary' : 'btn-secondary' }}"
                >
                    Log in
                </a>
            @endif

            @if (Route::has('register'))
                <a
                    href="{{ route('register') }}"
                    class="btn {{ request()->routeIs('register') ? 'btn-primary' : 'btn-secondary' }}"
                >
                    Sign up
                </a>
            @endif

            <x-language-switcher />
        </x-site-header>

        <main class="auth-main">
            <div class="container">
                <section class="auth-card">
                    {{ $slot }}
                </section>
            </div>
        </main>
    </body>
</html>
