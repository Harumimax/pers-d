<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_examples', function (Blueprint $table): void {
            $table->id();
            $table->morphs('exampleable');
            $table->text('example_text');
            $table->text('example_translation')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('source', 50);
            $table->string('source_external_id', 100)->nullable();
            $table->timestamps();

            $table->index(['source', 'source_external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_examples');
    }
};
