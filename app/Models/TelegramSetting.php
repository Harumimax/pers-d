<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramSetting extends Model
{
    protected $fillable = [
        'user_id',
        'timezone',
        'random_words_enabled',
    ];

    protected $casts = [
        'random_words_enabled' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function randomWordSessions(): HasMany
    {
        return $this->hasMany(TelegramRandomWordSession::class)
            ->orderBy('position');
    }
}
