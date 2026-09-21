<?php

namespace App\Domain\WeeklyAnalysis\Reports;

use Carbon\CarbonImmutable;

class WeeklyMeterWeekCalendar
{
    /**
     * Monday–Friday business weeks clipped to the calendar month.
     *
     * @return list<array{index:int, label:string, start:string, end:string, range:string}>
     */
    public function weeksForMonth(int $year, int $month): array
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->endOfMonth()->startOfDay();
        $cursor = $monthStart;
        $weeks = [];

        while ($cursor->lte($monthEnd)) {
            $isoDay = (int) $cursor->dayOfWeekIso;

            if ($isoDay >= 6) {
                $cursor = $cursor->addDay();

                continue;
            }

            $daysUntilFriday = 5 - $isoDay;
            $weekEnd = $cursor->addDays($daysUntilFriday);

            if ($weekEnd->gt($monthEnd)) {
                $weekEnd = $monthEnd;
            }

            $index = count($weeks) + 1;
            $weeks[] = [
                'index' => $index,
                'label' => 'WEEK '.$index,
                'start' => $cursor->toDateString(),
                'end' => $weekEnd->toDateString(),
                'range' => $cursor->format('m/d').'-'.$weekEnd->format('m/d'),
            ];

            $cursor = $weekEnd->addDay();
        }

        return $weeks;
    }

    /**
     * @param  list<array{index:int, start:string, end:string}>  $weeks
     */
    public function weekIndexForEndingDate(array $weeks, CarbonImmutable $weekEnding): ?int
    {
        $matchDate = $weekEnding->startOfDay();

        if ((int) $matchDate->dayOfWeekIso >= 6) {
            $matchDate = $matchDate->subDays((int) $matchDate->dayOfWeekIso - 5);
        }

        foreach ($weeks as $week) {
            $start = CarbonImmutable::parse($week['start'])->startOfDay();
            $end = CarbonImmutable::parse($week['end'])->startOfDay();

            if ($matchDate->betweenIncluded($start, $end)) {
                return (int) $week['index'];
            }
        }

        return null;
    }
}
