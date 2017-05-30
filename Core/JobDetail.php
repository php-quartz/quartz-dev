<?php
namespace Quartz\Core;

/**
 * Conveys the detail properties of a given <code>Job</code> instance. JobDetails are
 * to be created/defined with {@link JobBuilder}.
 *
 * <p>
 * Quartz does not store an actual instance of a <code>Job</code> class, but
 * instead allows you to define an instance of one, through the use of a <code>JobDetail</code>.
 * </p>
 *
 * <p>
 * <code>Job</code>s have a name and group associated with them, which
 * should uniquely identify them within a single <code>{@link Scheduler}</code>.
 * </p>
 *
 * <p>
 * <code>Trigger</code>s are the 'mechanism' by which <code>Job</code>s
 * are scheduled. Many <code>Trigger</code>s can point to the same <code>Job</code>,
 * but a single <code>Trigger</code> can only point to one <code>Job</code>.
 * </p>
 *
 * @see JobBuilder
 * @see Job
 * @see JobDataMap
 * @see Trigger
 */
interface JobDetail
{
    /**
     * @return Key
     */
    public function getKey();

    /**
     * <p>
     * Return the description given to the <code>Job</code> instance by its
     * creator (if any).
     * </p>
     *
     * @return string|null if no description was set.
     */
    public function getDescription();

    /**
     * <p>
     * Get the <code>JobDataMap</code> that is associated with the <code>Job</code>.
     * </p>
     *
     * @return array
     */
    public function getJobDataMap();

    /**
     * <p>
     * Whether or not the <code>Job</code> should remain stored after it is
     * orphaned (no <code>{@link Trigger}s</code> point to it).
     * </p>
     *
     * <p>
     * If not explicitly set, the default value is <code>false</code>.
     * </p>
     *
     * @return boolean <code>true</code> if the Job should remain persisted after being orphaned.
     */
    public function isDurable();

    /**
     * <p>
     * Instructs the <code>Scheduler</code> whether or not the <code>Job</code>
     * should be re-executed if a 'recovery' or 'fail-over' situation is
     * encountered.
     * </p>
     *
     * <p>
     * If not explicitly set, the default value is <code>false</code>.
     * </p>
     *
     * @see JobExecutionContext#isRecovering()
     *
     * @return bool
     */
    public function requestsRecovery();
}
