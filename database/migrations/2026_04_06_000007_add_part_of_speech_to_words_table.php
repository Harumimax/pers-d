<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->string('part_of_speech')->nullable()->after('word');
            $table->index('part_of_speech');
        });
    }

    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropIndex(['part_of_speech']);
            $table->dropColumn('part_of_speech');
        });
    }
};
