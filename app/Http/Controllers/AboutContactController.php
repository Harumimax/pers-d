<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAboutContactRequest;
use App\Mail\AboutContactMessage as AboutContactMail;
use App\Models\AboutContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
            Mail::to((string) config('mail.about_contact_recipient'))
                ->locale(app()->getLocale())
                ->send(new AboutContactMail($contactMessage));

            $contactMessage->forceFill([
                'delivery_status' => AboutContactMessage::STATUS_SENT,
                'delivered_at' => Carbon::now(),
                'delivery_error' => null,
            ])->save();

            return redirect()
                ->route('about')
                ->with('aboutContactStatus', __('about.contact.status.success'));
        } catch (Throwable $exception) {
            $contactMessage->forceFill([
                'delivery_status' => AboutContactMessage::STATUS_FAILED,
                'delivery_error' => Str::limit($exception->getMessage(), 1000),
            ])->save();

            return redirect()
                ->route('about')
                ->withInput()
                ->with('aboutContactError', __('about.contact.status.error'));
        }
    }
}
