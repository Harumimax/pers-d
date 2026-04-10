@props([
    'navClass' => 'header-actions',
    'label' => 'Main navigation',
])

<header class="site-header">
    <div class="container header-inner">
        <a href="{{ url('/') }}" class="logo" aria-label="WordKeeper home">
            <img
                src="{{ asset('images/wordkeeper-logo.jpg') }}"
                alt="WordKeeper"
                class="logo__image"
            >
        </a>

        <nav class="{{ $navClass }}" aria-label="{{ $label }}">
            {{ $slot }}
        </nav>
    </div>
</header>
