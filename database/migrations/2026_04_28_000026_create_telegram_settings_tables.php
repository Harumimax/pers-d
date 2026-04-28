<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('timezone');
            $table->boolean('random_words_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('telegram_random_word_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_setting_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->time('send_time');
            $table->string('translation_direction');
            $table->timestamps();

            $table->unique(['telegram_setting_id', 'position'], 'telegram_random_sessions_setting_position_unique');
        });

        Schema::create('telegram_random_word_session_part_of_speech', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_random_word_session_id', 'telegram_rw_session_pos_session_id')
                ->constrained('telegram_random_word_sessions')
                ->cascadeOnDelete();
            $table->string('part_of_speech');

            $table->unique(
                ['telegram_random_word_session_id', 'part_of_speech'],
                'telegram_rw_session_part_of_speech_unique'
            );
        });

        Schema::create('telegram_random_word_session_user_dictionary', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_random_word_session_id', 'telegram_rw_session_user_dict_session_id')
                ->constrained('telegram_random_word_sessions')
                ->cascadeOnDelete();
            $table->foreignId('user_dictionary_id')->constrained()->cascadeOnDelete();

            $table->unique(
                ['telegram_random_word_session_id', 'user_dictionary_id'],
                'telegram_rw_session_user_dictionary_unique'
            );
        });

        Schema::create('telegram_random_word_session_ready_dictionary', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_random_word_session_id', 'telegram_rw_session_ready_dict_session_id')
                ->constrained('telegram_random_word_sessions')
                ->cascadeOnDelete();
            $table->foreignId('ready_dictionary_id')->constrained('ready_dictionaries')->cascadeOnDelete();

            $table->unique(
                ['telegram_random_word_session_id', 'ready_dictionary_id'],
                'telegram_rw_session_ready_dictionary_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_random_word_session_ready_dictionary');
        Schema::dropIfExists('telegram_random_word_session_user_dictionary');
        Schema::dropIfExists('telegram_random_word_session_part_of_speech');
        Schema::dropIfExists('telegram_random_word_sessions');
        Schema::dropIfExists('telegram_settings');
    }
};
