<section class="profile-section">
    <header class="profile-section__header">
        <h2 class="profile-section__title">{{ __('profile.delete.title') }}</h2>
        <p class="profile-section__description">
            {{ __('profile.delete.description') }}
        </p>
    </header>

    <button
        type="button"
        class="profile-delete-trigger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('profile.delete.trigger') }}</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="profile-modal-form">
            @csrf
            @method('delete')

            <h2 class="profile-modal-title">
                {{ __('profile.delete.confirm_title') }}
            </h2>

            <p class="profile-modal-text">
                {{ __('profile.delete.confirm_text') }}
            </p>

            <div class="profile-field">
                <label for="password" class="profile-label sr-only">{{ __('profile.delete.password_placeholder') }}</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="profile-input"
                    placeholder="{{ __('profile.delete.password_placeholder') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="profile-error" />
            </div>

            <div class="profile-modal-actions">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">
                    {{ __('profile.delete.cancel') }}
                </button>

                <button type="submit" class="profile-danger-btn">
                    {{ __('profile.delete.confirm') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
