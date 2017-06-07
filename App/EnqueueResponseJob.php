<?php
namespace Quartz\App;

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

        if (empty($data['topic'])) {
            $context->getTrigger()->setErrorMessage('There is no enqueue topic');

            $context->setUnscheduleFiringTrigger();

            return;
        }

        $this->producer->send($data['topic'], $data);
    }
}
