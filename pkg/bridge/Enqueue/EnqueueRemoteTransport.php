<?php

namespace Quartz\Bridge\Enqueue;

use Enqueue\Client\ProducerV2Interface;
use Enqueue\Util\JSON;
use Quartz\Bridge\Scheduler\RemoteTransport;

class EnqueueRemoteTransport implements RemoteTransport
{
    const COMMAND = 'quartz_rpc';

    /**
     * @var ProducerV2Interface
     */
    private $producer;

    /**
     * @param ProducerV2Interface $producer
     */
    public function __construct(ProducerV2Interface $producer)
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
