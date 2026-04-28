<?php

namespace App\Services\Telegram;

use App\Models\TelegramRandomWordSession;
use App\Models\TelegramSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TelegramScheduledSessionLocator
{
    /**
     * @return Collection<int, array{setting:TelegramSetting,session:TelegramRandomWordSession,scheduled_for:CarbonImmutable}>
     */
    public function dueSessions(?CarbonImmutable $nowUtc = null): Collection
    {
        $nowUtc ??= CarbonImmutable::now('UTC');

        return TelegramSetting::query()
            ->where('random_words_enabled', true)
            ->whereHas('user', fn ($query) => $query->whereNotNull('tg_chat_id'))
            ->with([
                'user',
                'randomWordSessions.partsOfSpeech',
                'randomWordSessions.userDictionaries',
                'randomWordSessions.readyDictionaries',
            ])
            ->get()
            ->flatMap(function (TelegramSetting $setting) use ($nowUtc): Collection {
                $timezone = (string) $setting->timezone;
                $localNow = $nowUtc->setTimezone($timezone);

                return $setting->randomWordSessions
                    ->map(function (TelegramRandomWordSession $session) use ($localNow, $timezone, $setting): ?array {
                        $scheduledLocal = CarbonImmutable::today($timezone)
                            ->setTimeFromTimeString((string) $session->send_time);

                        if ($scheduledLocal->format('H:i') !== $localNow->format('H:i')) {
                            return null;
                        }

                        return [
                            'setting' => $setting,
                            'session' => $session,
                            'scheduled_for' => $scheduledLocal->utc(),
                        ];
                    })
                    ->filter();
            })
            ->values();
    }
}
