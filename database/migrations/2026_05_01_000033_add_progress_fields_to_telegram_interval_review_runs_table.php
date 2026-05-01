<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_interval_review_runs', function (Blueprint $table): void {
            $table->unsignedBigInteger('word_list_message_id')->nullable()->after('intro_message_id');
            $table->timestampTz('finished_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_interval_review_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'word_list_message_id',
                'finished_at',
            ]);
        });
    }
};
