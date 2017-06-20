<?php
namespace Quartz\Tests\Triggers;

use function Makasim\Values\set_value;
use PHPUnit\Framework\TestCase;
use Quartz\Core\Calendar;
use Quartz\Core\CompletedExecutionInstruction;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;
use Quartz\Triggers\AbstractTrigger;

class AbstractTriggerTest extends TestCase
{
    public function testShouldImplementTriggerInterface()
    {
        $this->assertInstanceOf(Trigger::class, new AbstractTriggerImpl());
    }

    public function testShouldReturnDefaultPriority()
    {
        $trigger = new AbstractTriggerImpl();

        $this->assertSame(Trigger::DEFAULT_PRIORITY, $trigger->getPriority());
    }

    public function testShouldReturnDefaultMisfireInstruction()
    {
        $trigger = new AbstractTriggerImpl();

        $this->assertSame(Trigger::MISFIRE_INSTRUCTION_SMART_POLICY, $trigger->getMisfireInstruction());
    }

    public function testShouldReturnDefaultTimesTriggered()
    {
        $trigger = new AbstractTriggerImpl();

        $this->assertSame(0, $trigger->getTimesTriggered());
    }

    public function testShouldReturnInstanceName()
    {
        $trigger = new AbstractTriggerImpl();

        $this->assertSame('abstract', $trigger->getInstance());
    }

    public function testCouldSetGeyKey()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setKey($key = new Key('name'));

        $this->assertTrue($key->equals($trigger->getKey()));
    }

    public function testCouldSetGetJobKey()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setJobKey($key = new Key('name'));

        $this->assertTrue($key->equals($trigger->getJobKey()));
    }

    public function testCouldSetGetDescription()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setDescription('the description');

        $this->assertSame('the description', $trigger->getDescription());
    }

    public function testCouldSetGetCalendarName()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setCalendarName('the calendar name');

        $this->assertSame('the calendar name', $trigger->getCalendarName());
    }

    public function testCouldSetGetJobDataMap()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setJobDataMap(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $trigger->getJobDataMap());
    }

    public function testCouldSetGetPriority()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setPriority(10);

        $this->assertSame(10, $trigger->getPriority());
    }

    public function testCouldSetGetStartTime()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setStartTime(new \DateTime('2012-12-12 12:12:12'));

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $trigger->getStartTime());
    }

    public function testShouldThrowExceptionIfStartTimeIsGreaterThanEndTime()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End time cannot be before start time');

        $trigger = new AbstractTriggerImpl();
        $trigger->setEndTime(new \DateTime('2012-12-12 12:12:12'));
        $trigger->setStartTime(new \DateTime('2012-12-12 12:12:13'));
    }

    public function testCouldSetGetEndTime()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setEndTime(new \DateTime('2012-12-12 12:12:12'));

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $trigger->getEndTime());
    }

    public function testShouldThrowExceptionIfEndTimeIsLessThanStartTime()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End time cannot be before start time');

        $trigger = new AbstractTriggerImpl();
        $trigger->setStartTime(new \DateTime('2012-12-12 12:12:13'));
        $trigger->setEndTime(new \DateTime('2012-12-12 12:12:12'));
    }

    public function testCouldSetGetNextFireTime()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(new \DateTime('2012-12-12 12:12:12'));

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $trigger->getNextFireTime());
    }

    public function testCouldSetGetPreviousFireTime()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setPreviousFireTime(new \DateTime('2012-12-12 12:12:12'));

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $trigger->getPreviousFireTime());
    }

    public function testCouldSetGetState()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setState('the state');

        $this->assertSame('the state', $trigger->getState());
    }

    public function testCouldSetGetTimesTriggered()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setTimesTriggered(123);

        $this->assertSame(123, $trigger->getTimesTriggered());
    }

    public function testCouldSetGetFireInstanceId()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setFireInstanceId('the id');

        $this->assertSame('the id', $trigger->getFireInstanceId());
    }

    public function testCouldSetGetFireTime()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setFireTime(new \DateTime('2012-12-12 12:12:12'));

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $trigger->getFireTime());
    }

    public function testCouldSetGetScheduledFireTime()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setScheduledFireTime(new \DateTime('2012-12-12 12:12:12'));

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $trigger->getScheduledFireTime());
    }

    public function testCouldSetGetErrorMessage()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setErrorMessage('the error message');

        $this->assertSame('the error message', $trigger->getErrorMessage());
    }

    public function testCouldSetGetMisfireInstruction()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setMisfireInstruction(Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY);

        $this->assertSame(Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY, $trigger->getMisfireInstruction());
    }

    public function testShouldThrowExceptionIfMisfireInstructionIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The misfire instruction code is invalid for this type of trigger.');

        $trigger = new AbstractTriggerImpl();
        $trigger->setMisfireInstruction('invalid misfire instruction');
    }

    public function testCouldSetGetTimeZone()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setTimeZone(new \DateTimeZone('Europe/Simferopol'));

        $this->assertSame('Europe/Simferopol', $trigger->getTimeZone()->getName());
    }

    public function testOnValidateShouldThrowExceptionIfNameIsNotSet()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Trigger\'s name cannot be null');

        $trigger = new AbstractTriggerImpl();
        $trigger->validate();
    }

    public function testOnValidateShouldThrowExceptionIfGroupIsNotSet()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Trigger\'s group cannot be null');

        $trigger = new AbstractTriggerImpl();
        set_value($trigger, 'name', 'name');

        $trigger->validate();
    }

    public function testOnValidateShouldThrowExceptionIfJobNameIsNotSet()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Trigger\'s related Job\'s name cannot be null');

        $trigger = new AbstractTriggerImpl();
        $trigger->setKey(new Key('name'));

        $trigger->validate();
    }

    public function testOnValidateShouldThrowExceptionIfJobGroupIsNotSet()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Trigger\'s related Job\'s group cannot be null');

        $trigger = new AbstractTriggerImpl();
        $trigger->setKey(new Key('name'));
        set_value($trigger, 'jobName', 'name');

        $trigger->validate();
    }

    public function testOnExecutionCompleteShouldReturnNoopIfNoAnyOtherInstruction()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(new \DateTime());

        $context = $this->createMock(JobExecutionContext::class);

        $this->assertSame(CompletedExecutionInstruction::NOOP, $trigger->executionComplete($context));
    }

    public function testOnExecutionCompleteShouldReturnDeleteIfMayNotFireAgain()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(null);

        $context = $this->createMock(JobExecutionContext::class);

        $this->assertSame(CompletedExecutionInstruction::DELETE_TRIGGER, $trigger->executionComplete($context));
    }

    public function testOnExecutionCompleteShouldReturnReExecuteJobIfSetInContext()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(null);

        $context = $this->createMock(JobExecutionContext::class);
        $context
            ->expects($this->once())
            ->method('isRefireImmediately')
            ->willReturn(true)
        ;

        $this->assertSame(CompletedExecutionInstruction::RE_EXECUTE_JOB, $trigger->executionComplete($context));
    }

    public function testOnExecutionCompleteShouldReturnUnscheduleFiringTriggerIfSetInContext()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(null);

        $context = $this->createMock(JobExecutionContext::class);
        $context
            ->expects($this->once())
            ->method('isUnscheduleFiringTrigger')
            ->willReturn(true)
        ;

        $this->assertSame(CompletedExecutionInstruction::SET_TRIGGER_COMPLETE, $trigger->executionComplete($context));
    }

    public function testOnExecutionCompleteShouldReturnUnscheduleAllTriggersIfSetInContext()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(null);

        $context = $this->createMock(JobExecutionContext::class);
        $context
            ->expects($this->once())
            ->method('isUnscheduleAllTriggers')
            ->willReturn(true)
        ;

        $this->assertSame(CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE, $trigger->executionComplete($context));
    }

    public function testMayFireAgainShouldReturnTrueIfNextFireTimeIsNotNull()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(new \DateTime());

        $this->assertTrue($trigger->mayFireAgain());
    }

    public function testMayFireAgainShouldReturnFalseIfNextFireTimeIsNull()
    {
        $trigger = new AbstractTriggerImpl();
        $trigger->setNextFireTime(null);

        $this->assertFalse($trigger->mayFireAgain());
    }
}

class AbstractTriggerImpl extends AbstractTrigger
{
    public function __construct()
    {
        parent::__construct('abstract');
    }

    public function getInstance()
    {
        return $this->getValue('instance');
    }

    protected function validateMisfireInstruction($candidateMisfireInstruction)
    {
        return in_array($candidateMisfireInstruction, [
            Trigger::MISFIRE_INSTRUCTION_SMART_POLICY,
            Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY
        ], true);
    }

    public function getFireTimeAfter(\DateTime $afterTime = null){}
    public function getFinalFireTime(){}
    public function updateAfterMisfire(Calendar $cal = null){}
}
