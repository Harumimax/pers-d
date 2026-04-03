<section class="profile-section">
    <header class="profile-section__header">
        <h2 class="profile-section__title">{{ __('Delete Account') }}</h2>
        <p class="profile-section__description">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button
        type="button"
        class="profile-delete-trigger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="profile-modal-form">
            @csrf
            @method('delete')

            <h2 class="profile-modal-title">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="profile-modal-text">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="profile-field">
                <label for="password" class="profile-label sr-only">{{ __('Password') }}</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="profile-input"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="profile-error" />
            </div>

            <div class="profile-modal-actions">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" class="profile-danger-btn">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
