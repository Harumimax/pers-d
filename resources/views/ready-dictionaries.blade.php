@extends('layouts.profile', ['activeNav' => 'ready-dictionaries'])

@section('content')
    <main class="profile-main">
        <div class="profile-container">
            <header class="profile-page-header">
                <h1 class="profile-title">{{ __('ready_dictionaries.title') }}</h1>
            </header>

            <section class="profile-card">
                <div class="profile-section">
                    <div class="profile-section__header">
                        <h2 class="profile-section__title">{{ __('ready_dictionaries.subtitle') }}</h2>
                        <p class="profile-section__description">
                            {{ __('ready_dictionaries.description') }}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection
