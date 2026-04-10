<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    public const MODE_MANUAL = 'manual';
    public const DIRECTION_FOREIGN_TO_RU = 'foreign_to_ru';
    public const DIRECTION_RU_TO_FOREIGN = 'ru_to_foreign';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'user_id',
        'mode',
        'direction',
        'total_words',
        'correct_answers',
        'status',
        'started_at',
        'finished_at',
        'config_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'config_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GameSessionItem::class)->orderBy('order_index');
    }
}
