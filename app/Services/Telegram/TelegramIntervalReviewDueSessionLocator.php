<?php

namespace App\Services\Telegram;

use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TelegramIntervalReviewDueSessionLocator
{
    /**
     * @return Collection<int, TelegramIntervalReviewSession>
     */
    public function dueSessions(?CarbonImmutable $nowUtc = null): Collection
    {
        $nowUtc ??= CarbonImmutable::now('UTC');
        $minuteStart = $nowUtc->startOfMinute();
        $minuteEnd = $minuteStart->endOfMinute();

        return TelegramIntervalReviewSession::query()
            ->whereBetween('scheduled_for', [$minuteStart, $minuteEnd])
            ->where('status', TelegramIntervalReviewSession::STATUS_SCHEDULED)
            ->whereHas('plan', function ($query): void {
                $query->where('status', TelegramIntervalReviewPlan::STATUS_ACTIVE)
                    ->whereHas('user', fn ($userQuery) => $userQuery->whereNotNull('tg_chat_id'));
            })
            ->with(['plan.user', 'plan.words'])
            ->orderBy('scheduled_for')
            ->orderBy('session_number')
            ->get();
    }
}
