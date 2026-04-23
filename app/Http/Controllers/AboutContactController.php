<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAboutContactRequest;
use App\Jobs\SendAboutContactMessageJob;
use App\Models\AboutContactMessage;
use Illuminate\Http\RedirectResponse;
use Throwable;

class AboutContactController extends Controller
{
    public function store(StoreAboutContactRequest $request): RedirectResponse
    {
        $contactMessage = AboutContactMessage::create([
            'contact_email' => (string) $request->string('contact_email'),
            'subject' => (string) $request->string('subject'),
            'message' => (string) $request->string('message'),
            'delivery_status' => AboutContactMessage::STATUS_PENDING,
        ]);

        try {
            SendAboutContactMessageJob::dispatch($contactMessage->id, app()->getLocale());

            return redirect()
                ->route('about')
                ->with('aboutContactStatus', __('about.contact.status.success'));
        } catch (Throwable $exception) {
            report($exception);

            $contactMessage->forceFill([
                'delivery_status' => AboutContactMessage::STATUS_FAILED,
                'delivery_error' => AboutContactMessage::ERROR_DISPATCH_FAILED,
                'delivery_error_message' => $exception->getMessage(),
            ])->save();

            return redirect()
                ->route('about')
                ->withInput()
                ->with('aboutContactError', __('about.contact.status.error'));
        }
    }
}
