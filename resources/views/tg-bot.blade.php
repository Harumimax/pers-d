@extends('layouts.profile', ['activeNav' => 'tg-bot'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tg-bot.css') }}">
@endpush

@section('content')
    <main class="tg-bot-main">
        <div class="container tg-bot-container">
            <section class="tg-bot-hero">
                <h1 class="tg-bot-title">{{ __('tg-bot.title') }}</h1>
            </section>
        </div>
    </main>
@endsection
