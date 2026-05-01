<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_interval_review_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_interval_review_plan_id')
                ->constrained('telegram_interval_review_plans')
                ->cascadeOnDelete();
            $table->foreignId('telegram_interval_review_session_id')
                ->constrained('telegram_interval_review_sessions')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('session_number');
            $table->unsignedSmallInteger('total_words');
            $table->string('status', 24)->default('scheduled');
            $table->timestampTz('scheduled_for');
            $table->timestampTz('intro_message_sent_at')->nullable();
            $table->unsignedBigInteger('intro_message_id')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('last_interaction_at')->nullable();
            $table->string('last_error_code', 64)->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestampTz('last_error_at')->nullable();
            $table->jsonb('config_snapshot');
            $table->timestamps();

            $table->unique(['telegram_interval_review_session_id'], 'tir_runs_unique_session');
        });

        Schema::create('telegram_interval_review_run_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_interval_review_run_id')
                ->constrained('telegram_interval_review_runs')
                ->cascadeOnDelete();
            $table->foreignId('telegram_interval_review_plan_word_id')
                ->nullable()
                ->constrained('telegram_interval_review_plan_words')
                ->nullOnDelete();
            $table->unsignedSmallInteger('order_index');
            $table->string('word_snapshot');
            $table->text('translation_snapshot');
            $table->string('part_of_speech_snapshot', 64)->nullable();
            $table->text('comment_snapshot')->nullable();
            $table->text('prompt_text');
            $table->text('correct_answer');
            $table->string('source_type_snapshot', 16)->nullable();
            $table->jsonb('options_json')->nullable();
            $table->text('user_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestampTz('answered_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_interval_review_run_id', 'order_index'], 'tir_run_items_run_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_interval_review_run_items');
        Schema::dropIfExists('telegram_interval_review_runs');
    }
};
