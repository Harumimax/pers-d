<?php

namespace App\Services\Telegram;

use App\Models\TelegramSetting;
use App\Models\User;
use App\Support\PartOfSpeechCatalog;
use Illuminate\Support\Facades\DB;

class SaveTelegramSettingsService
{
    /**
     * @param array{
     *     timezone:string,
     *     random_words_enabled:bool,
     *     sessions:array<int,array{
     *         send_time:string,
     *         translation_direction:string,
     *         words_count:int,
     *         part_of_speech?:array<int,string>,
     *         user_dictionary_ids?:array<int,int>,
     *         ready_dictionary_ids?:array<int,int>
     *     }>
     * } $payload
     */
    public function save(User $user, array $payload): TelegramSetting
    {
        return DB::transaction(function () use ($user, $payload): TelegramSetting {
            $setting = TelegramSetting::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'timezone' => $payload['timezone'],
                    'random_words_enabled' => $payload['random_words_enabled'],
                ]
            );

            $setting->randomWordSessions()->delete();

            foreach (array_values($payload['sessions']) as $index => $sessionPayload) {
                $session = $setting->randomWordSessions()->create([
                    'position' => $index + 1,
                    'send_time' => $sessionPayload['send_time'],
                    'translation_direction' => $sessionPayload['translation_direction'],
                    'words_count' => (int) $sessionPayload['words_count'],
                ]);

                $partsOfSpeech = collect($sessionPayload['part_of_speech'] ?? [])
                    ->map(fn (string $value) => trim($value))
                    ->filter()
                    ->reject(fn (string $value) => $value === PartOfSpeechCatalog::ALL)
                    ->unique()
                    ->values();

                if ($partsOfSpeech->isNotEmpty()) {
                    $session->partsOfSpeech()->createMany(
                        $partsOfSpeech
                            ->map(fn (string $value): array => ['part_of_speech' => $value])
                            ->all()
                    );
                }

                $session->userDictionaries()->sync(
                    collect($sessionPayload['user_dictionary_ids'] ?? [])->unique()->values()->all()
                );

                $session->readyDictionaries()->sync(
                    collect($sessionPayload['ready_dictionary_ids'] ?? [])->unique()->values()->all()
                );
            }

            return $setting->fresh([
                'randomWordSessions.partsOfSpeech',
                'randomWordSessions.userDictionaries',
                'randomWordSessions.readyDictionaries',
            ]);
        });
    }
}
