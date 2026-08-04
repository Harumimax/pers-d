<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAuthStateStore;
use App\Services\Translation\TranslatorTextTranslationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramTextTranslationTest extends TestCase
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

    public function test_authorized_user_can_start_text_translation_flow(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate($this->ru('\\u041f\\u0435\\u0440\\u0435\\u0432\\u043e\\u0434 \\u0442\\u0435\\u043a\\u0441\\u0442\\u0430'), 35001))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');

        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_TEXT_TRANSLATION_TEXT, $state['step']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], $this->ru('\\u0412\\u0432\\u0435\\u0434\\u0438\\u0442\\u0435 \\u0442\\u0435\\u043a\\u0441\\u0442 \\u0434\\u043e 4000 \\u0441\\u0438\\u043c\\u0432\\u043e\\u043b\\u043e\\u0432.')));
    }

    public function test_authorized_user_can_translate_text(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        app()->bind(TranslatorTextTranslationServiceInterface::class, fn () => new class implements TranslatorTextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                TestCase::assertSame('Hello world', $text);
                TestCase::assertSame('en', $sourceLanguage);
                TestCase::assertSame('ru', $targetLanguage);

                return 'Привет, мир';
            }
        });

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        app(TelegramAuthStateStore::class)->startTextTranslation('1001');

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Hello world', 35002))
            ->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (string) $request['text'] === 'Привет, мир');
    }

    public function test_text_translation_rejects_text_longer_than_four_thousand_characters(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        app(TelegramAuthStateStore::class)->startTextTranslation('1001');

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate(str_repeat('a', 4001), 35003))
            ->assertOk();

        $state = app(TelegramAuthStateStore::class)->get('1001');

        $this->assertSame(TelegramAuthStateStore::STEP_AWAITING_TEXT_TRANSLATION_TEXT, $state['step']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], $this->ru('\\u0422\\u0435\\u043a\\u0441\\u0442 \\u043d\\u0435 \\u0434\\u043e\\u043b\\u0436\\u0435\\u043d \\u043f\\u0440\\u0435\\u0432\\u044b\\u0448\\u0430\\u0442\\u044c 4000 \\u0441\\u0438\\u043c\\u0432\\u043e\\u043b\\u043e\\u0432.')));
    }

    public function test_text_translation_reports_translation_error(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        app()->bind(TranslatorTextTranslationServiceInterface::class, fn () => new class implements TranslatorTextTranslationServiceInterface
        {
            public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
            {
                throw new RuntimeException('translation failed');
            }
        });

        User::factory()->create([
            'tg_chat_id' => '1001',
            'tg_linked_at' => now(),
        ]);

        app(TelegramAuthStateStore::class)->startTextTranslation('1001');

        $this->postJson('/telegram/webhook/telegram-secret', $this->messageUpdate('Hello world', 35004))
            ->assertOk();

        $this->assertNull(app(TelegramAuthStateStore::class)->get('1001'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], $this->ru('\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u043f\\u0435\\u0440\\u0435\\u0432\\u0435\\u0441\\u0442\\u0438 \\u0442\\u0435\\u043a\\u0441\\u0442. \\u041f\\u043e\\u043f\\u0440\\u043e\\u0431\\u0443\\u0439\\u0442\\u0435 \\u043f\\u043e\\u0437\\u0436\\u0435.')));
    }

    /**
     * @return array<string, mixed>
     */
    private function messageUpdate(string $text, int $updateId): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => $updateId,
                'date' => now()->timestamp,
                'chat' => [
                    'id' => 1001,
                    'type' => 'private',
                ],
                'from' => [
                    'id' => 1001,
                    'is_bot' => false,
                    'first_name' => 'Max',
                ],
                'text' => $text,
            ],
        ];
    }

    private function ru(string $encoded): string
    {
        return (string) json_decode('"'.$encoded.'"', true, 512, JSON_THROW_ON_ERROR);
    }
}