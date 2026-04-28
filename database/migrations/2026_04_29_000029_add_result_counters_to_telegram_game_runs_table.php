<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_game_runs', function (Blueprint $table): void {
            $table->unsignedInteger('correct_answers')->default(0)->after('total_words');
            $table->unsignedInteger('incorrect_answers')->default(0)->after('correct_answers');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_game_runs', function (Blueprint $table): void {
            $table->dropColumn(['correct_answers', 'incorrect_answers']);
        });
    }
};
