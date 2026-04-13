<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <div class="auth-field">
            <x-input-label for="name" :value="__('auth.register.name')" class="auth-label" />
            <x-text-input
                id="name"
                class="auth-input mt-2 w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error :messages="$errors->get('name')" class="auth-error mt-2" />
        </div>

        <div class="auth-field">
            <x-input-label for="email" :value="__('auth.register.email')" class="auth-label" />
            <x-text-input
                id="email"
                class="auth-input mt-2 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="auth-error mt-2" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" :value="__('auth.register.password')" class="auth-label" />
            <x-text-input
                id="password"
                class="auth-input mt-2 w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="auth-error mt-2" />
        </div>

        <div class="auth-field">
            <x-input-label for="password_confirmation" :value="__('auth.register.password_confirmation')" class="auth-label" />
            <x-text-input
                id="password_confirmation"
                class="auth-input mt-2 w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error mt-2" />
        </div>

        <div class="auth-actions">
            <a class="auth-link" href="{{ route('login') }}">
                {{ __('auth.register.already_registered') }}
            </a>

            <button type="submit" class="btn btn-primary btn-large auth-submit-btn">
                {{ __('auth.register.submit') }}
            </button>
        </div>
    </form>
</x-guest-layout>
