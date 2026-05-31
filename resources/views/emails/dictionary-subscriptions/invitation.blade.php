@php
    $t = fn (string $key, array $replace = []) => \Illuminate\Support\Facades\Lang::get($key, $replace, $locale);
@endphp

<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <p>{{ $t('dictionary-subscriptions.email.greeting') }}</p>

    <p>
        @if ($hasExistingAccount)
            {{ $t('dictionary-subscriptions.email.existing.intro', ['owner' => $ownerEmail, 'dictionary' => $dictionaryName]) }}
        @else
            {{ $t('dictionary-subscriptions.email.new_user.intro', ['owner' => $ownerEmail, 'dictionary' => $dictionaryName]) }}
        @endif
    </p>

    @if (! $hasExistingAccount)
        <p>{{ $t('dictionary-subscriptions.email.new_user.register_hint') }}</p>
        <p><a href="{{ $registerUrl }}">{{ $registerUrl }}</a></p>
    @endif

    <p>{{ $t('dictionary-subscriptions.email.link_hint') }}</p>
    <p><a href="{{ $invitationUrl }}">{{ $invitationUrl }}</a></p>

    <p>{{ $t('dictionary-subscriptions.email.ignore') }}</p>
</div>
