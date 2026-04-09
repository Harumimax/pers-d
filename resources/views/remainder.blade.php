@extends('layouts.profile', ['activeNav' => 'remainder'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/remainder.css') }}">
@endpush

@section('content')
    <main class="remainder-main">
        <div class="container remainder-container">
            <section class="remainder-card">
                <div class="remainder-copy">
                    <h1 class="remainder-title">Remainder</h1>
                    <p class="remainder-description">is a game for reminding words.</p>
                </div>
            </section>
        </div>
    </main>
@endsection
