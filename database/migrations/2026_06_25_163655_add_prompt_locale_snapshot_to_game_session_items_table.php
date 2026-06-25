<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_session_items', function (Blueprint $table): void {
            $table->string('prompt_locale_snapshot', 10)
                ->nullable()
                ->after('prompt_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_session_items', function (Blueprint $table): void {
            $table->dropColumn('prompt_locale_snapshot');
        });
    }
};
