<?php

namespace App\Models;

use Database\Factories\ReadyDictionaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadyDictionary extends Model
{
    /** @use HasFactory<ReadyDictionaryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'language',
        'level',
        'part_of_speech',
        'comment',
    ];

    public function words(): HasMany
    {
        return $this->hasMany(ReadyDictionaryWord::class);
    }
}
