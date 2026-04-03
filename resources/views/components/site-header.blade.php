@props([
    'navClass' => 'header-actions',
    'label' => 'Main navigation',
])

<header class="site-header">
    <div class="container header-inner">
        <a href="{{ url('/') }}" class="logo">WordKeeper</a>

        <nav class="{{ $navClass }}" aria-label="{{ $label }}">
            {{ $slot }}
        </nav>
    </div>
</header>
