@extends('layouts.profile', ['activeNav' => 'remainder'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/remainder-game.css') }}">
@endpush

@section('content')
    <main class="remainder-game-main">
        <div class="container remainder-game-container">
            <livewire:remainder.show :game-session="$gameSession" />
        </div>
    </main>
@endsection
