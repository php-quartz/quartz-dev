<?php
namespace Quartz\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\CronCalendar;
use Quartz\Core\Calendar;

class CronCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new CronCalendar());
    }

    public function testShouldReturnTrueFalseIfTimeIncludedOrNot()
    {
        $cal = new CronCalendar();
        // exclude all but business hours (8AM - 5PM) every
        $cal->setCronExpression('* * 0-7,18-23 ? * *');

        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 00:00:00')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-12 07:59:59')));
        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-12 08:00:00')));
        $this->assertTrue($cal->isTimeIncluded(strtotime('2012-12-12 17:59:59')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-13 18:00:00')));
        $this->assertFalse($cal->isTimeIncluded(strtotime('2012-12-13 23:59:59')));
    }

    public function testShouldReturnNextInvalidTimeAfter()
    {
        $cal = new CronCalendar();
        $cal->setCronExpression('0 */1 * * * *');

        $invalidTime = $cal->getNextInvalidTimeAfter(new \DateTime('2012-12-12 12:00:59'));

        $this->assertEquals(new \DateTime('2012-12-12 12:01:01'), $invalidTime);
    }

    public function testShouldReturnNextIncludedTime()
    {
        $cal = new CronCalendar();
        $cal->setCronExpression('0 */1 * * * *');

        $invalidTime = $cal->getNextIncludedTime(strtotime('2012-12-12 12:00:59'));
        $this->assertEquals(strtotime('2012-12-12 12:01:01'), $invalidTime);

        $invalidTime = $cal->getNextIncludedTime(strtotime('2012-12-12 12:01:10'));
        $this->assertEquals(strtotime('2012-12-12 12:01:11'), $invalidTime);
    }
}
