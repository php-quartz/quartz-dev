<?php
namespace Quartz\Events;

use Symfony\Component\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent
{
    /**
     * Called by the <code>{@link Scheduler}</code> to inform the listener
     * that all jobs, triggers and calendars were deleted.
     */
    const SCHEDULING_DATA_CLEARED = 'scheduling_data_cleared';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * has been added.
     * </p>
     */
    const JOB_ADDED = 'job_added';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * is scheduled.
     * </p>
     */
    const JOB_SCHEDULED = 'job_scheduled';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * has been deleted.
     * </p>
     */
    const JOB_DELETED = 'job_deleted';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * is unscheduled.
     * </p>
     */
    const JOB_UNSCHEDULED = 'job_unscheduled';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
     * has been paused.
     * </p>
     */
    const TRIGGER_PAUSED = 'trigger_paused';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * has been paused.
     * </p>
     */
    const JOB_PAUSED = 'job_paused';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
     * has been un-paused.
     * </p>
     */
    const TRIGGER_RESUMED = 'trigger_resumed';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * has been un-paused.
     * </p>
     */
    const JOB_RESUMED = 'job_resume';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a
     * group of <code>{@link Trigger}s</code> has been paused.
     * </p>
     *
     * <p>If all groups were paused then triggerGroup will be null</p>
     *
     * @param string $triggerGroup the paused group, or null if all were paused
     */
    const TRIGGERS_PAUSED = 'triggers_paused';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a
     * group of <code>{@link Trigger}s</code> has been un-paused.
     * </p>
     */
    const TRIGGERS_RESUMED = 'triggers_resumed';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
     * has fired, and it's associated <code>{@link org.quartz.JobDetail}</code>
     * is about to be executed.
     * </p>
     *
     * <p>
     * It is called before the <code>vetoJobExecution(..)</code> method of this
     * interface.
     * </p>
     *
     * @param JobExecutionContext $context
     */
    const TRIGGER_FIRED = 'trigger_fired';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
     * has reached the condition in which it will never fire again.
     * </p>
     *
     * @param Trigger
     */
    const TRIGGER_FINALIZED = 'trigger_finalized';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
     * has fired, it's associated <code>{@link org.quartz.JobDetail}</code>
     * has been executed, and it's <code>triggered(xx)</code> method has been
     * called.
     * </p>
     *
     */
    const TRIGGER_COMPLETE = 'trigger_complete';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
     * has misfired.
     * </p>
     *
     * <p>
     * Consideration should be given to how much time is spent in this method,
     * as it will affect all triggers that are misfiring.  If you have lots
     * of triggers misfiring at once, it could be an issue it this method
     * does a lot.
     * </p>
     *
     * @param Trigger
     */
    const TRIGGER_MISFIRED = 'trigger_misfired';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * was about to be executed (an associated <code>{@link Trigger}</code>
     * has occurred), but a <code>{@link TriggerListener}</code> vetoed it's
     * execution.
     * </p>
     *
     * @param JobExecutionContext $context
     */
    const JOB_EXECUTION_VETOED = 'job_execution_vetoed';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> when a <code>{@link org.quartz.JobDetail}</code>
     * is about to be executed (an associated <code>{@link Trigger}</code>
     * has occurred).
     * </p>
     *
     * <p>
     * This method will not be invoked if the execution of the Job was vetoed
     * by a <code>{@link TriggerListener}</code>.
     * </p>
     *
     * @param JobExecutionContext
     */
    const JOB_TO_BE_EXECUTED = 'job_to_be_executed';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> after a <code>{@link org.quartz.JobDetail}</code>
     * has been executed, and be for the associated <code>Trigger</code>'s
     * <code>triggered(xx)</code> method has been called.
     * </p>
     *
     * @param JobExecutionContext
     */
    const JOB_WAS_EXECUTED = 'job_was_executed';


    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> to inform the listener
     * that it has started.
     * </p>
     */
    const SCHEDULER_STARTED = 'scheduler_started';

    /**
     * <p>
     * Called by the <code>{@link Scheduler}</code> to inform the listener
     * that it is starting.
     * </p>
     */
    const SCHEDULER_STARTING = 'scheduler_starting';

//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> when a
//     * group of <code>{@link org.quartz.JobDetail}s</code> has been paused.
//     * </p>
//     *
//     * @param jobGroup the paused group, or null if all were paused
//     */
//    void jobsPaused(String jobGroup);
//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> when a
//     * group of <code>{@link org.quartz.JobDetail}s</code> has been un-paused.
//     * </p>
//     */
//    void jobsResumed(String jobGroup);
//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> when a serious error has
//     * occurred within the scheduler - such as repeated failures in the <code>JobStore</code>,
//     * or the inability to instantiate a <code>Job</code> instance when its
//     * <code>Trigger</code> has fired.
//     * </p>
//     *
//     * <p>
//     * The <code>getErrorCode()</code> method of the given SchedulerException
//     * can be used to determine more specific information about the type of
//     * error that was encountered.
//     * </p>
//     */
//    void schedulerError(String msg, SchedulerException cause);
//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> to inform the listener
//     * that it has move to standby mode.
//     * </p>
//     */
//    void schedulerInStandbyMode();
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> to inform the listener
//     * that it has shutdown.
//     * </p>
//     */
//    void schedulerShutdown();
//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> to inform the listener
//     * that it has begun the shutdown sequence.
//     * </p>
//     */
//    void schedulerShuttingdown();
}