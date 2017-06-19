<?php
namespace Quartz\Bridge\Tests\Enqueue;

use Enqueue\Client\ProducerV2Interface;
use PHPUnit\Framework\TestCase;
use Quartz\Bridge\Enqueue\EnqueueResponseJob;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Scheduler;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\SimpleTrigger;

class EnqueueResponseJobTest extends TestCase
{
    public function testShouldImplementJobInterface()
    {
        $this->assertInstanceOf(Job::class, new EnqueueResponseJob($this->createProducerMock()));
    }

    public function testShouldUnscheduleTrigger()
    {
        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $producer
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $job = new EnqueueResponseJob($producer);

        $context = new JobExecutionContext($this->createMock(Scheduler::class), new SimpleTrigger(), new JobDetail());

        $job->execute($context);

        $this->assertSame('There is no enqueue topic or command', $context->getTrigger()->getErrorMessage());
        $this->assertTrue($context->isUnscheduleFiringTrigger());
    }

    public function testShouldSendEvent()
    {
        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
            ->with('the-topic', ['topic' => 'the-topic'])
        ;

        $job = new EnqueueResponseJob($producer);
        $trigger = new SimpleTrigger();
        $trigger->setJobDataMap(['topic' => 'the-topic']);

        $context = new JobExecutionContext($this->createMock(Scheduler::class), $trigger, new JobDetail());

        $job->execute($context);

        $this->assertFalse($context->isUnscheduleFiringTrigger());
    }

    public function testShouldSendCommand()
    {
        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendCommand')
            ->with('the-command', ['command' => 'the-command'])
        ;

        $job = new EnqueueResponseJob($producer);
        $trigger = new SimpleTrigger();
        $trigger->setJobDataMap(['command' => 'the-command']);

        $context = new JobExecutionContext($this->createMock(Scheduler::class), $trigger, new JobDetail());

        $job->execute($context);

        $this->assertFalse($context->isUnscheduleFiringTrigger());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerV2Interface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerV2Interface::class);
    }
}
