<?php
namespace Quartz\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\HolidayCalendar;
use Quartz\Core\Calendar;

class HolidayCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new HolidayCalendar());
    }

    public function testCouldAddExcludedDateAndSetTimeToMidnight()
    {
        $cal = new HolidayCalendar();
        $cal->addExcludedDate(new \DateTime('2012-12-12 12:12:12'));

        $excludedDates = $cal->getExcludedDates();

        $this->assertCount(1, $excludedDates);
        $this->assertEquals(new \DateTime('2012-12-12 00:00:00'), $excludedDates[0]);
    }

    public function testCouldRemoveExcludedDate()
    {
        $cal = new HolidayCalendar();
        $cal->addExcludedDate(new \DateTime('2012-12-12 12:12:12'));
        $cal->addExcludedDate(new \DateTime('2012-12-13 12:12:12'));

        $this->assertCount(2, $cal->getExcludedDates());

        $cal->removeExcludedDate(new \DateTime('2012-12-13 23:01:02'));
        $excludedDates = $cal->getExcludedDates();

        $this->assertCount(1, $cal->getExcludedDates());
        $this->assertEquals(new \DateTime('2012-12-12 00:00:00'), $excludedDates[0]);
    }

    public function testShouldReturnTrueFalseIfTimeIncludedOrNot()
    {
        $cal = new HolidayCalendar();
        $cal->addExcludedDate(new \DateTime('2012-12-12 12:12:12'));

        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-11 13:12:12')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 13:12:12')));
        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-13 13:12:12')));
    }

    public function testShouldReturnNextIncludedTime()
    {
        $cal = new HolidayCalendar();
        $cal->addExcludedDate(new \DateTime('2012-12-10 12:12:12'));
        $cal->addExcludedDate(new \DateTime('2012-12-11 12:12:12'));
        $cal->addExcludedDate(new \DateTime('2012-12-12 12:12:12'));
        $cal->addExcludedDate(new \DateTime('2012-12-13 12:12:12'));

        $nextIncludedTime = $cal->getNextIncludedTime(strtotime('2012-12-11 13:12:12'));

        $this->assertSame(strtotime('2012-12-14 00:00:00'), $nextIncludedTime);
    }
}
