<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ready_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language');
            $table->string('level')->nullable();
            $table->string('part_of_speech')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language']);
            $table->index('language');
            $table->index('level');
            $table->index('part_of_speech');
        });

        Schema::create('ready_dictionary_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ready_dictionary_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('word');
            $table->string('translation');
            $table->string('part_of_speech')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('ready_dictionary_id');
            $table->index('word');
            $table->index('part_of_speech');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ready_dictionary_words');
        Schema::dropIfExists('ready_dictionaries');
    }
};
