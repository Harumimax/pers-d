<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramIntervalReviewPlan extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'user_id',
        'status',
        'language',
        'start_time',
        'timezone',
        'words_count',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'string',
            'words_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function words(): HasMany
    {
        return $this->hasMany(TelegramIntervalReviewPlanWord::class)
            ->orderBy('position');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TelegramIntervalReviewSession::class)
            ->orderBy('session_number');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TelegramIntervalReviewRun::class)
            ->orderByDesc('scheduled_for');
    }
}
