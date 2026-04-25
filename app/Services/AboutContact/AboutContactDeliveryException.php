<?php

namespace App\Services\AboutContact;

use RuntimeException;

class AboutContactDeliveryException extends RuntimeException
{
    public function __construct(
        public readonly string $deliveryErrorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
