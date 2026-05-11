<x-guest-layout>
    <div class="auth-intro">
        <h1 class="auth-title">{{ __('auth.telegram_link.title') }}</h1>
        <p class="auth-copy">{{ __('auth.telegram_link.description') }}</p>
        <p class="auth-copy">
            {{ __('auth.telegram_link.account_label') }}
            <strong>{{ $email }}</strong>
        </p>
    </div>

    <x-auth-session-status class="auth-status" :status="session('status')" />

    <form method="POST" action="{{ route('telegram-auth.store', ['token' => $token]) }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <x-input-label for="password" :value="__('auth.login.password')" class="auth-label" />
            <x-text-input
                id="password"
                class="auth-input mt-2 w-full"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="auth-error mt-2" />
        </div>

        <div class="auth-actions">
            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">
                    {{ __('auth.login.forgot_password') }}
                </a>
            @endif

            <button type="submit" class="btn btn-primary btn-large auth-submit-btn">
                {{ __('auth.login.submit') }}
            </button>
        </div>
    </form>
</x-guest-layout>
