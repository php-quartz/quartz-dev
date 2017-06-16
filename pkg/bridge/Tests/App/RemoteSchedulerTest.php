<?php
namespace Quartz\Tests\App;

use Enqueue\Client\ProducerV2Interface;
use Enqueue\Null\NullMessage;
use Enqueue\Rpc\Promise;
use Enqueue\Util\JSON;
use PHPUnit\Framework\TestCase;
use Quartz\App\RemoteScheduler;
use Quartz\App\RpcProtocol;
use Quartz\Core\SchedulerException;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\SimpleTrigger;

class RemoteSchedulerTest extends TestCase
{
    public function testShouldDoRemoteCall()
    {
        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $request = 'request';
        $response = 'response';

        $responseMessage = new NullMessage(JSON::encode(['key' => 'value']));

        $promise = $this->createMock(Promise::class);
        $promise
            ->expects($this->once())
            ->method('receive')
            ->willReturn($responseMessage)
        ;

        $producer = $this->createMock(ProducerV2Interface::class);
        $producer
            ->expects($this->once())
            ->method('sendCommand')
            ->with(RemoteScheduler::COMMAND, $request)
            ->willReturn($promise)
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

        $scheduler = new RemoteScheduler($producer, $rpcProto);

        $result = $scheduler->scheduleJob($trigger, $job);

        $this->assertSame($response, $result);
    }

    public function testShouldThrowExceptionIfExceptionReceived()
    {
        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $responseMessage = new NullMessage();

        $e = new SchedulerException('message');

        $promise = $this->createMock(Promise::class);
        $promise
            ->expects($this->once())
            ->method('receive')
            ->willReturn($responseMessage)
        ;

        $producer = $this->createMock(ProducerV2Interface::class);
        $producer
            ->expects($this->once())
            ->method('sendCommand')
            ->willReturn($promise)
        ;

        $rpcProto = $this->createMock(RpcProtocol::class);
        $rpcProto
            ->expects($this->once())
            ->method('decodeValue')
            ->willReturn($e)
        ;

        $scheduler = new RemoteScheduler($producer, $rpcProto);

        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('message');

        $scheduler->scheduleJob($trigger, $job);
    }
}
