<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramIntervalReviewSession extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PAUSED = 'paused';

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
}
