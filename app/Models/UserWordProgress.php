<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWordProgress extends Model
{
    protected $table = 'user_word_progress';

    protected $fillable = [
        'user_id',
        'word_id',
        'remainder_had_mistake',
    ];

    protected function casts(): array
    {
        return [
            'remainder_had_mistake' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }
}
