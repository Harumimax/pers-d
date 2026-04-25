<?php

namespace App\Notifications\Messages;

class NotiSendMessage
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $text,
        public readonly string $html,
        public readonly ?string $replyTo = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [
            'from_email' => (string) config('services.notisend.from_email'),
            'from_name' => (string) config('services.notisend.from_name'),
            'to' => $this->to,
            'subject' => $this->subject,
            'text' => $this->text,
            'html' => $this->html,
        ];

        if ($this->replyTo !== null && trim($this->replyTo) !== '') {
            $payload['smtp_headers'] = [
                'Reply-To' => $this->replyTo,
            ];
        }

        return $payload;
    }
}
