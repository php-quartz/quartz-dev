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

//public interface SchedulerSignaler {
//
//    /*
//     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//     *
//     * Interface.
//     *
//     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//     */
//
//     void notifyTriggerListenersMisfired(Trigger trigger);
//
//     void notifySchedulerListenersFinalized(Trigger trigger);
//
//     void notifySchedulerListenersJobDeleted(JobKey jobKey);
//
//     void signalSchedulingChange(long candidateNewNextFireTime);
//
//     void notifySchedulerListenersError(String string, SchedulerException jpe);
//}

//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> when a <code>{@link Trigger}</code>
//     * has reached the condition in which it will never fire again.
//     * </p>
//     */
//    void triggerFinalized(Trigger trigger);
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
//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> to inform the listener
//     * that it has started.
//     * </p>
//     */
//    void schedulerStarted();
//
//    /**
//     * <p>
//     * Called by the <code>{@link Scheduler}</code> to inform the listener
//     * that it is starting.
//     * </p>
//     */
//    void schedulerStarting();
//
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