<?php

namespace App\Models;

use Database\Factories\ReadyDictionaryWordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
