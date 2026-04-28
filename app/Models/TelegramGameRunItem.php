<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramGameRunItem extends Model
{
    protected $fillable = [
        'telegram_game_run_id',
        'word_id',
        'order_index',
        'prompt_text',
        'part_of_speech_snapshot',
        'correct_answer',
        'source_type_snapshot',
        'options_json',
        'user_answer',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function telegramGameRun(): BelongsTo
    {
        return $this->belongsTo(TelegramGameRun::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }
}
