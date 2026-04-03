<section class="profile-section">
    <header class="profile-section__header">
        <h2 class="profile-section__title">{{ __('Personal Information') }}</h2>
        <p class="profile-section__description">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="profile-form">
        @csrf
        @method('patch')

        <div class="profile-field">
            <label for="name" class="profile-label">{{ __('Name') }}</label>
            <input
                id="name"
                name="name"
                type="text"
                class="profile-input"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
            >
            <x-input-error class="profile-error" :messages="$errors->get('name')" />
        </div>

        <div class="profile-field">
            <label for="email" class="profile-label">{{ __('Email') }}</label>
            <input
                id="email"
                name="email"
                type="email"
                class="profile-input"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
            >
            <x-input-error class="profile-error" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="profile-inline-status">
                    <p>
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="profile-inline-link" type="submit">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="profile-success">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="profile-actions">
            <button type="submit" class="btn btn-primary profile-submit-btn">{{ __('Save Changes') }}</button>

            @if (session('status') === 'profile-updated')
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
