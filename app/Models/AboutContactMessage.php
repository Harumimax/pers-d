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
    public const ERROR_API_TRANSPORT_FAILED = 'api_transport_failed';
    public const ERROR_API_REQUEST_FAILED = 'api_request_failed';
    public const ERROR_API_AUTH_FAILED = 'api_auth_failed';
    public const ERROR_API_BALANCE_INSUFFICIENT = 'api_balance_insufficient';
    public const ERROR_API_UNPROCESSABLE = 'api_unprocessable';
    public const ERROR_API_RATE_LIMITED = 'api_rate_limited';
    public const ERROR_API_SERVICE_UNAVAILABLE = 'api_service_unavailable';

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
