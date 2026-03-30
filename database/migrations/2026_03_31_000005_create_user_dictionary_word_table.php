<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dictionary_word', function (Blueprint $table) {
            $table->foreignId('user_dictionary_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('word_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_dictionary_id', 'word_id']);
            $table->index('word_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dictionary_word');
    }
};
