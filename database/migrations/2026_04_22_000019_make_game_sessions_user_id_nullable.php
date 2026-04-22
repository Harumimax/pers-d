<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE game_sessions ALTER COLUMN user_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('game_sessions')->whereNull('user_id')->delete();
            DB::statement('ALTER TABLE game_sessions ALTER COLUMN user_id SET NOT NULL');
        }
    }
};
