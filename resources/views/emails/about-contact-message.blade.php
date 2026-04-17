<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('about.contact.mail.title') }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; font-family: Arial, sans-serif;">
    <div style="max-width: 720px; margin: 0 auto; background: #ffffff; border: 1px solid #d8dce4; border-radius: 16px; padding: 28px;">
        <h1 style="margin: 0 0 20px; font-size: 24px; line-height: 1.2;">{{ __('about.contact.mail.title') }}</h1>

        <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6;">
            <strong>{{ __('about.contact.email') }}:</strong>
            {{ $contactMessage->contact_email }}
        </p>

        <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6;">
            <strong>{{ __('about.contact.subject') }}:</strong>
            {{ $contactMessage->subject }}
        </p>

        <div style="margin-top: 20px;">
            <p style="margin: 0 0 10px; font-size: 15px; line-height: 1.6;">
                <strong>{{ __('about.contact.message') }}:</strong>
            </p>

            <div style="padding: 18px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">{{ $contactMessage->message }}</div>
        </div>
    </div>
</body>
</html>
