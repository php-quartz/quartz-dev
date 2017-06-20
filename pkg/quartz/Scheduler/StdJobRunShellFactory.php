<?php
namespace Quartz\Scheduler;

use Quartz\Core\Trigger;

class StdJobRunShellFactory implements JobRunShellFactory
{
    /**
     * @var JobRunShell
     */
    private $jobRunShell;

    /**
     * @param JobRunShell $jobRunShell
     */
    public function __construct(JobRunShell $jobRunShell)
    {
        $this->jobRunShell = $jobRunShell;
    }

    /**
     * {@inheritdoc}
     */
    public function createJobRunShell(Trigger $trigger)
    {
        return $this->jobRunShell;
    }
}
