<?php

namespace Tests\Feature;

use App\Models\DictionarySubscription;
use App\Models\ReadyDictionary;
use App\Models\ReadyDictionaryWord;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Dictionaries\UserDictionaryWordSearchService;
use App\Services\Telegram\TelegramAuthStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramDictionaryWordSearchTest extends TestCase
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

    public function test_authorized_start_shows_search_button_in_main_menu(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('/start', 33001))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && data_get($request->data(), 'reply_markup.keyboard.0.0.text') === 'Словари'
                && data_get($request->data(), 'reply_markup.keyboard.1.0.text') === 'Поиск слов'
                && data_get($request->data(), 'reply_markup.keyboard.2.0.text') === 'Добавить слово'
                && data_get($request->data(), 'reply_markup.keyboard.3.0.text') === 'Выход';
        });
    }

    public function test_search_words_command_starts_search_flow_and_sends_prompt(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Поиск слов', 33001))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');

        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_DICTIONARY_SEARCH_QUERY, $state['step']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'Введите слово или часть слова для поиска.')
            && str_contains((string) $request['text'], 'Поиск будет осуществлён по вашим словарям'));
    }

    public function test_search_query_returns_results_by_word_for_current_users_dictionaries_only(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);
        $otherUser = User::factory()->create();

        $generalDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Общий словарь',
            'language' => 'English',
        ]);
        $politeDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Вежливый словарь',
            'language' => 'English',
        ]);
        $foreignDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Чужой словарь',
            'language' => 'English',
        ]);

        $helloWord = Word::create([
            'word' => 'Hello',
            'translation' => 'Привет',
            'part_of_speech' => 'interjection',
            'comment' => 'Приветствие',
        ]);
        $formalHelloWord = Word::create([
            'word' => 'Hello',
            'translation' => 'Здравствуйте',
            'part_of_speech' => 'interjection',
            'comment' => 'Официальное приветствие',
        ]);
        $foreignWord = Word::create([
            'word' => 'Hello hidden',
            'translation' => 'Скрытый привет',
            'part_of_speech' => 'interjection',
            'comment' => 'Чужой комментарий',
        ]);

        $generalDictionary->words()->attach($helloWord->id);
        $politeDictionary->words()->attach($formalHelloWord->id);
        $foreignDictionary->words()->attach($foreignWord->id);

        ReadyDictionaryWord::factory()->create([
            'ready_dictionary_id' => ReadyDictionary::factory()->create([
                'name' => 'Ready greetings',
                'language' => 'English',
            ])->id,
            'word' => 'Hello ready',
            'translation' => 'Готовый привет',
            'comment' => 'Готовый комментарий',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Поиск слов', 33001))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('hell', 33002))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(function (Request $request): bool {
            $text = (string) $request['text'];

            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($text, 'Результаты поиска:')
                && str_contains($text, '1. Hello - ')
                && str_contains($text, '2. Hello - ')
                && str_contains($text, 'Hello - Привет')
                && str_contains($text, 'Приветствие')
                && str_contains($text, 'Общий словарь')
                && str_contains($text, 'Hello - Здравствуйте')
                && str_contains($text, 'Официальное приветствие')
                && str_contains($text, 'Вежливый словарь')
                && ! str_contains($text, 'Чужой словарь')
                && ! str_contains($text, 'Hello ready');
        });
    }

    public function test_search_query_returns_results_by_translation(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Испанские слова',
            'language' => 'Spanish',
        ]);

        $matchingWord = Word::create([
            'word' => 'ventana',
            'translation' => 'окно в комнате',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);
        $nonMatchingWord = Word::create([
            'word' => 'mesa',
            'translation' => 'стол',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$matchingWord->id, $nonMatchingWord->id]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Поиск слов', 33001))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('комнат', 33002))->assertOk();

        Http::assertSent(function (Request $request): bool {
            $text = (string) $request['text'];

            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($text, 'ventana - окно в комнате')
                && str_contains($text, 'Испанские слова')
                && ! str_contains($text, 'mesa - стол');
        });
    }

    public function test_search_query_returns_results_from_subscribed_dictionary(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $owner = User::factory()->create();
        $subscriber = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared dictionary',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'shared hello',
            'translation' => 'РѕР±С‰РёР№ РїСЂРёРІРµС‚',
            'comment' => 'Shared comment',
        ]);

        $dictionary->words()->attach($word->id);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $dictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        $results = app(UserDictionaryWordSearchService::class)->search($subscriber, 'shared');

        $this->assertCount(1, $results);
        $this->assertSame('Shared dictionary', $results->first()->dictionary_name);
        $this->assertSame('shared hello', $results->first()->word);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('РџРѕРёСЃРє СЃР»РѕРІ', 33011))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('shared', 33012))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage'));
    }

    public function test_search_query_returns_empty_state_when_nothing_is_found(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'English Core',
            'language' => 'English',
        ]);

        $word = Word::create([
            'word' => 'sun',
            'translation' => 'солнце',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Поиск слов', 33001))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('moonlight', 33002))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (string) $request['text'] === "Результаты поиска:\nТаких слов не найдено в ваших словарях.");
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
}
