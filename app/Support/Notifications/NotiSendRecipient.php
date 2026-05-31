<?php

namespace App\Support\Notifications;

use Illuminate\Notifications\Notifiable;

class NotiSendRecipient
{
    use Notifiable;

    public function __construct(
        public readonly string $email,
        public readonly string $locale,
    ) {
    }

    public function preferredLocaleOrDefault(): string
    {
        return trim($this->locale) !== '' ? $this->locale : (string) app()->getLocale();
    }

    public function getKey(): string
    {
        return $this->email;
    }
}
