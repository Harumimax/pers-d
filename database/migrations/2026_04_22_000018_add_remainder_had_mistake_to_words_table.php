<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table): void {
            $table->boolean('remainder_had_mistake')->default(false)->after('comment');
        });

        DB::table('words')
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('user_dictionary_word')
                    ->whereColumn('user_dictionary_word.word_id', 'words.id');
            })
            ->update(['remainder_had_mistake' => false]);
    }

    public function down(): void
    {
        Schema::table('words', function (Blueprint $table): void {
            $table->dropColumn('remainder_had_mistake');
        });
    }
};
