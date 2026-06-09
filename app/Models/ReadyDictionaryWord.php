<?php

namespace App\Models;

use Database\Factories\ReadyDictionaryWordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadyDictionaryWord extends Model
{
    /** @use HasFactory<ReadyDictionaryWordFactory> */
    use HasFactory;

    protected $fillable = [
        'ready_dictionary_id',
        'word',
        'translation',
        'part_of_speech',
        'comment',
    ];

    public function readyDictionary(): BelongsTo
    {
        return $this->belongsTo(ReadyDictionary::class);
    }

    public function favoriteMarks(): HasMany
    {
        return $this->hasMany(FavoriteWord::class, 'source_word_id')
            ->where('source_word_type', FavoriteWord::SOURCE_WORD_READY);
    }
}
