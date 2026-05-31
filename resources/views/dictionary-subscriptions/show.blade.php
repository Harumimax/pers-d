<x-guest-layout>
    <div class="auth-form">
        <div class="auth-field">
            <h1 class="auth-label" style="font-size: 1.4rem; font-weight: 700;">
                {{ __('dictionary-subscriptions.page.title') }}
            </h1>

            @if ($errors->has('invitation'))
                <p class="auth-error mt-2">{{ $errors->first('invitation') }}</p>
            @endif

            @if ($state === 'invalid')
                <p class="auth-copy mt-2">{{ __('dictionary-subscriptions.page.invalid') }}</p>
            @elseif ($state === 'expired')
                <p class="auth-copy mt-2">{{ __('dictionary-subscriptions.page.expired') }}</p>
            @elseif ($state === 'accepted')
                <p class="auth-copy mt-2">
                    {{ __('dictionary-subscriptions.page.accepted', ['dictionary' => $invitation?->dictionary?->name]) }}
                </p>
                <div class="auth-actions">
                    <a class="btn btn-primary btn-large auth-submit-btn" href="{{ route('dictionaries.index') }}">
                        {{ __('dictionary-subscriptions.page.open_dictionaries') }}
                    </a>
                </div>
            @elseif ($state === 'email_mismatch')
                <p class="auth-copy mt-2">
                    {{ __('dictionary-subscriptions.page.email_mismatch', ['email' => $invitation?->target_email]) }}
                </p>
            @elseif ($state === 'guest')
                <p class="auth-copy mt-2">
                    {{ __('dictionary-subscriptions.page.guest_intro', [
                        'dictionary' => $invitation?->dictionary?->name,
                        'owner' => $invitation?->owner?->email,
                    ]) }}
                </p>

                <div class="auth-actions">
                    <a class="btn btn-primary btn-large auth-submit-btn" href="{{ route('login') }}">
                        {{ __('dictionary-subscriptions.page.login') }}
                    </a>
                    <a class="btn btn-secondary btn-large auth-submit-btn" href="{{ route('register') }}">
                        {{ __('dictionary-subscriptions.page.register') }}
                    </a>
                </div>
            @elseif ($state === 'ready_to_accept')
                <p class="auth-copy mt-2">
                    {{ __('dictionary-subscriptions.page.ready_to_accept', [
                        'dictionary' => $invitation?->dictionary?->name,
                        'owner' => $invitation?->owner?->email,
                    ]) }}
                </p>

                <form method="POST" action="{{ route('dictionary-subscriptions.accept', ['token' => $token]) }}" class="auth-actions">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-large auth-submit-btn">
                        {{ __('dictionary-subscriptions.page.accept_button') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-guest-layout>
