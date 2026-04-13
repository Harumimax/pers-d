<section class="profile-section">
    <header class="profile-section__header">
        <h2 class="profile-section__title">{{ __('profile.personal_information.title') }}</h2>
        <p class="profile-section__description">
            {{ __('profile.personal_information.description') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="profile-form">
        @csrf
        @method('patch')

        <div class="profile-field">
            <label for="name" class="profile-label">{{ __('profile.personal_information.name') }}</label>
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
            <label for="email" class="profile-label">{{ __('profile.personal_information.email') }}</label>
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
                        {{ __('profile.personal_information.unverified') }}

                        <button form="send-verification" class="profile-inline-link" type="submit">
                            {{ __('profile.personal_information.resend_verification') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="profile-success">
                            {{ __('profile.personal_information.verification_sent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="profile-actions">
            <button type="submit" class="btn btn-primary profile-submit-btn">{{ __('profile.personal_information.save') }}</button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="profile-muted-status"
                >{{ __('profile.personal_information.saved') }}</p>
            @endif
        </div>
    </form>
</section>
