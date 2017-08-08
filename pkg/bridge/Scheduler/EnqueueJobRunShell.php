<?php
namespace Quartz\Bridge\Scheduler;

use Enqueue\Client\ProducerInterface;
use Quartz\Scheduler\JobRunShell;
use Quartz\Core\Trigger;
use Quartz\Scheduler\StdScheduler;

class EnqueueJobRunShell implements JobRunShell
{
    const COMMAND = 'quartz_job_run_shell';

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
    public function initialize(StdScheduler $scheduler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Trigger $trigger)
    {
        $this->producer->sendCommand(self::COMMAND, [
            'fireInstanceId' => $trigger->getFireInstanceId(),
        ], false);
    }
}
