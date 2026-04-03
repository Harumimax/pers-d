<section class="profile-section">
    <header class="profile-section__header">
        <h2 class="profile-section__title">{{ __('Change Password') }}</h2>
        <p class="profile-section__description">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="profile-form">
        @csrf
        @method('put')

        <div class="profile-field">
            <label for="update_password_current_password" class="profile-label">{{ __('Current Password') }}</label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="profile-input"
                autocomplete="current-password"
            >
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="profile-error" />
        </div>

        <div class="profile-field">
            <label for="update_password_password" class="profile-label">{{ __('New Password') }}</label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                class="profile-input"
                autocomplete="new-password"
            >
            <x-input-error :messages="$errors->updatePassword->get('password')" class="profile-error" />
        </div>

        <div class="profile-field">
            <label for="update_password_password_confirmation" class="profile-label">{{ __('Confirm Password') }}</label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="profile-input"
                autocomplete="new-password"
            >
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="profile-error" />
        </div>

        <div class="profile-actions">
            <button type="submit" class="btn btn-primary profile-submit-btn">{{ __('Save Changes') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="profile-muted-status"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
