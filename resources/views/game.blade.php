@extends('layouts.profile', ['activeNav' => 'game'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/game.css') }}">
@endpush

@section('content')
    <main class="game-page" data-game-page>
        <div class="container game-page__container">
            <section class="game-hero">
                <div class="game-hero__copy">
                    <p class="game-hero__eyebrow">{{ __('game.hero.eyebrow') }}</p>
                    <h1 class="game-hero__title">{{ __('game.hero.title') }}</h1>
                    <p class="game-hero__description">{{ __('game.hero.description') }}</p>
                </div>
            </section>

            <section class="game-shell" aria-label="{{ __('game.canvas.aria') }}">
                <div class="game-shell__stage">
                    <div class="game-canvas-frame">
                        <canvas
                            id="platformer-canvas"
                            class="game-canvas"
                            width="960"
                            height="540"
                            aria-label="{{ __('game.canvas.aria') }}"
                        ></canvas>

                        <div class="game-start-screen" data-game-start-screen>
                            <div class="game-start-screen__card">
                                <p class="game-start-screen__eyebrow">{{ __('game.start.badge') }}</p>
                                <h2 class="game-start-screen__title">{{ __('game.start.title') }}</h2>
                                <p class="game-start-screen__description">{{ __('game.start.description') }}</p>

                                <ul class="game-start-screen__controls" aria-label="{{ __('game.start.controls_aria') }}">
                                    <li><strong>{{ __('game.start.controls.left_right_keys') }}</strong> — {{ __('game.start.controls.left_right') }}</li>
                                    <li><strong>{{ __('game.start.controls.jump_key') }}</strong> — {{ __('game.start.controls.jump') }}</li>
                                    <li><strong>{{ __('game.start.controls.shoot_key') }}</strong> — {{ __('game.start.controls.shoot') }}</li>
                                </ul>

                                <button type="button" class="btn btn-primary game-start-screen__button" data-game-start-button>
                                    {{ __('game.start.action') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="game-progress-panel" aria-label="{{ __('game.progress.title') }}">
                    <div class="game-progress-panel__card">
                        <div>
                            <p class="game-progress-panel__eyebrow">{{ __('game.progress.eyebrow') }}</p>
                            <h2 class="game-progress-panel__title">{{ __('game.progress.title') }}</h2>
                            <p class="game-progress-panel__description">{{ __('game.progress.description') }}</p>
                        </div>

                        <div class="game-progress-panel__preview" data-game-progress-preview>
                            <div class="game-progress-panel__preview-image">
                                <span>{{ __('game.progress.preview_label', ['number' => 1]) }}</span>
                            </div>
                            <div class="game-progress-panel__preview-meta">
                                <strong data-game-progress-caption>{{ __('game.progress.slide_caption', ['current' => 1, 'total' => 10]) }}</strong>
                                <span>{{ __('game.progress.preview_hint') }}</span>
                            </div>
                        </div>

                        <ol class="game-progress-panel__steps">
                            @foreach ($progressSlides as $slideNumber)
                                <li
                                    class="game-progress-panel__step {{ $slideNumber === 1 ? 'is-active' : '' }}"
                                    data-game-progress-step
                                    data-slide-number="{{ $slideNumber }}"
                                >
                                    <span class="game-progress-panel__step-index">{{ str_pad((string) $slideNumber, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="game-progress-panel__step-label">{{ __('game.progress.preview_label', ['number' => $slideNumber]) }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <script src="{{ asset('js/game.js') }}" defer></script>
@endsection
