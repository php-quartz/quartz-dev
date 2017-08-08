<?php
namespace Quartz\Bridge\Enqueue;

use Enqueue\Client\ProducerInterface;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;

class EnqueueResponseJob implements Job
{
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
    public function execute(JobExecutionContext $context)
    {
        $data = $context->getMergedJobDataMap();

        if (false == empty($data['topic'])) {
            $this->producer->sendEvent($data['topic'], $data);
        } elseif (false == empty($data['command'])) {
            $this->producer->sendCommand($data['command'], $data);
        } else {
            $context->getTrigger()->setErrorMessage('There is no enqueue topic or command');
            $context->setUnscheduleFiringTrigger();

            return;
        }
    }
}
