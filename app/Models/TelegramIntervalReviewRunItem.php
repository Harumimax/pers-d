<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramIntervalReviewRunItem extends Model
{
    protected $fillable = [
        'telegram_interval_review_run_id',
        'telegram_interval_review_plan_word_id',
        'order_index',
        'word_snapshot',
        'translation_snapshot',
        'part_of_speech_snapshot',
        'comment_snapshot',
        'prompt_text',
        'correct_answer',
        'source_type_snapshot',
        'options_json',
        'user_answer',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'options_json' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'immutable_datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(TelegramIntervalReviewRun::class, 'telegram_interval_review_run_id');
    }

    public function planWord(): BelongsTo
    {
        return $this->belongsTo(TelegramIntervalReviewPlanWord::class, 'telegram_interval_review_plan_word_id');
    }
}
