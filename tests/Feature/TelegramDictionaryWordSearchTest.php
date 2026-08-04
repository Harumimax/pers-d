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
                && data_get($request->data(), 'reply_markup.keyboard.0.0.text') === $this->ru('\\u0421\\u043b\\u043e\\u0432\\u0430\\u0440\\u0438')
                && data_get($request->data(), 'reply_markup.keyboard.1.0.text') === $this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0441\\u043b\\u043e\\u0432')
                && data_get($request->data(), 'reply_markup.keyboard.2.0.text') === $this->ru('\\u0414\\u043e\\u0431\\u0430\\u0432\\u0438\\u0442\\u044c \\u0441\\u043b\\u043e\\u0432\\u043e')
                && data_get($request->data(), 'reply_markup.keyboard.3.0.text') === $this->ru('\\u0412\\u044b\\u0445\\u043e\\u0434');
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

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate($this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0441\\u043b\\u043e\\u0432'), 33001))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');

        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_DICTIONARY_SEARCH_QUERY, $state['step']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], $this->ru('\\u0412\\u0432\\u0435\\u0434\\u0438\\u0442\\u0435 \\u0441\\u043b\\u043e\\u0432\\u043e \\u0438\\u043b\\u0438 \\u0447\\u0430\\u0441\\u0442\\u044c \\u0441\\u043b\\u043e\\u0432\\u0430 \\u0434\\u043b\\u044f \\u043f\\u043e\\u0438\\u0441\\u043a\\u0430.'))
            && str_contains((string) $request['text'], $this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0431\\u0443\\u0434\\u0435\\u0442 \\u043e\\u0441\\u0443\\u0449\\u0435\\u0441\\u0442\\u0432\\u043b\\u0451\\u043d \\u043f\\u043e \\u0432\\u0430\\u0448\\u0438\\u043c \\u0441\\u043b\\u043e\\u0432\\u0430\\u0440\\u044f\\u043c')));
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
            'name' => 'General dictionary',
            'language' => 'English',
        ]);
        $politeDictionary = UserDictionary::create([
            'user_id' => $user->id,
            'name' => 'Polite dictionary',
            'language' => 'English',
        ]);
        $foreignDictionary = UserDictionary::create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign dictionary',
            'language' => 'English',
        ]);

        $helloWord = Word::create([
            'word' => 'Hello',
            'translation' => 'Hi',
            'part_of_speech' => 'interjection',
            'comment' => 'Greeting',
        ]);
        $formalHelloWord = Word::create([
            'word' => 'Hello',
            'translation' => 'Good day',
            'part_of_speech' => 'interjection',
            'comment' => 'Formal greeting',
        ]);
        $foreignWord = Word::create([
            'word' => 'Hello hidden',
            'translation' => 'Hidden hello',
            'part_of_speech' => 'interjection',
            'comment' => 'Foreign comment',
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
            'translation' => 'Ready hello',
            'comment' => 'Ready comment',
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate($this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0441\\u043b\\u043e\\u0432'), 33001))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('hell', 33002))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(function (Request $request): bool {
            $text = (string) $request['text'];

            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($text, $this->ru('\\u0420\\u0435\\u0437\\u0443\\u043b\\u044c\\u0442\\u0430\\u0442\\u044b \\u043f\\u043e\\u0438\\u0441\\u043a\\u0430:'))
                && str_contains($text, 'Hello - Hi')
                && str_contains($text, 'Hello - Good day')
                && str_contains($text, 'Greeting')
                && str_contains($text, 'General dictionary')
                && str_contains($text, 'Formal greeting')
                && str_contains($text, 'Polite dictionary')
                && ! str_contains($text, 'Foreign dictionary')
                && ! str_contains($text, 'Hello hidden')
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
            'name' => 'Spanish words',
            'language' => 'Spanish',
        ]);

        $matchingWord = Word::create([
            'word' => 'ventana',
            'translation' => 'room window',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);
        $nonMatchingWord = Word::create([
            'word' => 'mesa',
            'translation' => 'table',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach([$matchingWord->id, $nonMatchingWord->id]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate($this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0441\\u043b\\u043e\\u0432'), 33001))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('room', 33002))->assertOk();

        Http::assertSent(function (Request $request): bool {
            $text = (string) $request['text'];

            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($text, 'ventana - room window')
                && str_contains($text, 'Spanish words')
                && ! str_contains($text, 'mesa - table');
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
            'translation' => 'shared greeting',
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

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate($this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0441\\u043b\\u043e\\u0432'), 33011))->assertOk();
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
            'translation' => 'sun',
            'part_of_speech' => 'noun',
            'comment' => null,
        ]);

        $dictionary->words()->attach($word->id);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate($this->ru('\\u041f\\u043e\\u0438\\u0441\\u043a \\u0441\\u043b\\u043e\\u0432'), 33001))->assertOk();
        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('moonlight', 33002))->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (string) $request['text'] === $this->ru('\\u0420\\u0435\\u0437\\u0443\\u043b\\u044c\\u0442\\u0430\\u0442\\u044b \\u043f\\u043e\\u0438\\u0441\\u043a\\u0430:\\n\\u0422\\u0430\\u043a\\u0438\\u0445 \\u0441\\u043b\\u043e\\u0432 \\u043d\\u0435 \\u043d\\u0430\\u0439\\u0434\\u0435\\u043d\\u043e \\u0432 \\u0432\\u0430\\u0448\\u0438\\u0445 \\u0441\\u043b\\u043e\\u0432\\u0430\\u0440\\u044f\\u0445.'));
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

    private function ru(string $encoded): string
    {
        return (string) json_decode('"'.$encoded.'"', true, 512, JSON_THROW_ON_ERROR);
    }
}
