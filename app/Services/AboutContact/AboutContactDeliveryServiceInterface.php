<?php

namespace App\Services\AboutContact;

use App\Models\AboutContactMessage;

interface AboutContactDeliveryServiceInterface
{
    public function send(AboutContactMessage $contactMessage, string $locale): void;
}
