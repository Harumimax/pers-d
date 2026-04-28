<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramGameRun extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_AWAITING_START = 'awaiting_start';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'telegram_setting_id',
        'telegram_random_word_session_id',
        'mode',
        'direction',
        'total_words',
        'correct_answers',
        'incorrect_answers',
        'status',
        'scheduled_for',
        'intro_message_sent_at',
        'intro_message_id',
        'started_at',
        'finished_at',
        'cancelled_at',
        'config_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total_words' => 'integer',
            'correct_answers' => 'integer',
            'incorrect_answers' => 'integer',
            'scheduled_for' => 'datetime',
            'intro_message_sent_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'config_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramSetting(): BelongsTo
    {
        return $this->belongsTo(TelegramSetting::class);
    }

    public function telegramRandomWordSession(): BelongsTo
    {
        return $this->belongsTo(TelegramRandomWordSession::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TelegramGameRunItem::class)->orderBy('order_index');
    }
}
