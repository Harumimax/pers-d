<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Word extends Model
{
    protected $fillable = [
        'word',
        'part_of_speech',
        'translation',
        'comment',
        'remainder_had_mistake',
    ];

    protected function casts(): array
    {
        return [
            'remainder_had_mistake' => 'boolean',
            'user_remainder_had_mistake' => 'boolean',
        ];
    }

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

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserWordProgress::class);
    }

    public function scopeWithProgressForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? (int) $user->getKey() : (int) $user;

        return $query
            ->leftJoin('user_word_progress as current_user_word_progress', function ($join) use ($userId): void {
                $join->on('current_user_word_progress.word_id', '=', 'words.id')
                    ->where('current_user_word_progress.user_id', '=', $userId);
            })
            ->addSelect('words.*')
            ->selectRaw('COALESCE(current_user_word_progress.remainder_had_mistake, false) as user_remainder_had_mistake');
    }

    protected function remainderHadMistake(): Attribute
    {
        return Attribute::get(function ($value, array $attributes): bool {
            if (array_key_exists('user_remainder_had_mistake', $attributes)) {
                return (bool) $attributes['user_remainder_had_mistake'];
            }

            return (bool) ($attributes['remainder_had_mistake'] ?? false);
        });
    }
}
