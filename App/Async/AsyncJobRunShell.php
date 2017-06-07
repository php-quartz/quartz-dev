<?php
namespace Quartz\App\Async;

use Enqueue\Client\ProducerInterface;
use Quartz\Core\JobRunShell;
use Quartz\Core\Scheduler;
use Quartz\Core\Trigger;

class AsyncJobRunShell implements JobRunShell
{
    const TOPIC = 'quartz.job_run_shell';

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
    public function initialize(Scheduler $scheduler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Trigger $trigger)
    {
        $this->producer->send(self::TOPIC, [
            'fireInstanceId' => $trigger->getFireInstanceId(),
        ]);
    }
}
