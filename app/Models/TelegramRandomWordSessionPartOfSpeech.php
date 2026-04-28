<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramRandomWordSessionPartOfSpeech extends Model
{
    public $timestamps = false;

    protected $table = 'telegram_random_word_session_part_of_speech';

    protected $fillable = [
        'telegram_random_word_session_id',
        'part_of_speech',
    ];
}
