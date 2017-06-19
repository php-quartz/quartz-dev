<?php
namespace Quartz\Bridge\Tests\Enqueue;

use Enqueue\Client\ProducerV2Interface;
use Enqueue\Null\NullMessage;
use Enqueue\Rpc\Promise;
use Enqueue\Util\JSON;
use PHPUnit\Framework\TestCase;
use Quartz\Bridge\Enqueue\EnqueueRemoteTransport;
use Quartz\Bridge\Scheduler\RemoteTransport;

class EnqueueRemoteTransportTest extends TestCase
{
    public function testShouldImplementRemoteTransportInterface()
    {
        $producer = $this->createMock(ProducerV2Interface::class);

        $this->assertInstanceOf(RemoteTransport::class, new EnqueueRemoteTransport($producer));
    }

    public function testShouldSendCommandAndReturnResponse()
    {
        $message = new NullMessage();
        $message->setBody(JSON::encode(['key2' => 'value2']));

        $promise = $this->createMock(Promise::class);
        $promise
            ->expects($this->once())
            ->method('receive')
            ->willReturn($message)
        ;

        $producer = $this->createMock(ProducerV2Interface::class);
        $producer
            ->expects($this->once())
            ->method('sendCommand')
            ->with(EnqueueRemoteTransport::COMMAND, ['key' => 'value'], $this->isTrue())
            ->willReturn($promise)
        ;

        $transport = new EnqueueRemoteTransport($producer);
        $response = $transport->request(['key' => 'value']);

        $this->assertSame(['key2' => 'value2'], $response);
    }
}
