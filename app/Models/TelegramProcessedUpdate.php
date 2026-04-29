<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramProcessedUpdate extends Model
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'telegram_update_id',
        'callback_query_id',
        'chat_id',
        'update_type',
        'status',
        'attempts',
        'last_error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'telegram_update_id' => 'integer',
            'attempts' => 'integer',
            'processed_at' => 'datetime',
        ];
    }
}
