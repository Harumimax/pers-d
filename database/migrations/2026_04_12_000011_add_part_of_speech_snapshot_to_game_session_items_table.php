<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_session_items', function (Blueprint $table): void {
            $table->string('part_of_speech_snapshot')->nullable()->after('prompt_text');
        });
    }

    public function down(): void
    {
        Schema::table('game_session_items', function (Blueprint $table): void {
            $table->dropColumn('part_of_speech_snapshot');
        });
    }
};
