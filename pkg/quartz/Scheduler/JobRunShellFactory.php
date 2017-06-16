<?php
namespace Quartz\Scheduler;

use Quartz\Core\Trigger;

/**
 * <p>
 * Responsible for creating the instances of <code>{@link JobRunShell}</code>
 * to be used within the <class>{@link QuartzScheduler}</code> instance.
 * </p>
 */
interface JobRunShellFactory
{
    /**
     * <p>
     * Called by the <code>{@link org.quartz.core.QuartzSchedulerThread}</code>
     * to obtain instances of <code>{@link JobRunShell}</code>.
     * </p>
     *
     * @param Trigger $trigger
     *
     * @return JobRunShell
     */
    public function createJobRunShell(Trigger $trigger); // orig TriggerFiredBundle
}
