<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutContactMessage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const ERROR_DISPATCH_FAILED = 'dispatch_failed';
    public const ERROR_MAIL_TRANSPORT_FAILED = 'mail_transport_failed';

    protected $fillable = [
        'contact_email',
        'subject',
        'message',
        'delivery_status',
        'delivered_at',
        'delivery_error',
        'delivery_error_message',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }
}
