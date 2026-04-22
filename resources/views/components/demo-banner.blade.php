@guest
    <section class="demo-banner" aria-label="{{ __('common.demo.banner_aria') }}">
        <div class="container demo-banner__inner">
            <div class="demo-banner__copy">
                <p class="demo-banner__title">{{ __('common.demo.banner_title') }}</p>
                <p class="demo-banner__text">{{ __('common.demo.banner_text') }}</p>
            </div>
            <a href="{{ route('register') }}" class="btn btn-secondary demo-banner__cta">
                {{ __('common.demo.create_account') }}
            </a>
        </div>
    </section>
@endguest
