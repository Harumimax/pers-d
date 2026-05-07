<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeZone;

class TimezoneOptions
{
    /**
     * @return array<int, array{value:string,label:string}>
     */
    public static function build(): array
    {
        $reference = CarbonImmutable::now('UTC');

        return collect(DateTimeZone::listIdentifiers())
            ->map(function (string $timezone) use ($reference): array {
                $offsetSeconds = (new DateTimeZone($timezone))->getOffset($reference);
                $sign = $offsetSeconds >= 0 ? '+' : '-';
                $hours = intdiv(abs($offsetSeconds), 3600);
                $minutes = intdiv(abs($offsetSeconds) % 3600, 60);

                return [
                    'value' => $timezone,
                    'label' => sprintf('%s (UTC%s%02d:%02d)', $timezone, $sign, $hours, $minutes),
                ];
            })
            ->all();
    }
}
