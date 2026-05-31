<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_word_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->boolean('remainder_had_mistake')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'word_id']);
        });

        DB::statement(<<<'SQL'
            INSERT INTO user_word_progress (user_id, word_id, remainder_had_mistake, created_at, updated_at)
            SELECT DISTINCT
                user_dictionaries.user_id,
                words.id,
                TRUE,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            FROM words
            INNER JOIN user_dictionary_word
                ON user_dictionary_word.word_id = words.id
            INNER JOIN user_dictionaries
                ON user_dictionaries.id = user_dictionary_word.user_dictionary_id
            WHERE words.remainder_had_mistake = TRUE
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_word_progress');
    }
};
