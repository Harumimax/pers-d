<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_game_runs', function (Blueprint $table): void {
            $table->timestampTz('last_interaction_at')->nullable()->after('cancelled_at');
            $table->string('last_error_code')->nullable()->after('last_interaction_at');
            $table->text('last_error_message')->nullable()->after('last_error_code');
            $table->timestampTz('last_error_at')->nullable()->after('last_error_message');
        });

        Schema::create('telegram_processed_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_update_id')->nullable();
            $table->string('callback_query_id')->nullable();
            $table->string('chat_id')->nullable();
            $table->string('update_type');
            $table->string('status');
            $table->unsignedInteger('attempts')->default(1);
            $table->text('last_error_message')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestamps();

            $table->unique('telegram_update_id');
            $table->unique('callback_query_id');
            $table->index(['status', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_processed_updates');

        Schema::table('telegram_game_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'last_error_code',
                'last_error_message',
                'last_error_at',
                'last_interaction_at',
            ]);
        });
    }
};
