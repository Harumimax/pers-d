<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDictionary extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'language',
    ];

    public function user(): BelongsTo
    {
        return $this->owner();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function words(): BelongsToMany
    {
        return $this->belongsToMany(
            Word::class,
            'user_dictionary_word',
            'user_dictionary_id',
            'word_id'
        )
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(DictionarySubscription::class, 'user_dictionary_id');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'dictionary_subscriptions',
            'user_dictionary_id',
            'subscriber_user_id'
        )
            ->withTimestamps();
    }

    public function shareInvitations(): HasMany
    {
        return $this->hasMany(DictionaryShareInvitation::class, 'user_dictionary_id');
    }
}
