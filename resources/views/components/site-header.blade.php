@props([
    'navClass' => 'header-actions',
    'label' => 'Main navigation',
    'mobileLabel' => 'Mobile navigation',
])

<header class="site-header" x-data="{ mobileMenuOpen: false }" @keydown.escape.window="mobileMenuOpen = false">
    <div class="container header-inner">
        <a href="{{ url('/') }}" class="logo" aria-label="WordKeeper home">
            <img
                src="{{ asset('images/wordkeeper-logo.jpg') }}"
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
                x-on:click="mobileMenuOpen = !mobileMenuOpen"
                x-bind:aria-expanded="mobileMenuOpen.toString()"
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
            x-show="mobileMenuOpen"
            x-cloak
            x-on:click.self="mobileMenuOpen = false"
        >
            <nav class="container site-header__mobile-drawer" aria-label="{{ $mobileLabel }}">
                {{ $mobile }}
            </nav>
        </div>
    @endisset
</header>
