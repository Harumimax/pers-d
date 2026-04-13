<x-guest-layout>
    <x-auth-session-status class="auth-status" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <x-input-label for="email" :value="__('auth.login.email')" class="auth-label" />
            <x-text-input
                id="email"
                class="auth-input mt-2 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="auth-error mt-2" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" :value="__('auth.login.password')" class="auth-label" />
            <x-text-input
                id="password"
                class="auth-input mt-2 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="auth-error mt-2" />
        </div>

        <label for="remember_me" class="auth-remember">
            <input id="remember_me" type="checkbox" name="remember" class="auth-checkbox">
            <span>{{ __('auth.login.remember') }}</span>
        </label>

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
