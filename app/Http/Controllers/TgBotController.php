<?php

namespace App\Http\Controllers;

use App\Http\Requests\Telegram\UpdateTelegramSettingsRequest;
use App\Models\GameSession;
use App\Models\ReadyDictionary;
use App\Models\TelegramSetting;
use App\Services\Telegram\SaveTelegramSettingsService;
use App\Support\PartOfSpeechCatalog;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use App\Services\Navigation\HeaderNavigationService;
use App\Models\UserDictionary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TgBotController extends Controller
{
    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        $user = $request->user();
        $telegramSetting = $user->telegramSetting()
            ->with([
                'randomWordSessions.partsOfSpeech',
                'randomWordSessions.userDictionaries',
                'randomWordSessions.readyDictionaries',
            ])
            ->first();

        $userDictionaries = UserDictionary::query()
            ->where('user_id', $user->id)
            ->withCount('words')
            ->orderBy('name')
            ->get();

        $readyDictionaries = ReadyDictionary::query()
            ->withCount('words')
            ->orderBy('name')
            ->get();

        return view('tg-bot', [
            'activeNav' => 'tg-bot',
            'telegramBotUsername' => 'WordKeeperBot_bot',
            'telegramBotUrl' => 'https://t.me/WordKeeperBot_bot',
            'telegramConnected' => filled($user->tg_chat_id),
            'timezoneOptions' => $this->buildTimezoneOptions(),
            'directionOptions' => [
                GameSession::DIRECTION_FOREIGN_TO_RU => __('tg-bot.form.directions.foreign_to_ru'),
                GameSession::DIRECTION_RU_TO_FOREIGN => __('tg-bot.form.directions.ru_to_foreign'),
            ],
            'partOfSpeechOptions' => PartOfSpeechCatalog::labelsWithAll(),
            'userDictionaries' => $userDictionaries,
            'readyDictionaries' => $readyDictionaries,
            'telegramSettingsFormData' => $this->buildFormData($telegramSetting),
        ] + $headerNavigationService->forUser($user));
    }

    public function update(
        UpdateTelegramSettingsRequest $request,
        SaveTelegramSettingsService $saveTelegramSettingsService,
    ): RedirectResponse {
        $user = $request->user();

        if (! filled($user->tg_chat_id)) {
            return redirect()
                ->route('tg-bot')
                ->with('tgBotSettingsError', __('tg-bot.connection.required_to_configure'));
        }

        $saveTelegramSettingsService->save($user, $request->validated());

        return redirect()
            ->route('tg-bot')
            ->with('tgBotSettingsStatus', __('tg-bot.form.saved'));
    }

    private function buildFormData(?TelegramSetting $telegramSetting): array
    {
        if ($telegramSetting === null) {
            return [
                'timezone' => 'Europe/Moscow',
                'random_words_enabled' => false,
                'sessions' => [$this->defaultSession()],
            ];
        }

        $sessions = $telegramSetting->randomWordSessions
            ->map(fn ($session): array => [
                'send_time' => substr((string) $session->send_time, 0, 5),
                'translation_direction' => $session->translation_direction,
                'words_count' => max(2, min(20, (int) ($session->words_count ?? 10))),
                'part_of_speech' => $this->formatPartOfSpeechSelection($session->partsOfSpeech),
                'user_dictionary_ids' => $session->userDictionaries->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'ready_dictionary_ids' => $session->readyDictionaries->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'timezone' => $telegramSetting->timezone,
            'random_words_enabled' => $telegramSetting->random_words_enabled,
            'sessions' => $sessions !== [] ? $sessions : [$this->defaultSession()],
        ];
    }

    private function defaultSession(): array
    {
        return [
            'send_time' => '09:00',
            'translation_direction' => GameSession::DIRECTION_FOREIGN_TO_RU,
            'words_count' => 10,
            'part_of_speech' => [PartOfSpeechCatalog::ALL],
            'user_dictionary_ids' => [],
            'ready_dictionary_ids' => [],
        ];
    }

    private function formatPartOfSpeechSelection(Collection $partsOfSpeech): array
    {
        $values = $partsOfSpeech
            ->pluck('part_of_speech')
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->values()
            ->all();

        return $values !== [] ? $values : [PartOfSpeechCatalog::ALL];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function buildTimezoneOptions(): array
    {
        $reference = CarbonImmutable::now('UTC');

        return collect(DateTimeZone::listIdentifiers())
            ->map(function (string $timezone) use ($reference): array {
                $offsetSeconds = (new DateTimeZone($timezone))->getOffset($reference);
                $sign = $offsetSeconds >= 0 ? '+' : '-';
                $hours = intdiv(abs($offsetSeconds), 3600);
                $minutes = intdiv(abs($offsetSeconds) % 3600, 60);

                return [
                    'value' => $timezone,
                    'label' => sprintf('%s (UTC%s%02d:%02d)', $timezone, $sign, $hours, $minutes),
                ];
            })
            ->all();
    }
}
