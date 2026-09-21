<?php

namespace Tests\Unit;

use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterWeekCalendar;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class WeeklyMeterWeekCalendarTest extends TestCase
{
    public function test_april_2026_uses_five_weekday_ranges(): void
    {
        $weeks = (new WeeklyMeterWeekCalendar())->weeksForMonth(2026, 4);

        $this->assertSame([
            ['index' => 1, 'label' => 'WEEK 1', 'start' => '2026-04-01', 'end' => '2026-04-03', 'range' => '04/01-04/03'],
            ['index' => 2, 'label' => 'WEEK 2', 'start' => '2026-04-06', 'end' => '2026-04-10', 'range' => '04/06-04/10'],
            ['index' => 3, 'label' => 'WEEK 3', 'start' => '2026-04-13', 'end' => '2026-04-17', 'range' => '04/13-04/17'],
            ['index' => 4, 'label' => 'WEEK 4', 'start' => '2026-04-20', 'end' => '2026-04-24', 'range' => '04/20-04/24'],
            ['index' => 5, 'label' => 'WEEK 5', 'start' => '2026-04-27', 'end' => '2026-04-30', 'range' => '04/27-04/30'],
        ], $weeks);
    }

    public function test_week_ending_friday_maps_to_containing_range(): void
    {
        $calendar = new WeeklyMeterWeekCalendar();
        $weeks = $calendar->weeksForMonth(2026, 4);

        $this->assertSame(3, $calendar->weekIndexForEndingDate($weeks, CarbonImmutable::parse('2026-04-17')));
        $this->assertSame(1, $calendar->weekIndexForEndingDate($weeks, CarbonImmutable::parse('2026-04-03')));
    }
}
