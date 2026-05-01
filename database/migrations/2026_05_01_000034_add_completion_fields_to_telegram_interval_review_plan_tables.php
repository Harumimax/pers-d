<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_interval_review_plans', function (Blueprint $table): void {
            $table->unsignedSmallInteger('completed_sessions_count')->default(0)->after('words_count');
            $table->timestampTz('completed_at')->nullable()->after('completed_sessions_count');
        });

        Schema::table('telegram_interval_review_runs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('correct_answers')->default(0)->after('total_words');
            $table->unsignedSmallInteger('incorrect_answers')->default(0)->after('correct_answers');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_interval_review_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'correct_answers',
                'incorrect_answers',
            ]);
        });

        Schema::table('telegram_interval_review_plans', function (Blueprint $table): void {
            $table->dropColumn([
                'completed_sessions_count',
                'completed_at',
            ]);
        });
    }
};
