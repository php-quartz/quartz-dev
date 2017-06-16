<?php
namespace Quartz\Tests\Calendar;

use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
use PHPUnit\Framework\TestCase;
use Quartz\Calendar\BaseCalendar;
use Quartz\Calendar\HolidayCalendar;
use Quartz\Core\Calendar;

class BaseCalendarTest extends TestCase
{
    public function testShouldImplementCalendarInterface()
    {
        $this->assertInstanceOf(Calendar::class, new BaseCalendarImpl(''));
    }

    public function testShouldSetInstanceName()
    {
        $cal = new BaseCalendarImpl('base-calendar');

        $this->assertSame('base-calendar', $cal->getInstance());
    }

    public function testCouldSetGetBaseCalendar()
    {
        $baseCal = new HolidayCalendar(); // have to use real calendar
        $baseCal->setDescription('the description');

        $cal = new BaseCalendarImpl('');

        // set base calendar avoid object cache
        set_value($cal, 'baseCalendar', get_values($baseCal));

        $this->assertNotNull($cal->getBaseCalendar());
        $this->assertInstanceOf(HolidayCalendar::class, $cal->getBaseCalendar());
        $this->assertSame('the description', $cal->getBaseCalendar()->getDescription());
    }

    public function testCouldSetGetDescription()
    {
        $cal = new BaseCalendarImpl('');
        $cal->setDescription('the description');

        $this->assertSame('the description', $cal->getDescription());
    }

    public function testOnIsTimeIncludedShouldReturnTrueIfBaseCalendarNotSet()
    {
        $cal = new BaseCalendarImpl('');

        $this->assertTrue($cal->isTimeIncluded(1111));
    }

    public function testOnIsTimeIncludedShouldThrowExceptionIfTimeIsLessThenZero()
    {
        $cal = new BaseCalendarImpl('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('timeStamp must be greater 0');

        $cal->isTimeIncluded(-1);
    }

    public function testOnIsTimeIncludedShouldCallBaseIsTimeIncludedMethod()
    {
        $cal = new BaseCalendarImpl('');
        $cal->setBaseCalendar(new BaseCalendarImpl2(''));

        $this->assertSame(12345, $cal->isTimeIncluded(12345));
    }

    public function testOnGetNextIncludedTimeShouldReturnTimeIfBaseCalendarNotSet()
    {
        $cal = new BaseCalendarImpl('');

        $this->assertSame(1111, $cal->getNextIncludedTime(1111));
    }

    public function testOnGetNextIncludedTimeShouldThrowExceptionIfTimeIsLessThenZero()
    {
        $cal = new BaseCalendarImpl('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('timeStamp must be greater 0');

        $cal->getNextIncludedTime(-1);
    }

    public function testOnGetNextIncludedTimeShouldCallBaseIsTimeIncludedMethod()
    {
        $cal = new BaseCalendarImpl('');
        $cal->setBaseCalendar(new BaseCalendarImpl2(''));

        $this->assertSame(12345+1, $cal->getNextIncludedTime(12345));
    }

    public function testCouldGetSetTimezone()
    {
        $cal = new BaseCalendarImpl('');
        $cal->setTimeZone(new \DateTimeZone('Europe/Simferopol'));

        $this->assertNotNull($cal->getTimeZone());
        $this->assertInstanceOf(\DateTimeZone::class, $cal->getTimeZone());
        $this->assertSame('Europe/Simferopol', $cal->getTimeZone()->getName());
    }
}

class BaseCalendarImpl extends BaseCalendar
{
    public $isTimeIncludedReturnValue;

    public function getInstance()
    {
        return $this->getValue('instance');
    }
}

class BaseCalendarImpl2 extends BaseCalendar
{
    public function isTimeIncluded($timeStamp)
    {
        return $timeStamp;
    }

    public function getNextIncludedTime($timeStamp)
    {
        return $timeStamp + 1;
    }
}
