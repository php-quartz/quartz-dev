<?php

namespace Quartz\Bridge\Enqueue;

use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Quartz\Bridge\Scheduler\RemoteTransport;

class EnqueueRemoteTransport implements RemoteTransport
{
    const COMMAND = 'quartz_rpc';

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function request(array $parameters)
    {
        $responseMessage = $this->producer->sendCommand(self::COMMAND, $parameters, true)->receive();

        return JSON::decode($responseMessage->getBody());
    }
}
