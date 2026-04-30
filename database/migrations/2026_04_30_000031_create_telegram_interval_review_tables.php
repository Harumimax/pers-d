<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_interval_review_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('status', 24)->default('active');
            $table->string('language', 32);
            $table->time('start_time');
            $table->string('timezone');
            $table->unsignedSmallInteger('words_count');
            $table->timestamps();
        });

        Schema::create('telegram_interval_review_plan_words', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_interval_review_plan_id')
                ->constrained('telegram_interval_review_plans')
                ->cascadeOnDelete();
            $table->string('source_type', 16);
            $table->unsignedBigInteger('source_dictionary_id')->nullable();
            $table->unsignedBigInteger('source_word_id')->nullable();
            $table->string('dictionary_name');
            $table->string('language', 32);
            $table->string('word');
            $table->text('translation');
            $table->string('part_of_speech', 64)->nullable();
            $table->text('comment')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index(['telegram_interval_review_plan_id', 'position'], 'tir_plan_words_plan_position_idx');
        });

        Schema::create('telegram_interval_review_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_interval_review_plan_id')
                ->constrained('telegram_interval_review_plans')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('session_number');
            $table->timestampTz('scheduled_for');
            $table->string('status', 24)->default('scheduled');
            $table->timestamps();

            $table->unique(['telegram_interval_review_plan_id', 'session_number'], 'tir_sessions_plan_number_unique');
            $table->index(['telegram_interval_review_plan_id', 'scheduled_for'], 'tir_sessions_plan_scheduled_for_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_interval_review_sessions');
        Schema::dropIfExists('telegram_interval_review_plan_words');
        Schema::dropIfExists('telegram_interval_review_plans');
    }
};
