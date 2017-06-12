<?php
namespace Quartz\Tests\App;

use Enqueue\Client\RpcClient;
use Enqueue\Null\NullMessage;
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

        $rpcClient = $this->createMock(RpcClient::class);
        $rpcClient
            ->expects($this->once())
            ->method('call')
            ->with(RemoteScheduler::TOPIC, $request, 12345)
            ->willReturn($responseMessage)
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

        $scheduler = new RemoteScheduler($rpcClient, $rpcProto);
        $scheduler->setCallTimeout(12345);

        $result = $scheduler->scheduleJob($trigger, $job);

        $this->assertSame($response, $result);
    }

    public function testShouldThrowExceptionIfExceptionReceived()
    {
        $trigger = new SimpleTrigger();
        $job = new JobDetail();

        $responseMessage = new NullMessage();

        $e = new SchedulerException('message');

        $rpcClient = $this->createMock(RpcClient::class);
        $rpcClient
            ->expects($this->once())
            ->method('call')
            ->willReturn($responseMessage)
        ;

        $rpcProto = $this->createMock(RpcProtocol::class);
        $rpcProto
            ->expects($this->once())
            ->method('decodeValue')
            ->willReturn($e)
        ;

        $scheduler = new RemoteScheduler($rpcClient, $rpcProto);

        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('message');

        $scheduler->scheduleJob($trigger, $job);
    }
}
