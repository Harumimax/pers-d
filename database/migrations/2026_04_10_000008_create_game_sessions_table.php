<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 32);
            $table->string('direction', 32);
            $table->unsignedInteger('total_words');
            $table->unsignedInteger('correct_answers')->default(0);
            $table->string('status', 32);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('config_snapshot');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
