<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WordExample extends Model
{
    protected $fillable = [
        'example_text',
        'example_translation',
        'sort_order',
        'source',
        'source_external_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function exampleable(): MorphTo
    {
        return $this->morphTo();
    }
}
