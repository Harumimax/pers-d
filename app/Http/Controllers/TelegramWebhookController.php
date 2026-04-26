<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramUpdateHandler $handler): Response
    {
        $configuredSecret = (string) config('services.telegram.webhook_secret');

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $secret)) {
            abort(403);
        }

        try {
            $handler->handle($request->json()->all());
        } catch (Throwable $exception) {
            report($exception);
        }

        return response('', Response::HTTP_OK);
    }
}
