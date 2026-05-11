@extends('layouts.profile', ['activeNav' => 'game'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/game.css') }}">
@endpush

@section('content')
    <main class="game-page" data-game-page>
        <div class="container game-page__container">
            <section class="game-shell" aria-label="{{ __('game.canvas.aria') }}">
                <div class="game-shell__stage">
                    <div class="game-canvas-frame">
                        <div class="game-hud">
                            <div class="game-hud__lives" data-game-lives data-label="{{ __('game.hud.lives') }}">
                                {{ __('game.hud.lives') }}: 3
                            </div>
                        </div>

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
                                    <li><strong>{{ __('game.start.controls.left_right_keys') }}</strong> - {{ __('game.start.controls.left_right') }}</li>
                                    <li><strong>{{ __('game.start.controls.jump_key') }}</strong> - {{ __('game.start.controls.jump') }}</li>
                                    <li><strong>{{ __('game.start.controls.shoot_key') }}</strong> - {{ __('game.start.controls.shoot') }}</li>
                                </ul>

                                <button type="button" class="btn btn-primary game-start-screen__button" data-game-start-button>
                                    {{ __('game.start.action') }}
                                </button>
                            </div>
                        </div>

                        <div class="game-status-screen is-hidden" data-game-win-screen aria-hidden="true">
                            <div class="game-status-screen__card">
                                <h2 class="game-status-screen__title">{{ __('game.win.title') }}</h2>
                                <p class="game-status-screen__description">{{ __('game.win.description') }}</p>

                                <button type="button" class="btn btn-primary game-status-screen__button" data-game-win-restart-button>
                                    {{ __('game.win.action') }}
                                </button>
                            </div>
                        </div>

                        <div class="game-status-screen is-hidden" data-game-lose-screen aria-hidden="true">
                            <div class="game-status-screen__card">
                                <h2 class="game-status-screen__title">{{ __('game.lose.title') }}</h2>
                                <p class="game-status-screen__description">{{ __('game.lose.description') }}</p>

                                <button type="button" class="btn btn-primary game-status-screen__button" data-game-lose-restart-button>
                                    {{ __('game.lose.action') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="game-progress-panel" aria-label="{{ __('game.progress.title') }}">
                    <div class="game-progress-panel__card">
                        <h2 class="game-progress-panel__title">{{ __('game.progress.title') }}</h2>

                        <div class="game-progress-panel__preview" data-game-progress-preview>
                            <div class="game-progress-panel__preview-image">
                                <span>{{ __('game.progress.preview_label', ['number' => 1]) }}</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <script src="{{ asset('js/game.js') }}" defer></script>
@endsection
