<?php
namespace Quartz\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\WeeklyCalendar;
use Quartz\Core\Calendar;

class WeeklyCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new WeeklyCalendar());
    }

    public function testShouldReturnDefaultExcludedWeekDays()
    {
        $expectedWDays = [
            1 => false,
            2 => false,
            3 => false,
            4 => false,
            5 => false,
            6 => true,
            7 => true,
        ];

        $cal = new WeeklyCalendar();

        $this->assertSame($expectedWDays, $cal->getDaysExcluded());
    }

    public function testOnSetDaysExcludedShouldThrowExceptionIfNotAllDaysAreSet()
    {
        $cal = new WeeklyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not all week days were set: "1,2"');

        $cal->setDaysExcluded([1 => true, 2 => false]);
    }

    public function testOnSetDaysExcludedShouldThrowExceptionIfArrayKeyIsNotDayOfWeek()
    {
        $cal = new WeeklyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of week: "8"');

        $cal->setDaysExcluded([
            8 => true, 2 => false, 3 => true, 4 => false, 5 => true, 6 => false, 7 => true,
        ]);
    }

    public function testOnSetDaysExcludedShouldThrowExceptionIfArrayValueIsNotBool()
    {
        $cal = new WeeklyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array must contain only bool values. True - excludes day of week.');

        $cal->setDaysExcluded([
            1 => true, 2 => false, 3 => 'string', 4 => false, 5 => true, 6 => false, 7 => true,
        ]);
    }

    public function testShouldSetExcludedDaysOfWeek()
    {
        $cal = new WeeklyCalendar();

        $cal->setDaysExcluded([
            1 => true, 2 => false, 3 => true, 4 => false, 5 => true, 6 => false, 7 => true,
        ]);

        $expectedWDays = [
            1 => true,
            2 => false,
            3 => true,
            4 => false,
            5 => true,
            6 => false,
            7 => true,
        ];

        $this->assertSame($expectedWDays, $cal->getDaysExcluded());
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

        $cal->setDaysExcluded([
            1 => false,
            2 => false,
            3 => false,
            4 => false,
            5 => false,
            6 => false,
            7 => false,
        ]);

        $cal->setDayExcluded(3, true);
        $cal->setDayExcluded(5, true);

        $expectedWDays = [
            1 => false,
            2 => false,
            3 => true,
            4 => false,
            5 => true,
            6 => false,
            7 => false,
        ];

        $this->assertSame($expectedWDays, $cal->getDaysExcluded());
    }

    public function testShouldReturnTrueWhenAllDaysAreExcluded()
    {
        $cal = new WeeklyCalendar();

        $this->assertFalse($cal->areAllDaysExcluded());

        $cal->setDaysExcluded([
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => true,
            7 => true,
        ]);

        $this->assertTrue($cal->areAllDaysExcluded());
    }

    public function testShouldReturnTrueWhenTimeIsIncluded()
    {
        $cal = new WeeklyCalendar();

        $cal->setDaysExcluded([
            1 => false,
            2 => false,
            3 => false,
            4 => true,
            5 => false,
            6 => false,
            7 => false,
        ]);

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

        $cal->setDaysExcluded([
            1 => false,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => false,
            7 => false,
        ]);

        $date = new \DateTime('2012-12-11 12:12:12');

        $this->assertSame(2, (int) $date->format('N'));

        $nextIncludedTime = $cal->getNextIncludedTime((int) $date->format('U'));

        $this->assertInternalType('int', $nextIncludedTime);

        $this->assertEquals(new \DateTime('2012-12-15 00:00:00'), \DateTime::createFromFormat('U', $nextIncludedTime));
    }
}
