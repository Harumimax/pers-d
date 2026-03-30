<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Word extends Model
{
    protected $fillable = [
        'word',
        'translation',
        'comment',
        'source_language',
    ];

    public function dictionaries(): BelongsToMany
    {
        return $this->belongsToMany(UserDictionary::class, 'user_dictionary_word')
            ->withTimestamps();
    }
}
