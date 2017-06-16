<?php
namespace Quartz\Tests\Core;

use PHPUnit\Framework\TestCase;
use Quartz\Calendar\HolidayCalendar;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Scheduler;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\SimpleTrigger;

class JobExecutionContextTest extends TestCase
{
    public function testShouldReturnSchedulerInstance()
    {
        $context = new JobExecutionContext(
            $scheduler = $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $this->assertSame($scheduler, $context->getScheduler());
    }

    public function testShouldReturnTriggerInstance()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            $trigger = new SimpleTrigger(),
            new JobDetail()
        );

        $this->assertSame($trigger, $context->getTrigger());
    }

    public function testShouldReturnJobDetailInstance()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            $jobDetail = new JobDetail()
        );

        $this->assertSame($jobDetail, $context->getJobDetail());
    }

    public function testShouldReturnCalendarInstance()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail(),
            $calendar = new HolidayCalendar()
        );

        $this->assertSame($calendar, $context->getCalendar());
    }

    public function testShouldMergeTriggersAndJobsDataMap()
    {
        $trigger = new SimpleTrigger();
        $trigger->setJobDataMap(['a' => 1, 'b'=> 2]);

        $job = new JobDetail();
        $job->setJobDataMap(['b' => 5, 'c' => 3]);

        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            $trigger,
            $job
        );

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $context->getMergedJobDataMap());
    }

    public function testCouldSetGetJobRunTime()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $context->setJobRunTime(12345);

        $this->assertSame(12345, $context->getJobRunTime());
    }

    public function testCouldSetGetResult()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $context->setResult('result');

        $this->assertSame('result', $context->getResult());
    }

    public function testCouldSetGetException()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $context->setException($exception = new \Exception());

        $this->assertSame($exception, $context->getException());
    }

    public function testCouldIncrementRefireFire()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $context->incrementRefireCount();
        $context->incrementRefireCount();
        $context->incrementRefireCount();

        $this->assertSame(3, $context->getRefireCount());
    }

    public function testCouldSetRefireImmediately()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $this->assertFalse($context->isRefireImmediately());

        $context->setRefireImmediately();

        $this->assertTrue($context->isRefireImmediately());
    }

    public function testCouldSetUnscheduleFiringTrigger()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $this->assertFalse($context->isUnscheduleFiringTrigger());

        $context->setUnscheduleFiringTrigger();

        $this->assertTrue($context->isUnscheduleFiringTrigger());
    }

    public function testCouldSetUnscheduleAllTriggers()
    {
        $context = new JobExecutionContext(
            $this->createSchedulerMock(),
            new SimpleTrigger(),
            new JobDetail()
        );

        $this->assertFalse($context->isUnscheduleAllTriggers());

        $context->setUnscheduleAllTriggers();

        $this->assertTrue($context->isUnscheduleAllTriggers());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Scheduler
     */
    private function createSchedulerMock()
    {
        return $this->createMock(Scheduler::class);
    }
}
