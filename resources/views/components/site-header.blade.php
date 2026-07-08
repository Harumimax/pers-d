@props([
    'navClass' => 'header-actions',
    'label' => 'Main navigation',
    'mobileLabel' => 'Mobile navigation',
])

<header class="site-header" data-site-header>
    <div class="container header-inner">
        <a href="{{ url('/') }}" class="logo" aria-label="WordKeeper home">
            <img
                src="{{ asset('images/wordkeeper-logo2.jpg') }}"
                alt="WordKeeper"
                class="logo__image"
            >
        </a>

        <div class="site-header__desktop-group">
            <nav class="{{ $navClass }} site-header__desktop-nav" aria-label="{{ $label }}">
                {{ $slot }}
            </nav>

            @isset($utility)
                <div class="site-header__utility">
                    {{ $utility }}
                </div>
            @endisset

            <button
                type="button"
                class="site-header__burger"
                data-site-header-toggle
                aria-expanded="false"
                aria-controls="site-mobile-drawer"
                aria-label="{{ __('common.navigation.toggle') }}"
            >
                <span class="sr-only">{{ __('common.navigation.toggle') }}</span>
                <span class="site-header__burger-line"></span>
                <span class="site-header__burger-line"></span>
                <span class="site-header__burger-line"></span>
            </button>
        </div>
    </div>

    @isset($mobile)
        <div
            id="site-mobile-drawer"
            class="site-header__mobile-shell"
            data-site-header-drawer
            hidden
        >
            <nav class="container site-header__mobile-drawer" aria-label="{{ $mobileLabel }}">
                {{ $mobile }}
            </nav>
        </div>
    @endisset
</header>
