<?php

namespace Quartz\Bridge\Enqueue;

use Enqueue\AmqpTools\DelayStrategy;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpTopic;
use Quartz\Core\JobBuilder;
use Quartz\Core\Scheduler;
use Quartz\Core\SimpleScheduleBuilder;
use Quartz\Core\TriggerBuilder;

class AmqpQuartzDelayStrategy implements DelayStrategy
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @param Scheduler $scheduler
     */
    public function __construct(Scheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, AmqpMessage $message, $delayMsec)
    {
        $data = [
            'message' => [
                'body' => $message->getBody(),
                'headers' => $message->getHeaders(),
                'properties' => $message->getProperties(),
                'flags' => $message->getFlags(),
                'routingKey' => $message->getRoutingKey(),
            ],
            'destination' =>  [
                'isTopic' => $dest instanceof AmqpTopic,
                'name' => $dest instanceof AmqpTopic ? $dest->getTopicName() : $dest->getQueueName(),
            ]
        ];

        $job = JobBuilder::newJob(AmqpQuartzDelayStrategyJob::class)
            ->setJobData($data)
            ->build()
        ;

        $trigger = TriggerBuilder::newTrigger()
            ->forJobDetail($job)
            ->startAt(new \DateTime(sprintf('+%s seconds', $delayMsec / 1000)))
            ->withSchedule(SimpleScheduleBuilder::simpleSchedule())
            ->build()
        ;

        $this->scheduler->scheduleJob($trigger, $job);
    }
}
