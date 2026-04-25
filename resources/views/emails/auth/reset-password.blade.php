<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('auth.reset_password_mail.subject') }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; font-family: Arial, sans-serif;">
    <div style="max-width: 720px; margin: 0 auto; background: #ffffff; border: 1px solid #d8dce4; border-radius: 16px; padding: 28px;">
        <h1 style="margin: 0 0 20px; font-size: 24px; line-height: 1.2;">{{ __('auth.reset_password_mail.subject') }}</h1>

        <p style="margin: 0 0 14px; font-size: 15px; line-height: 1.7;">
            {{ __('auth.reset_password_mail.intro') }}
        </p>

        <p style="margin: 0 0 20px;">
            <a
                href="{{ $resetUrl }}"
                style="display: inline-block; padding: 12px 20px; border-radius: 12px; background: #2f4a9e; color: #ffffff; text-decoration: none; font-weight: 700;"
            >
                {{ __('auth.reset_password_mail.action_label') }}
            </a>
        </p>

        <p style="margin: 0 0 14px; font-size: 15px; line-height: 1.7;">
            {{ __('auth.reset_password_mail.expire', ['count' => $expireMinutes]) }}
        </p>

        <p style="margin: 0; font-size: 15px; line-height: 1.7;">
            {{ __('auth.reset_password_mail.outro') }}
        </p>
    </div>
</body>
</html>
