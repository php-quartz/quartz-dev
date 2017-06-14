<?php
namespace Quartz\Tests\App;

use Enqueue\Client\RpcClient;
use PHPUnit\Framework\TestCase;
use Quartz\App\EnqueueResponseJob;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Scheduler;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\SimpleTrigger;

class EnqueueResponseJobTest extends TestCase
{
    public function testShouldImplementJobInterface()
    {
        $this->assertInstanceOf(Job::class, new EnqueueResponseJob($this->createRpcClientMock()));
    }

    public function testCouldSetGetTimeout()
    {
        $job = new EnqueueResponseJob($this->createRpcClientMock());

        $this->assertSame(5000, $job->getTimeout());

        $job->setTimeout(10000);

        $this->assertSame(10000, $job->getTimeout());
    }

    public function testShouldUnscheduleTrigger()
    {
        $rpc = $this->createRpcClientMock();
        $rpc
            ->expects($this->never())
            ->method('call')
        ;

        $job = new EnqueueResponseJob($rpc);

        $context = new JobExecutionContext($this->createMock(Scheduler::class), new SimpleTrigger(), new JobDetail());

        $job->execute($context);

        $this->assertSame('There is no enqueue topic', $context->getTrigger()->getErrorMessage());
        $this->assertTrue($context->isUnscheduleFiringTrigger());
    }

    public function testShouldMakeRpcCall()
    {
        $rpc = $this->createRpcClientMock();
        $rpc
            ->expects($this->once())
            ->method('call')
            ->with('the-topic', ['topic' => 'the-topic'])
        ;

        $job = new EnqueueResponseJob($rpc);
        $trigger = new SimpleTrigger();
        $trigger->setJobDataMap(['topic' => 'the-topic']);

        $context = new JobExecutionContext($this->createMock(Scheduler::class), $trigger, new JobDetail());

        $job->execute($context);

        $this->assertFalse($context->isUnscheduleFiringTrigger());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RpcClient
     */
    private function createRpcClientMock()
    {
        return $this->createMock(RpcClient::class);
    }
}
