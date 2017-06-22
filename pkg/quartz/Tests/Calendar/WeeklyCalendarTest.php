<?php
namespace Quartz\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\WeeklyCalendar;
use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;

class WeeklyCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new WeeklyCalendar());
    }

    public function testShouldReturnDefaultExcludedWeekDays()
    {
        $cal = new WeeklyCalendar();

        $this->assertSame([DateBuilder::SATURDAY, DateBuilder::SUNDAY], $cal->getDaysExcluded());
    }

    public function testOnSetDaysExcludedShouldThrowExceptionIfArrayKeyIsNotDayOfWeek()
    {
        $cal = new WeeklyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of week: "8"');

        $cal->setDaysExcluded([8]);
    }

    public function testShouldSetExcludedDaysOfWeek()
    {
        $cal = new WeeklyCalendar();

        $cal->setDaysExcluded([DateBuilder::MONDAY, DateBuilder::FRIDAY]);

        $this->assertSame([DateBuilder::MONDAY, DateBuilder::FRIDAY], $cal->getDaysExcluded());
    }

    public function testOnSetDayExcludedShouldThrowExceptionIfArgumentIsNotDayOfWeek()
    {
        $cal = new WeeklyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of week: "9"');

        $cal->setDayExcluded(9, true);
    }

    public function testShouldSetExcludedDayOfWeek()
    {
        $cal = new WeeklyCalendar();

        $cal->setDayExcluded(DateBuilder::THURSDAY, true);
        $cal->setDayExcluded(DateBuilder::FRIDAY, true);

        $this->assertSame([
            DateBuilder::THURSDAY,
            DateBuilder::FRIDAY,
            DateBuilder::SATURDAY,
            DateBuilder::SUNDAY
        ], $cal->getDaysExcluded());
    }

    public function testShouldUnsetExcludedDayOfWeek()
    {
        $cal = new WeeklyCalendar();

        $cal->setDayExcluded(DateBuilder::FRIDAY, true);

        $this->assertSame([
            DateBuilder::FRIDAY,
            DateBuilder::SATURDAY,
            DateBuilder::SUNDAY
        ], $cal->getDaysExcluded());

        // unset
        $cal->setDayExcluded(DateBuilder::FRIDAY, false);
        $cal->setDayExcluded(DateBuilder::SUNDAY, false);

        $this->assertSame([
            DateBuilder::SATURDAY,
        ], $cal->getDaysExcluded());
    }

    public function testShouldReturnTrueWhenAllDaysAreExcluded()
    {
        $cal = new WeeklyCalendar();

        $this->assertFalse($cal->areAllDaysExcluded());

        $cal->setDaysExcluded(range(1, 7));

        $this->assertTrue($cal->areAllDaysExcluded());
    }

    public function testShouldReturnTrueWhenTimeIsIncluded()
    {
        $cal = new WeeklyCalendar();

        $cal->setDaysExcluded([4]);

        $included = new \DateTime('2012-12-12 12:12:12');
        $excluded = new \DateTime('2012-12-13 12:12:12');

        // included
        $this->assertSame(3, (int) $included->format('N'));
        $this->assertTrue($cal->isTimeIncluded((int) $included->format('U')));

        // excluded
        $this->assertSame(4, (int) $excluded->format('N'));
        $this->assertFalse($cal->isTimeIncluded((int) $excluded->format('U')));
    }

    public function testShouldReturnNextIncludedTime()
    {
        $cal = new WeeklyCalendar();

        $cal->setDaysExcluded([2, 3, 4, 5]);

        $date = new \DateTime('2012-12-11 12:12:12');

        $this->assertSame(2, (int) $date->format('N'));

        $nextIncludedTime = $cal->getNextIncludedTime((int) $date->format('U'));

        $this->assertInternalType('int', $nextIncludedTime);

        $this->assertEquals(new \DateTime('2012-12-15 00:00:00'), \DateTime::createFromFormat('U', $nextIncludedTime));
    }
}
