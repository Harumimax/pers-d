<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutContactMessage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'contact_email',
        'subject',
        'message',
        'delivery_status',
        'delivered_at',
        'delivery_error',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }
}
