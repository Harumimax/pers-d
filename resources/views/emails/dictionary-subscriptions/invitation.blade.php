<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    @foreach (['en', 'ru'] as $locale)
        @php
            $t = fn (string $key, array $replace = []) => \Illuminate\Support\Facades\Lang::get($key, $replace, $locale);
            $localeTitle = $locale === 'en' ? 'English' : 'Русский';
        @endphp

        <section style="{{ $loop->first ? '' : 'margin-top: 32px; padding-top: 24px; border-top: 1px solid #d1d5db;' }}">
            <p style="margin: 0 0 12px; font-size: 14px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #475569;">
                {{ $localeTitle }}
            </p>

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
        </section>
    @endforeach
</div>
