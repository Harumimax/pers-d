<?php

namespace Tests\Feature;

use App\Models\DictionarySubscription;
use App\Models\User;
use App\Models\UserDictionary;
use App\Models\Word;
use App\Services\Telegram\TelegramAuthStateStore;
use App\Services\Translation\Data\TranslationResult;
use App\Services\Translation\Data\TranslationSuggestion;
use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAddWordFlowTest extends TestCase
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

    public function test_authorized_user_can_add_word_to_dictionary_from_telegram(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->instance(TranslationServiceInterface::class, new class implements TranslationServiceInterface
        {
            public function translate(string $text, string $sourceLanguage, string $targetLanguage): TranslationResult
            {
                return new TranslationResult([
                    new TranslationSuggestion('привет', 'top result'),
                    new TranslationSuggestion('здравствуйте', 'alternative'),
                    new TranslationSuggestion('алло', 'alternative'),
                    new TranslationSuggestion('добрый день', 'alternative'),
                    new TranslationSuggestion('приветствие', 'alternative'),
                    new TranslationSuggestion('здорово', 'alternative'),
                    new TranslationSuggestion('лишний вариант', 'alternative'),
                ]);
            }
        });

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Travel English',
            'language' => 'English',
        ]);

        UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Spanish verbs',
            'language' => 'Spanish',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Добавить слово', 40001))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'Выберите словарь, в который надо добавить слово:')
            && str_contains((string) $request['text'], '1. Travel English (English)')
            && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.text') === '1');

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_add_word:dictionary:{$dictionary->id}", 40002, 'cb-dict'))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');
        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_TEXT, $state['step']);
        $this->assertSame($dictionary->id, $state['dictionary_id']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (string) $request['text'] === 'Введите слово для перевода. Не более 50 символов.');

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate(str_repeat('a', 51), 40003))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');
        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_TEXT, $state['step']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (string) $request['text'] === 'Слово не должно превышать 50 символов.');

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('hello', 40004))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');
        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_TRANSLATION, $state['step']);
        $this->assertCount(6, $state['translation_options']);
        $this->assertSame('hello', $state['word']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'Выберите вариант перевода:')
            && str_contains((string) $request['text'], '1. привет (top result)')
            && str_contains((string) $request['text'], '6. здорово (alternative)')
            && ! str_contains((string) $request['text'], 'лишний вариант'));

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('tg_add_word:translation:1', 40005, 'cb-translation'))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');
        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_PART_OF_SPEECH, $state['step']);
        $this->assertSame('здравствуйте', $state['selected_translation']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'Выберите часть речи:'));

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('tg_add_word:part_of_speech:verb', 40006, 'cb-pos'))
            ->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));
        $this->assertDatabaseHas('words', [
            'word' => 'hello',
            'translation' => 'здравствуйте',
            'part_of_speech' => 'verb',
        ]);

        /** @var Word $word */
        $word = Word::query()->where('word', 'hello')->firstOrFail();
        $this->assertTrue($dictionary->words()->where('words.id', $word->id)->exists());

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'Слово hello')
            && str_contains((string) $request['text'], 'успешно сохранено в Travel English'));
    }

    public function test_user_cannot_pick_foreign_dictionary_in_add_word_flow(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $foreignUser = User::factory()->create();
        $foreignDictionary = UserDictionary::create([
            'user_id' => $foreignUser->id,
            'name' => 'Hidden dictionary',
            'language' => 'English',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_add_word:dictionary:{$foreignDictionary->id}", 40100, 'cb-foreign'))
            ->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery')
            && (string) $request['text'] === 'Словарь не найден.');
    }

    public function test_user_cannot_pick_subscribed_dictionary_in_add_word_flow(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $owner = User::factory()->create();
        $subscriber = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $subscribedDictionary = UserDictionary::create([
            'user_id' => $owner->id,
            'name' => 'Shared dictionary',
            'language' => 'English',
        ]);

        DictionarySubscription::query()->create([
            'user_dictionary_id' => $subscribedDictionary->id,
            'subscriber_user_id' => $subscriber->id,
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_add_word:dictionary:{$subscribedDictionary->id}", 40101, 'cb-subscribed'))
            ->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery'));
    }

    public function test_user_can_cancel_add_word_flow_from_translation_step(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->instance(TranslationServiceInterface::class, new class implements TranslationServiceInterface
        {
            public function translate(string $text, string $sourceLanguage, string $targetLanguage): TranslationResult
            {
                return new TranslationResult([
                    new TranslationSuggestion('РїСЂРёРІРµС‚', 'top result'),
                    new TranslationSuggestion('Р·РґСЂР°РІСЃС‚РІСѓР№С‚Рµ', 'alternative'),
                ]);
            }
        });

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Travel English',
            'language' => 'English',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Р”РѕР±Р°РІРёС‚СЊ СЃР»РѕРІРѕ', 40201))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_add_word:dictionary:{$dictionary->id}", 40202, 'cb-dict-cancel-1'))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('hello', 40203))->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');
        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_TRANSLATION, $state['step']);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('tg_add_word:cancel:1', 40204, 'cb-cancel-translation'))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));
        $this->assertDatabaseCount('words', 0);
    }

    public function test_user_can_cancel_add_word_flow_from_part_of_speech_step(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->instance(TranslationServiceInterface::class, new class implements TranslationServiceInterface
        {
            public function translate(string $text, string $sourceLanguage, string $targetLanguage): TranslationResult
            {
                return new TranslationResult([
                    new TranslationSuggestion('РїСЂРёРІРµС‚', 'top result'),
                    new TranslationSuggestion('Р·РґСЂР°РІСЃС‚РІСѓР№С‚Рµ', 'alternative'),
                ]);
            }
        });

        $user = User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $dictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Travel English',
            'language' => 'English',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Р”РѕР±Р°РІРёС‚СЊ СЃР»РѕРІРѕ', 40301))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate("tg_add_word:dictionary:{$dictionary->id}", 40302, 'cb-dict-cancel-2'))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('hello', 40303))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('tg_add_word:translation:0', 40304, 'cb-translation-cancel'))->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');
        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_ADD_WORD_PART_OF_SPEECH, $state['step']);

        $this->postJson('/telegram/webhook/telegram-secret', $this->callbackUpdate('tg_add_word:cancel:1', 40305, 'cb-cancel-pos'))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));
        $this->assertDatabaseCount('words', 0);
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
                    'text' => 'callback',
                ],
                'data' => $data,
            ],
        ];
    }
}
