<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramIntervalReviewSession extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_AWAITING_START = 'awaiting_start';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'telegram_interval_review_plan_id',
        'session_number',
        'scheduled_for',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_number' => 'integer',
            'scheduled_for' => 'immutable_datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TelegramIntervalReviewPlan::class, 'telegram_interval_review_plan_id');
    }

    public function runs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TelegramIntervalReviewRun::class, 'telegram_interval_review_session_id');
    }
}
