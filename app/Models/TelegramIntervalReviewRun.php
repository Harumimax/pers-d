<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramIntervalReviewRun extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_AWAITING_START = 'awaiting_start';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'telegram_interval_review_plan_id',
        'telegram_interval_review_session_id',
        'session_number',
        'total_words',
        'status',
        'scheduled_for',
        'intro_message_sent_at',
        'intro_message_id',
        'word_list_message_id',
        'started_at',
        'cancelled_at',
        'finished_at',
        'last_interaction_at',
        'last_error_code',
        'last_error_message',
        'last_error_at',
        'config_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'session_number' => 'integer',
            'total_words' => 'integer',
            'scheduled_for' => 'immutable_datetime',
            'intro_message_sent_at' => 'immutable_datetime',
            'word_list_message_id' => 'integer',
            'started_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'last_interaction_at' => 'immutable_datetime',
            'last_error_at' => 'immutable_datetime',
            'config_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TelegramIntervalReviewPlan::class, 'telegram_interval_review_plan_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TelegramIntervalReviewSession::class, 'telegram_interval_review_session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TelegramIntervalReviewRunItem::class)
            ->orderBy('order_index');
    }
}
