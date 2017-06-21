<?php
namespace Quartz\Bridge\Enqueue;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Quartz\Bridge\Scheduler\RpcProtocol;
use Quartz\Core\Scheduler;

class EnqueueRemoteTransportProcessor implements PsrProcessor, CommandSubscriberInterface, QueueSubscriberInterface
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

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return [
            'processorName' => EnqueueRemoteTransport::COMMAND,
            'queueName' => EnqueueRemoteTransport::COMMAND,
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedQueues()
    {
        return [EnqueueRemoteTransport::COMMAND];
    }
}
