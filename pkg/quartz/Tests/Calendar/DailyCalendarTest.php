<?php
namespace Quartz\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\DailyCalendar;
use Quartz\Core\Calendar;

class DailyCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new DailyCalendar());
    }

    public function testShouldReturnFalseWhenTimeIsIncluded()
    {
        $cal = new DailyCalendar();
        $cal->setTimeRange(12, 12, 12, 13, 13, 13);

        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-12 12:12:11')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 12:12:12')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 13:13:13')));
        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-12 13:13:14')));
    }

    public function testShouldReturnTrueWhenTimeIsIncludedIfInvertTimeRangeIsSet()
    {
        $cal = new DailyCalendar();
        $cal->setTimeRange(12, 12, 12, 13, 13, 13);
        $cal->setInvertTimeRange(true);

        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 12:12:11')));
        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-12 12:12:12')));
        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-12 13:13:13')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 13:13:14')));
    }

    public function testShouldReturnNextIncludedTime()
    {
        $cal = new DailyCalendar();
        $cal->setTimeRange(12, 12, 12, 13, 13, 13);

        $nextIncludedTime = $cal->getNextIncludedTime(strtotime('2012-12-12 13:00:00'));

        $this->assertInternalType('int', $nextIncludedTime);

        $this->assertEquals(strtotime('2012-12-12 13:13:14'), $nextIncludedTime);
    }

    public function testShouldReturnNextIncludedTimeWhenTimeIsIncludedIfInvertTimeRangeIsSet()
    {
        $cal = new DailyCalendar();
        $cal->setTimeRange(12, 12, 12, 13, 13, 13);
        $cal->setInvertTimeRange(true);

        $nextIncludedTime = $cal->getNextIncludedTime(strtotime('2012-12-12 13:00:00'));

        $this->assertInternalType('int', $nextIncludedTime);

        $this->assertEquals(new \DateTime('2012-12-12 13:00:01'), \DateTime::createFromFormat('U', $nextIncludedTime));
    }

    public function testShouldThrowExceptionIfEndTimeIsBeforeStartTime()
    {
        $cal = new DailyCalendar();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid time range: 12:12:12 - 12:12:11');

        $cal->setTimeRange(12, 12, 12, 12, 12, 11);
    }
}
