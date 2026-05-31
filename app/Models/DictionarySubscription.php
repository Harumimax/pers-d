<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictionarySubscription extends Model
{
    protected $fillable = [
        'user_dictionary_id',
        'subscriber_user_id',
    ];

    public function dictionary(): BelongsTo
    {
        return $this->belongsTo(UserDictionary::class, 'user_dictionary_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_user_id');
    }
}
