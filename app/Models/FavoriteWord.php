<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteWord extends Model
{
    public const SOURCE_DICTIONARY_USER = 'user_dictionary';
    public const SOURCE_DICTIONARY_READY = 'ready_dictionary';
    public const SOURCE_WORD_USER = 'word';
    public const SOURCE_WORD_READY = 'ready_dictionary_word';

    protected $fillable = [
        'user_id',
        'source_dictionary_type',
        'source_dictionary_id',
        'source_word_type',
        'source_word_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceUserDictionary(): BelongsTo
    {
        return $this->belongsTo(UserDictionary::class, 'source_dictionary_id');
    }

    public function sourceReadyDictionary(): BelongsTo
    {
        return $this->belongsTo(ReadyDictionary::class, 'source_dictionary_id');
    }

    public function sourceWord(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'source_word_id');
    }

    public function sourceReadyDictionaryWord(): BelongsTo
    {
        return $this->belongsTo(ReadyDictionaryWord::class, 'source_word_id');
    }

    public function isUserDictionaryFavorite(): bool
    {
        return $this->source_dictionary_type === self::SOURCE_DICTIONARY_USER
            && $this->source_word_type === self::SOURCE_WORD_USER;
    }

    public function isReadyDictionaryFavorite(): bool
    {
        return $this->source_dictionary_type === self::SOURCE_DICTIONARY_READY
            && $this->source_word_type === self::SOURCE_WORD_READY;
    }
}
