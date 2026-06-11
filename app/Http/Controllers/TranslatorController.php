<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranslateTextRequest;
use App\Services\Navigation\HeaderNavigationService;
use App\Services\Translation\TranslatorTextTranslationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class TranslatorController extends Controller
{
    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        return $this->renderPage(
            request: $request,
            headerNavigationService: $headerNavigationService,
            formData: $this->defaultFormData(),
            translatedText: '',
        );
    }

    public function store(
        TranslateTextRequest $request,
        TranslatorTextTranslationServiceInterface $textTranslationService,
        HeaderNavigationService $headerNavigationService,
    ): View {
        $validated = $request->validated();

        try {
            $translatedText = $textTranslationService->translateText(
                $validated['text'],
                $validated['source_language'],
                $validated['target_language'],
            );
        } catch (Throwable $exception) {
            Log::warning('translator.translation_failed', [
                'user_id' => $request->user()?->id,
                'source_language' => $validated['source_language'],
                'target_language' => $validated['target_language'],
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->renderPage(
                request: $request,
                headerNavigationService: $headerNavigationService,
                formData: $validated,
                translatedText: '',
                errorMessage: __('translator.messages.failed'),
            );
        }

        return $this->renderPage(
            request: $request,
            headerNavigationService: $headerNavigationService,
            formData: $validated,
            translatedText: $translatedText,
        );
    }

    /**
     * @param  array{source_language:string,target_language:string,text:string}  $formData
     */
    private function renderPage(
        Request $request,
        HeaderNavigationService $headerNavigationService,
        array $formData,
        string $translatedText,
        ?string $errorMessage = null,
    ): View {
        return view('translator.index', [
            'activeNav' => 'translator',
            'formData' => $formData,
            'translatedText' => $translatedText,
            'translationError' => $errorMessage,
            'languageOptions' => [
                ['value' => 'ru', 'label' => 'RU'],
                ['value' => 'en', 'label' => 'EN'],
                ['value' => 'es', 'label' => 'SP'],
                ['value' => 'de', 'label' => 'DE'],
                ['value' => 'it', 'label' => 'IT'],
                ['value' => 'pt', 'label' => 'PT'],
            ],
        ] + $headerNavigationService->forUser($request->user()));
    }

    /**
     * @return array{source_language:string,target_language:string,text:string}
     */
    private function defaultFormData(): array
    {
        return [
            'source_language' => 'en',
            'target_language' => 'ru',
            'text' => '',
        ];
    }
}
