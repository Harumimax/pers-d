<x-guest-layout>
    <div class="auth-intro">
        <h1 class="auth-title">{{ $title }}</h1>
        <p class="auth-copy">{{ $message }}</p>
    </div>

    <div class="auth-actions">
        <a href="{{ route('login') }}" class="btn {{ $isSuccess ? 'btn-primary' : 'btn-secondary' }} btn-large auth-submit-btn">
            {{ __('auth.telegram_link.back_to_site') }}
        </a>
    </div>
</x-guest-layout>
