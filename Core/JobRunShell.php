<?php
namespace Quartz\Core;

interface JobRunShell
{
    /**
     * @param Scheduler $scheduler
     */
    public function initialize(Scheduler $scheduler);

    /**
     * @param Trigger $trigger
     */
    public function execute(Trigger $trigger);
}
