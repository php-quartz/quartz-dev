<?php
namespace Quartz\Scheduler;

use Quartz\Core\Trigger;

interface JobRunShell
{
    /**
     * @param StdScheduler $scheduler
     */
    public function initialize(StdScheduler $scheduler);

    /**
     * @param Trigger $trigger
     */
    public function execute(Trigger $trigger);
}
