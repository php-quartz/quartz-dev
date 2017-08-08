<?php

namespace Quartz\Bridge\Enqueue;

use Interop\Amqp\AmqpContext;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;

class AmqpQuartzDelayStrategyJob implements Job
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * {@inheritdoc}
     */
    public function execute(JobExecutionContext $context)
    {
        $data = $context->getMergedJobDataMap();

        $message = $this->context->createMessage();
        $message->setBody($data['message']['body']);
        $message->setProperties($data['message']['properties']);
        $message->setHeaders($data['message']['headers']);
        $message->setRoutingKey($data['message']['routingKey']);
        $message->setFlags($data['message']['flags']);

        $dest = $data['destination']['isTopic']
            ? $this->context->createTopic($data['destination']['name'])
            : $this->context->createQueue($data['destination']['name'])
        ;

        $this->context->createProducer()->send($dest, $message);
    }
}
