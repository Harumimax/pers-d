<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_random_word_sessions', function (Blueprint $table): void {
            $table->boolean('use_favorites')
                ->default(false)
                ->after('words_count');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_random_word_sessions', function (Blueprint $table): void {
            $table->dropColumn('use_favorites');
        });
    }
};
