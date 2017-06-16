<?php
namespace Quartz\Tests\App;

use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrProcessor;
use Quartz\App\RemoteSchedulerProcessor;
use Quartz\App\RpcProtocol;
use Quartz\Core\Scheduler;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\SimpleTrigger;

class RemoteSchedulerProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldImpleentPsrProcessorInterface()
    {
        $processor = new RemoteSchedulerProcessor($this->createSchedulerMock(), $this->createRpcProtocolMock());

        $this->assertInstanceOf(PsrProcessor::class, $processor);
    }

    public function testShouldInvokeSchedulerMethodAndReturnResponse()
    {
        $message = new NullMessage();
        $message->setBody('"request"');

        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $proto = $this->createRpcProtocolMock();
        $proto
            ->expects($this->once())
            ->method('decodeRequest')
            ->with('request')
            ->willReturn(['method' => 'scheduleJob', 'args' => [$trigger, $job]])
        ;
        $proto
            ->expects($this->once())
            ->method('encodeValue')
            ->with('scheduler-result')
            ->willReturn('result')
        ;

        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->once())
            ->method('scheduleJob')
            ->with($this->identicalTo($trigger), $this->identicalTo($job))
            ->willReturn('scheduler-result')
        ;

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new NullMessage('result'))
        ;

        $processor = new RemoteSchedulerProcessor($scheduler, $proto);
        $result = $processor->process($message, $context);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertNotNull($result->getReply());
        $this->assertSame('result', $result->getReply()->getBody());
    }

    public function testOnExceptionShouldEncodeExceptionAndReturn()
    {
        $message = new NullMessage();
        $message->setBody('"request"');

        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $ex = new \Exception();

        $proto = $this->createRpcProtocolMock();
        $proto
            ->expects($this->once())
            ->method('decodeRequest')
            ->with('request')
            ->willReturn(['method' => 'scheduleJob', 'args' => [$trigger, $job]])
        ;
        $proto
            ->expects($this->once())
            ->method('encodeValue')
            ->with($this->identicalTo($ex))
            ->willReturn('result')
        ;

        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->once())
            ->method('scheduleJob')
            ->with($this->identicalTo($trigger), $this->identicalTo($job))
            ->willThrowException($ex)
        ;

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new NullMessage('result'))
        ;

        $processor = new RemoteSchedulerProcessor($scheduler, $proto);
        $result = $processor->process($message, $context);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertNotNull($result->getReply());
        $this->assertSame('result', $result->getReply()->getBody());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RpcProtocol
     */
    private function createRpcProtocolMock()
    {
        return $this->createMock(RpcProtocol::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Scheduler
     */
    private function createSchedulerMock()
    {
        return $this->createMock(Scheduler::class);
    }
}
