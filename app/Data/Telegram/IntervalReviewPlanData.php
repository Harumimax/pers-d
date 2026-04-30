<?php

namespace App\Data\Telegram;

readonly class IntervalReviewPlanData
{
    /**
     * @param array<int, array{
     *     selection_key:string,
     *     source:string,
     *     dictionary_id:int,
     *     dictionary_name:string,
     *     language:string,
     *     word_id:int,
     *     word:string,
     *     translation:string,
     *     part_of_speech:?string,
     *     comment:?string
     * }> $selectedWords
     */
    public function __construct(
        public bool $enabled,
        public string $language,
        public string $startTime,
        public string $timezone,
        public array $selectedWords,
    ) {
    }
}
