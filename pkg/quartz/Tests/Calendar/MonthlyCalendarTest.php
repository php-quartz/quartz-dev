<?php
namespace Quartz\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\MonthlyCalendar;
use Quartz\Core\Calendar;

class MonthlyCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new MonthlyCalendar());
    }

    public function testOnSetDaysExcludedShouldThrowExceptionIfIsNotDayOfMonth()
    {
        $cal = new MonthlyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of month (must be >= 1 and <= 31).');

        $cal->setDaysExcluded([32]);
    }

    public function testShouldSetExcludedDaysOfMonth()
    {
        $cal = new MonthlyCalendar();

        $cal->setDaysExcluded([1, 5, 10]);

        $this->assertSame([1, 5, 10], $cal->getDaysExcluded());
    }

    public function testOnSetDayExcludedShouldThrowExceptionIfArgumentIsNotDayOfMonth()
    {
        $cal = new MonthlyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of month (must be >= 1 and <= 31).');

        $cal->setDayExcluded(32, true);
    }

    public function testShouldSetExcludedDayOfMonth()
    {
        $cal = new MonthlyCalendar();

        $cal->setDayExcluded(3, true);
        $cal->setDayExcluded(5, true);

        $this->assertSame([3, 5], $cal->getDaysExcluded());
    }

    public function testShouldUnsetExcludedDayOfMonth()
    {
        $cal = new MonthlyCalendar();

        $cal->setDayExcluded(3, true);
        $cal->setDayExcluded(5, true);
        $cal->setDayExcluded(10, true);

        $this->assertSame([3, 5, 10], $cal->getDaysExcluded());

        //unset
        $cal->setDayExcluded(5, false);
        $this->assertSame([3, 10], $cal->getDaysExcluded());
    }

    public function testShouldReturnTrueWhenAllDaysAreExcluded()
    {
        $cal = new MonthlyCalendar();

        $this->assertFalse($cal->areAllDaysExcluded());

        $cal->setDaysExcluded(range(1, 31));

        $this->assertTrue($cal->areAllDaysExcluded());
    }

    public function testShouldReturnTrueWhenTimeIsIncluded()
    {
        $cal = new MonthlyCalendar();

        $cal->setDaysExcluded([13]);

        $included = new \DateTime('2012-12-12 12:12:12');
        $excluded = new \DateTime('2012-12-13 12:12:12');

        // included
        $this->assertTrue($cal->isTimeIncluded((int) $included->format('U')));

        // excluded
        $this->assertFalse($cal->isTimeIncluded((int) $excluded->format('U')));
    }

    public function testShouldReturnNextIncludedTime()
    {
        $cal = new MonthlyCalendar();

        $cal->setDaysExcluded([11, 12, 13, 14, 15]);

        $date = new \DateTime('2012-12-11 12:12:12');

        $nextIncludedTime = $cal->getNextIncludedTime((int) $date->format('U'));

        $this->assertInternalType('int', $nextIncludedTime);

        $this->assertEquals(new \DateTime('2012-12-16 00:00:00'), \DateTime::createFromFormat('U', $nextIncludedTime));
    }
}
