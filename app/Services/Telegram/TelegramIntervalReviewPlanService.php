<?php

namespace App\Services\Telegram;

use App\Data\Telegram\IntervalReviewPlanData;
use App\Models\TelegramIntervalReviewPlan;
use App\Models\TelegramIntervalReviewSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TelegramIntervalReviewPlanService
{
    public function __construct(
        private readonly TelegramIntervalReviewSchedulePreviewService $previewService,
    ) {
    }

    public function loadForUser(User $user): ?TelegramIntervalReviewPlan
    {
        return TelegramIntervalReviewPlan::query()
            ->where('user_id', $user->id)
            ->with(['words', 'sessions'])
            ->first();
    }

    public function save(User $user, IntervalReviewPlanData $data): TelegramIntervalReviewPlan
    {
        return DB::transaction(function () use ($user, $data): TelegramIntervalReviewPlan {
            $plan = TelegramIntervalReviewPlan::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => $data->enabled
                        ? TelegramIntervalReviewPlan::STATUS_ACTIVE
                        : TelegramIntervalReviewPlan::STATUS_PAUSED,
                    'language' => $data->language,
                    'start_time' => $data->startTime,
                    'timezone' => $data->timezone,
                    'words_count' => count($data->selectedWords),
                    'completed_sessions_count' => 0,
                    'completed_at' => null,
                ]
            );

            $plan->words()->delete();
            $plan->sessions()->delete();

            foreach (array_values($data->selectedWords) as $index => $word) {
                $plan->words()->create([
                    'source_type' => $word['source'],
                    'source_dictionary_id' => $word['dictionary_id'],
                    'source_word_id' => $word['word_id'],
                    'dictionary_name' => $word['dictionary_name'],
                    'language' => $word['language'],
                    'word' => $word['word'],
                    'translation' => $word['translation'],
                    'part_of_speech' => $word['part_of_speech'],
                    'comment' => $word['comment'],
                    'position' => $index + 1,
                ]);
            }

            $sessionStatus = $data->enabled
                ? TelegramIntervalReviewSession::STATUS_SCHEDULED
                : TelegramIntervalReviewSession::STATUS_PAUSED;

            foreach ($this->previewService->build($data->timezone, $data->startTime) as $session) {
                $plan->sessions()->create([
                    'session_number' => $session['session_number'],
                    'scheduled_for' => CarbonImmutable::parse($session['scheduled_at_iso'])->utc(),
                    'status' => $sessionStatus,
                ]);
            }

            return $plan->fresh(['words', 'sessions']);
        });
    }

    public function toggleStatus(User $user, bool $enabled): ?TelegramIntervalReviewPlan
    {
        return DB::transaction(function () use ($user, $enabled): ?TelegramIntervalReviewPlan {
            $plan = TelegramIntervalReviewPlan::query()
                ->where('user_id', $user->id)
                ->with(['words', 'sessions'])
                ->first();

            if (! $plan instanceof TelegramIntervalReviewPlan) {
                return null;
            }

            if ($plan->status === TelegramIntervalReviewPlan::STATUS_COMPLETED) {
                return $plan;
            }

            $planStatus = $enabled
                ? TelegramIntervalReviewPlan::STATUS_ACTIVE
                : TelegramIntervalReviewPlan::STATUS_PAUSED;
            $sessionStatus = $enabled
                ? TelegramIntervalReviewSession::STATUS_SCHEDULED
                : TelegramIntervalReviewSession::STATUS_PAUSED;

            $plan->forceFill(['status' => $planStatus])->save();
            $plan->sessions()
                ->whereIn('status', [
                    TelegramIntervalReviewSession::STATUS_SCHEDULED,
                    TelegramIntervalReviewSession::STATUS_PAUSED,
                ])
                ->update(['status' => $sessionStatus]);

            return $plan->fresh(['words', 'sessions']);
        });
    }

    public function reset(User $user): void
    {
        DB::transaction(function () use ($user): void {
            TelegramIntervalReviewPlan::query()
                ->where('user_id', $user->id)
                ->delete();
        });
    }
}
