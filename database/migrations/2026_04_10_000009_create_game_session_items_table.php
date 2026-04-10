<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order_index');
            $table->string('prompt_text');
            $table->string('correct_answer');
            $table->string('user_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'order_index']);
            $table->index(['game_session_id', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_items');
    }
};
