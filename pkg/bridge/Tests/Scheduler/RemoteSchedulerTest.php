<?php
namespace Quartz\Bridge\Tests\Scheduler;

use PHPUnit\Framework\TestCase;
use Quartz\Bridge\Scheduler\RemoteScheduler;
use Quartz\Bridge\Scheduler\RemoteTransport;
use Quartz\Bridge\Scheduler\RpcProtocol;
use Quartz\Core\SchedulerException;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\SimpleTrigger;

class RemoteSchedulerTest extends TestCase
{
    public function testShouldDoRemoteCall()
    {
        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $request = ['request'];
        $response = 'response';

        $transport = $this->createMock(RemoteTransport::class);
        $transport
            ->expects($this->once())
            ->method('request')
            ->with($this->identicalTo($request))
            ->willReturn(['key' => 'value'])
        ;

        $rpcProto = $this->createMock(RpcProtocol::class);
        $rpcProto
            ->expects($this->once())
            ->method('encodeRequest')
            ->with('scheduleJob', [$trigger, $job])
            ->willReturn($request)
        ;
        $rpcProto
            ->expects($this->once())
            ->method('decodeValue')
            ->with(['key' => 'value'])
            ->willReturn($response)
        ;

        $scheduler = new RemoteScheduler($transport, $rpcProto);

        $result = $scheduler->scheduleJob($trigger, $job);

        $this->assertSame($response, $result);
    }

    public function testShouldThrowExceptionIfExceptionReceived()
    {
        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $e = new SchedulerException('message');

        $transport = $this->createMock(RemoteTransport::class);
        $transport
            ->expects($this->once())
            ->method('request')
        ;

        $rpcProto = $this->createMock(RpcProtocol::class);
        $rpcProto
            ->expects($this->once())
            ->method('encodeRequest')
            ->willReturn([])
        ;
        $rpcProto
            ->expects($this->once())
            ->method('decodeValue')
            ->willReturn($e)
        ;

        $scheduler = new RemoteScheduler($transport, $rpcProto);

        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('message');

        $scheduler->scheduleJob($trigger, $job);
    }
}
