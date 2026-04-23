<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_session_items', function (Blueprint $table): void {
            $table->string('source_type_snapshot')
                ->nullable()
                ->after('correct_answer');
        });
    }

    public function down(): void
    {
        Schema::table('game_session_items', function (Blueprint $table): void {
            $table->dropColumn('source_type_snapshot');
        });
    }
};
