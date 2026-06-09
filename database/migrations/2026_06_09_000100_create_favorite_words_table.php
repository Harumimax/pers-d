<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_words', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_dictionary_type', 32);
            $table->unsignedBigInteger('source_dictionary_id');
            $table->string('source_word_type', 32);
            $table->unsignedBigInteger('source_word_id');
            $table->timestamps();

            $table->unique([
                'user_id',
                'source_dictionary_type',
                'source_dictionary_id',
                'source_word_type',
                'source_word_id',
            ], 'favorite_words_unique_source_per_user');

            $table->index(['user_id', 'created_at'], 'favorite_words_user_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_words');
    }
};
