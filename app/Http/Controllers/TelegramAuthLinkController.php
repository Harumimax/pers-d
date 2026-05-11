<?php

namespace App\Http\Controllers;

use App\Http\Requests\Telegram\ConsumeTelegramLoginIntentRequest;
use App\Services\Telegram\TelegramLoginIntentService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class TelegramAuthLinkController extends Controller
{
    public function __construct(
        private readonly TelegramLoginIntentService $telegramLoginIntentService,
    ) {
    }

    public function show(string $token): View|Response
    {
        $intent = $this->telegramLoginIntentService->findValidByToken($token);

        if ($intent === null) {
            return response()->view('auth.telegram-link-status', [
                'title' => __('auth.telegram_link.invalid_title'),
                'message' => __('auth.telegram_link.invalid_message'),
                'isSuccess' => false,
            ], 410);
        }

        return view('auth.telegram-link-login', [
            'token' => $token,
            'email' => (string) $intent->email,
        ]);
    }

    public function store(ConsumeTelegramLoginIntentRequest $request, string $token): View|Response
    {
        $result = $this->telegramLoginIntentService->consumeWithPassword(
            $token,
            (string) $request->string('password'),
        );

        if ($result['status'] === 'invalid_or_expired') {
            return response()->view('auth.telegram-link-status', [
                'title' => __('auth.telegram_link.invalid_title'),
                'message' => __('auth.telegram_link.invalid_message'),
                'isSuccess' => false,
            ], 410);
        }

        if ($result['status'] === 'invalid_credentials') {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors([
                    'password' => __('auth.failed'),
                ]);
        }

        return view('auth.telegram-link-status', [
            'title' => __('auth.telegram_link.success_title'),
            'message' => __('auth.telegram_link.success_message'),
            'isSuccess' => true,
        ]);
    }
}
