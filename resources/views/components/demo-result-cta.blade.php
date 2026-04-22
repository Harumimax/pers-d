@props([
    'correct' => 0,
    'total' => 0,
])

<section class="demo-result-cta" aria-label="{{ __('common.demo.result_aria') }}">
    <div class="demo-result-cta__copy">
        <p class="demo-result-cta__eyebrow">{{ __('common.demo.banner_title') }}</p>
        <h2 class="demo-result-cta__title">
            {{ __('common.demo.result_score', ['correct' => $correct, 'total' => $total]) }}
        </h2>
        <p class="demo-result-cta__text">{{ __('common.demo.result_text') }}</p>
    </div>

    <div class="demo-result-cta__actions">
        <a href="{{ route('register') }}" class="btn btn-primary remainder-game-action-btn">
            {{ __('common.demo.create_account') }}
        </a>
        <a href="{{ route('remainder') }}" class="btn btn-secondary remainder-game-action-btn">
            {{ __('common.demo.try_again') }}
        </a>
    </div>
</section>
