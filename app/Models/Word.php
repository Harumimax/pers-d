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
    ];

    public function dictionaries(): BelongsToMany
    {
        return $this->belongsToMany(
            UserDictionary::class,
            'user_dictionary_word',
            'word_id',
            'user_dictionary_id'
        )
            ->withTimestamps();
    }
}
