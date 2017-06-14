<?php
namespace Quartz\App;

use Enqueue\Client\ProducerV2Interface;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;

class EnqueueResponseJob implements Job
{
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
