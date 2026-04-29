<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramDictionaryBrowserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'telegram-test-token',
            'services.telegram.webhook_secret' => 'telegram-secret',
        ]);
    }

    public function test_authorized_user_can_open_dictionary_list_from_main_menu(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Travel English',
            'language' => 'English',
        ]);

        UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'Spanish verbs',
            'language' => 'Spanish',
        ]);

        $otherUser = User::factory()->create();
        UserDictionary::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Hidden dictionary',
            'language' => 'German',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Словари', 30001))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Ваши словари:')
                && str_contains((string) $request['text'], '1. Travel English — English')
                && str_contains((string) $request['text'], '2. Spanish verbs — Spanish')
                && ! str_contains((string) $request['text'], 'Hidden dictionary')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.text') === '1';
        });
    }

    public function test_dictionary_menu_shows_empty_state_when_user_has_no_dictionaries(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Словари', 30001))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'У вас пока нет созданных словарей')
            && str_contains((string) $request['text'], 'https://wordkeeper.space'));
    }

    public function test_user_can_open_dictionary_page_paginate_and_go_back(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::query()->create([
            'user_id' => $user->id,
            'name' => 'My big dictionary',
            'language' => 'English',
        ]);

        foreach (range(1, 25) as $index) {
            $word = Word::query()->create([
                'word' => 'word'.$index,
                'translation' => 'перевод'.$index,
                'part_of_speech' => $index === 1 ? 'noun' : null,
                'comment' => $index === 1 ? 'важный комментарий' : null,
            ]);

            $dictionary->words()->attach($word->id);
        }

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_dict:show:{$dictionary->id}:1", 31001, 'callback-dict-show'))
            ->assertOk();

        Http::assertSent(function (Request $request) use ($dictionary): bool {
            return str_ends_with($request->url(), '/editMessageText')
                && str_contains((string) $request['text'], "Словарь: {$dictionary->name}")
                && str_contains((string) $request['text'], 'Слова 1–20 из 25')
                && str_contains((string) $request['text'], '1. word1')
                && str_contains((string) $request['text'], 'Часть речи: noun')
                && str_contains((string) $request['text'], 'Комментарий: важный комментарий')
                && str_contains((string) $request['text'], '20. word20')
                && ! str_contains((string) $request['text'], '21. word21')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.1.text') === '1/2'
                && data_get($request->data(), 'reply_markup.inline_keyboard.1.0.text') === 'К словарям';
        });

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_dict:page:{$dictionary->id}:2", 31002, 'callback-dict-page'))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/editMessageText')
                && str_contains((string) $request['text'], 'Слова 21–25 из 25')
                && str_contains((string) $request['text'], '21. word21')
                && str_contains((string) $request['text'], '25. word25')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.1.text') === '2/2';
        });

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('tg_dict:back', 31003, 'callback-dict-back'))
            ->assertOk();

        Http::assertSent(function (Request $request) use ($dictionary): bool {
            return str_ends_with($request->url(), '/editMessageText')
                && str_contains((string) $request['text'], 'Ваши словари:')
                && str_contains((string) $request['text'], "1. {$dictionary->name} — English");
        });
    }

    public function test_user_cannot_open_foreign_dictionary(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $otherUser = User::factory()->create();
        $foreignDictionary = UserDictionary::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign dictionary',
            'language' => 'English',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_dict:show:{$foreignDictionary->id}:1", 32001, 'callback-dict-foreign'))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery')
            && str_contains((string) ($request['text'] ?? ''), 'Словарь не найден.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function messageUpdate(string $text, int $updateId): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 5001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'chat' => [
                    'id' => 1001,
                    'type' => 'private',
                ],
                'date' => now()->timestamp,
                'text' => $text,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackUpdate(string $data, int $updateId, string $callbackId): array
    {
        return [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => $callbackId,
                'from' => [
                    'id' => 5001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                    'username' => 'wordkeeper_user',
                ],
                'message' => [
                    'message_id' => 765,
                    'chat' => [
                        'id' => 1001,
                        'type' => 'private',
                    ],
                    'date' => now()->timestamp,
                    'text' => 'dictionary',
                ],
                'data' => $data,
            ],
        ];
    }
}
