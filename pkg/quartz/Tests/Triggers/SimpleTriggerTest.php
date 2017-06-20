<?php
namespace Quartz\Tests\Triggers;

use PHPUnit\Framework\TestCase;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;
use Quartz\Triggers\SimpleTrigger;

class SimpleTriggerTest extends TestCase
{
    public function testShouldImplementTriggerInterface()
    {
        $this->assertInstanceOf(Trigger::class, new SimpleTrigger());
    }

    public function testCouldSetGetRepeatCount()
    {
        $t = new SimpleTrigger();
        $t->setRepeatCount(123);

        $this->assertSame(123, $t->getRepeatCount());
    }

    public function testCouldSetGetRepeatInterval()
    {
        $t = new SimpleTrigger();
        $t->setRepeatInterval(123);

        $this->assertSame(123, $t->getRepeatInterval());
    }

    public function testOnValidateShouldThrowExceptionIfRepeatIntervalIsLessThanOne()
    {
        $t = new SimpleTrigger();
        $t->setRepeatInterval(0);

        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Trigger\'s name cannot be null');

        $t->validate();
    }

    public function testShouldComputeFirstFireTime()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setRepeatInterval(10);

        $this->assertEquals(new \DateTime('2012-12-12 12:00:00'), $t->computeFirstFireTime());
    }

    public function testShouldComputeFireTimeAfter()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setRepeatInterval(10);
        $t->setRepeatCount(SimpleTrigger::REPEAT_INDEFINITELY);

        $this->assertEquals(new \DateTime('2012-12-12 12:00:10'), $t->getFireTimeAfter(new \DateTime('2012-12-12 12:00:00')));
        $this->assertEquals(new \DateTime('2012-12-13 00:00:00'), $t->getFireTimeAfter(new \DateTime('2012-12-12 23:59:55')));
    }

    public function testOnFireTimeAfterShouldReturnNullIfTimesTriggeredMoreThanRepeatCount()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setRepeatInterval(10);
        $t->setTimesTriggered(5);
        $t->setRepeatCount(3);

        $this->assertNull($t->getFireTimeAfter());
    }

    public function testOnFireTimeAfterShouldReturnNullIfRepeatCountZeroAndAfterTimeAfterStartTime()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setRepeatInterval(10);
        $t->setRepeatCount(0);

        $this->assertNull($t->getFireTimeAfter(new \DateTime('2012-12-12 13:00:00')));
    }

    public function testOnFireTimeAfterShouldReturnStartTimeIfAfterTimeBeforeStartTime()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setRepeatInterval(10);
        $t->setRepeatCount(0);

        $this->assertEquals(new \DateTime('2012-12-12 12:00:00'), $t->getFireTimeAfter(new \DateTime('2012-12-12 11:00:00')));
    }

    public function testOnFireTimeAfterShouldReturnNullIfNumTimesExecutedIsMoreThanRepeatCount()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setRepeatInterval(10);
        $t->setRepeatCount(2);

        $this->assertNull($t->getFireTimeAfter(new \DateTime('2012-12-12 12:00:21')));
    }

    public function testOnFireTimeAfterShouldReturnNullIfCalculatedTimeIsAfterEndTime()
    {
        $t = new SimpleTrigger();
        $t->setStartTime(new \DateTime('2012-12-12 12:00:00'));
        $t->setEndTime(new \DateTime('2012-12-12 13:00:00'));
        $t->setRepeatInterval(10);
        $t->setRepeatCount(SimpleTrigger::REPEAT_INDEFINITELY);

        $this->assertNull($t->getFireTimeAfter(new \DateTime('2012-12-12 12:59:55')));
    }

    public function testShouldUpdateAfterMisfireWithFireNowInstruction()
    {
        $t = new SimpleTrigger();
        $t->setRepeatCount(0);
        $t->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW);

        $this->assertNull($t->getNextFireTime());

        $t->updateAfterMisfire();

        $this->assertEquals(new \DateTime(), $t->getNextFireTime(), '', 5); // closer to now
        $this->assertSame(0, $t->getRepeatCount());
    }
}
