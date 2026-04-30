<?php

namespace App\Services\Telegram;

use Carbon\CarbonImmutable;

class TelegramIntervalReviewSchedulePreviewService
{
    /**
     * @return array<int, array{session_number:int,label:string,scheduled_at_local:string,scheduled_at_iso:string}>
     */
    public function build(string $timezone, string $startTime, ?CarbonImmutable $nowUtc = null): array
    {
        $nowUtc ??= CarbonImmutable::now('UTC');
        $localNow = $nowUtc->setTimezone($timezone);

        $firstSession = CarbonImmutable::today($timezone)
            ->setTimeFromTimeString($this->normalizeTime($startTime));

        if ($firstSession->lessThanOrEqualTo($localNow)) {
            $firstSession = $firstSession->addDay();
        }

        $sessions = [
            1 => $firstSession,
            2 => $firstSession->addHours(6),
            3 => $firstSession->addDay()->setTimeFromTimeString($this->normalizeTime($startTime)),
            4 => $firstSession->addDays(3)->setTimeFromTimeString($this->normalizeTime($startTime)),
            5 => $firstSession->addDays(7)->setTimeFromTimeString($this->normalizeTime($startTime)),
            6 => $firstSession->addDays(14)->setTimeFromTimeString($this->normalizeTime($startTime)),
        ];

        return collect($sessions)
            ->map(fn (CarbonImmutable $scheduledAt, int $sessionNumber): array => [
                'session_number' => $sessionNumber,
                'label' => $this->sessionLabel($sessionNumber),
                'scheduled_at_local' => $scheduledAt->translatedFormat('d.m.Y H:i'),
                'scheduled_at_iso' => $scheduledAt->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function normalizeTime(string $time): string
    {
        $normalized = trim($time);

        return preg_match('/^\d{2}:\d{2}$/', $normalized) === 1
            ? $normalized.':00'
            : '09:00:00';
    }

    private function sessionLabel(int $sessionNumber): string
    {
        return __('tg-bot.interval_review.preview.session_label', ['number' => $sessionNumber]);
    }
}
