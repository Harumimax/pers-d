<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramRandomWordSession extends Model
{
    protected $fillable = [
        'telegram_setting_id',
        'position',
        'send_time',
        'translation_direction',
    ];

    public function telegramSetting(): BelongsTo
    {
        return $this->belongsTo(TelegramSetting::class);
    }

    public function userDictionaries(): BelongsToMany
    {
        return $this->belongsToMany(
            UserDictionary::class,
            'telegram_random_word_session_user_dictionary'
        );
    }

    public function readyDictionaries(): BelongsToMany
    {
        return $this->belongsToMany(
            ReadyDictionary::class,
            'telegram_random_word_session_ready_dictionary'
        );
    }

    public function partsOfSpeech(): HasMany
    {
        return $this->hasMany(TelegramRandomWordSessionPartOfSpeech::class);
    }

    public function gameRuns(): HasMany
    {
        return $this->hasMany(TelegramGameRun::class);
    }
}
