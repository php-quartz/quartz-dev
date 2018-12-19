<?php
namespace Quartz\Bridge\Enqueue;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Quartz\Bridge\Scheduler\RpcProtocol;
use Quartz\Core\Scheduler;

class EnqueueRemoteTransportProcessor implements Processor, CommandSubscriberInterface, QueueSubscriberInterface
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var RpcProtocol
     */
    private $rpcProtocol;

    /**
     * @param Scheduler   $scheduler
     * @param RpcProtocol $rpcProtocol
     */
    public function __construct(Scheduler $scheduler, RpcProtocol $rpcProtocol)
    {
        $this->scheduler = $scheduler;
        $this->rpcProtocol = $rpcProtocol;
    }

    public function process(Message $message, Context $context): Result
    {
        try {
            $request = $this->rpcProtocol->decodeRequest(JSON::decode($message->getBody()));

            $result = call_user_func_array([$this->scheduler, $request['method']], $request['args']);
            $result = $this->rpcProtocol->encodeValue($result);
        } catch (\Exception $e) {
            $result = $this->rpcProtocol->encodeValue($e);
        }

        return Result::reply($context->createMessage(JSON::encode($result)));
    }

    public static function getSubscribedCommand(): array
    {
        return [
            'command' => EnqueueRemoteTransport::COMMAND,
            'queue' => EnqueueRemoteTransport::COMMAND,
            'prefix_queue' => false,
            'exclusive' => true,
        ];
    }

    public static function getSubscribedQueues(): array
    {
        return [EnqueueRemoteTransport::COMMAND];
    }
}
