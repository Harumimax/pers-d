<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_is_available(): void
    {
        $response = $this->get('/game');

        $response
            ->assertOk()
            ->assertSee('platformer-canvas', false)
            ->assertSee('data-game-start-button', false)
            ->assertSee('data-game-progress-step', false);
    }
}
