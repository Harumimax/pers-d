<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tg_chat_id')->nullable()->after('tg_login');
            $table->timestamp('tg_linked_at')->nullable()->after('tg_chat_id');
            $table->unique('tg_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['tg_chat_id']);
            $table->dropColumn(['tg_chat_id', 'tg_linked_at']);
        });
    }
};
