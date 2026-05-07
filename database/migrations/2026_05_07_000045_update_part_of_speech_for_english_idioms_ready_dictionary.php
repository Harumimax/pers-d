<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DICTIONARY_NAME = 'Идеомы английского языка';
    private const LANGUAGE = 'English';
    private const PART_OF_SPEECH = 'stable_expression';

    public function up(): void
    {
        $this->updatePartOfSpeech(self::PART_OF_SPEECH);
    }

    public function down(): void
    {
        $this->updatePartOfSpeech(null);
    }

    private function updatePartOfSpeech(?string $partOfSpeech): void
    {
        DB::transaction(function () use ($partOfSpeech): void {
            $dictionaryId = DB::table('ready_dictionaries')
                ->where('name', self::DICTIONARY_NAME)
                ->where('language', self::LANGUAGE)
                ->value('id');

            if ($dictionaryId === null) {
                return;
            }

            DB::table('ready_dictionaries')
                ->where('id', $dictionaryId)
                ->update([
                    'part_of_speech' => $partOfSpeech,
                    'updated_at' => now(),
                ]);

            DB::table('ready_dictionary_words')
                ->where('ready_dictionary_id', $dictionaryId)
                ->update([
                    'part_of_speech' => $partOfSpeech,
                    'updated_at' => now(),
                ]);
        });
    }
};
