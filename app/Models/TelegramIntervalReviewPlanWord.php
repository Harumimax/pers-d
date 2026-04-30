<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramIntervalReviewPlanWord extends Model
{
    protected $fillable = [
        'telegram_interval_review_plan_id',
        'source_type',
        'source_dictionary_id',
        'source_word_id',
        'dictionary_name',
        'language',
        'word',
        'translation',
        'part_of_speech',
        'comment',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'source_dictionary_id' => 'integer',
            'source_word_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TelegramIntervalReviewPlan::class, 'telegram_interval_review_plan_id');
    }
}
