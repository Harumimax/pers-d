<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_game_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_setting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_random_word_session_id')->constrained()->cascadeOnDelete();
            $table->string('mode');
            $table->string('direction');
            $table->unsignedInteger('total_words');
            $table->string('status');
            $table->timestampTz('scheduled_for');
            $table->timestampTz('intro_message_sent_at')->nullable();
            $table->unsignedBigInteger('intro_message_id')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->jsonb('config_snapshot');
            $table->timestamps();

            $table->unique(['telegram_random_word_session_id', 'scheduled_for'], 'telegram_game_runs_unique_schedule');
            $table->index(['status', 'scheduled_for']);
        });

        Schema::create('telegram_game_run_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_game_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('order_index');
            $table->string('prompt_text');
            $table->string('part_of_speech_snapshot')->nullable();
            $table->string('correct_answer');
            $table->string('source_type_snapshot')->nullable();
            $table->jsonb('options_json')->nullable();
            $table->string('user_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestampTz('answered_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_game_run_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_game_run_items');
        Schema::dropIfExists('telegram_game_runs');
    }
};
