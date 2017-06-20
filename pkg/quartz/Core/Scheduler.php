<?php
namespace Quartz\Core;

interface Scheduler
{
    ///////////////////////////////////////////////////////////////////////////
    ///
    /// Scheduler State Management Methods
    ///
    ///////////////////////////////////////////////////////////////////////////

    public function start();

    ///////////////////////////////////////////////////////////////////////////
    ///
    /// Scheduling-related Methods
    ///
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Clears (deletes!) all scheduling data - all {@link Job}s, {@link Trigger}s
     * {@link Calendar}s.
     *
     * @throws SchedulerException
     */
    public function clear();
    /**
     * @param Trigger   $trigger
     * @param JobDetail $jobDetail
     *
     * @return \DateTime
     *
     * @throws SchedulerException
     */
    public function scheduleJob(Trigger $trigger, JobDetail $jobDetail = null);

    /**
     * Add the given <code>Job</code> to the Scheduler - with no associated
     * <code>Trigger</code>. The <code>Job</code> will be 'dormant' until
     * it is scheduled with a <code>Trigger</code>, or <code>Scheduler.triggerJob()</code>
     * is called for it.
     *
     * <p>
     * With the <code>storeNonDurableWhileAwaitingScheduling</code> parameter
     * set to <code>true</code>, a non-durable job can be stored.  Once it is
     * scheduled, it will resume normal non-durable behavior (i.e. be deleted
     * once there are no remaining associated triggers).
     * </p>
     *
     * @param JobDetail $jobDetail
     * @param bool      $replace
     * @param bool      $storeNonDurableWhileAwaitingScheduling
     *
     * @throws SchedulerException
     *           if there is an internal Scheduler error, or if the Job is not
     *           durable, or a Job with the same name already exists, and
     *           <code>replace</code> is <code>false</code>.
     */
    public function addJob(JobDetail $jobDetail, $replace = false, $storeNonDurableWhileAwaitingScheduling = false);

    /**
     * Delete the identified <code>Job</code>s from the Scheduler - and any
     * associated <code>Trigger</code>s.
     *
     * <p>Note that while this bulk operation is likely more efficient than
     * invoking <code>deleteJob(JobKey jobKey)</code> several
     * times, it may have the adverse affect of holding data locks for a
     * single long duration of time (rather than lots of small durations
     * of time).</p>
     *
     * @param Key[] $jobKeys
     *
     * @return bool true if all of the Jobs were found and deleted, false if
     * one or more were not deleted.
     *
     * @throws SchedulerException
     *           if there is an internal Scheduler error.
     */
    public function deleteJobs(array $jobKeys);

    /**
     * Remove all of the indicated <code>{@link Trigger}</code>s from the scheduler.
     *
     * <p>If the related job does not have any other triggers, and the job is
     * not durable, then the job will also be deleted.</p>
     *
     * <p>Note that while this bulk operation is likely more efficient than
     * invoking <code>unscheduleJob(TriggerKey triggerKey)</code> several
     * times, it may have the adverse affect of holding data locks for a
     * single long duration of time (rather than lots of small durations
     * of time).</p>
     *
     * @param Key[] $triggerKeys
     */
    public function unscheduleJobs(array $triggerKeys);

    /**
     * <p>
     * Remove the indicated <code>{@link org.quartz.Trigger}</code> from the
     * scheduler.
     * </p>
     *
     * @param Key $triggerKey
     *
     * @return bool
     */
    public function unscheduleJob(Key $triggerKey);

    /**
     * <p>
     * Delete the identified <code>Job</code> from the Scheduler - and any
     * associated <code>Trigger</code>s.
     * </p>
     *
     * @param Key $jobKey
     *
     * @return true if the Job was found and deleted.
     *
     * @throws SchedulerException
     *           if there is an internal Scheduler error.
     */
    public function deleteJob(Key $jobKey);

    /**
     * <p>
     * Remove (delete) the <code>{@link org.quartz.Trigger}</code> with the
     * given name, and store the new given one - which must be associated
     * with the same job.
     * </p>
     *
     * @param Key $triggerKey
     * @param Trigger $newTrigger
     *          The new <code>Trigger</code> to be stored.
     *
     * @return \DateTime <code>null</code> if a <code>Trigger</code> with the given
     *         name & group was not found and removed from the store, otherwise
     *         the first fire time of the newly scheduled trigger.
     *
     * @throws SchedulerException
     */
    public function rescheduleJob(Key $triggerKey, Trigger $newTrigger);

    /**
     * <p>
     * Trigger the identified <code>{@link org.quartz.Job}</code> (execute it
     * now) - with a non-volatile trigger.
     * </p>
     *
     * @param Key   $jobKey
     * @param array $jobDataMap
     */
    public function triggerJob(Key $jobKey, array $jobDataMap = []);

    /**
     * <p>
     * Pause the <code>{@link Trigger}</code> with the given name.
     * </p>
     *
     * @param Key $triggerKey
     */
    public function pauseTrigger(Key $triggerKey);

    /**
     * <p>
     * Pause the <code>{@link org.quartz.JobDetail}</code> with the given
     * name - by pausing all of its current <code>Trigger</code>s.
     * </p>
     *
     * @param Key $jobKey
     */
    public function pauseJob(Key $jobKey);

    /**
     * @return string[]
     */
    public function getPausedTriggerGroups();

    /**
     * <p>
     * Resume (un-pause) the <code>{@link Trigger}</code> with the given
     * name.
     * </p>
     *
     * <p>
     * If the <code>Trigger</code> missed one or more fire-times, then the
     * <code>Trigger</code>'s misfire instruction will be applied.
     * </p>
     *
     * @param Key $triggerKey
     *
     */
    public function resumeTrigger(Key $triggerKey);

    /**
     * <p>
     * Resume (un-pause) the <code>{@link org.quartz.JobDetail}</code> with
     * the given name.
     * </p>
     *
     * <p>
     * If any of the <code>Job</code>'s<code>Trigger</code> s missed one
     * or more fire-times, then the <code>Trigger</code>'s misfire
     * instruction will be applied.
     * </p>
     *
     * @param Key $jobKey
     */
    public function resumeJob(Key $jobKey);

    /**
     * <p>
     * Pause all triggers - equivalent of calling <code>pauseTriggers(GroupMatcher<TriggerKey>)</code>
     * with a matcher matching all known groups.
     * </p>
     *
     * <p>
     * When <code>resumeAll()</code> is called (to un-pause), trigger misfire
     * instructions WILL be applied.
     * </p>
     */
    public function pauseAll();

    /**
     * <p>
     * Resume (un-pause) all triggers - equivalent of calling <code>resumeTriggerGroup(group)</code>
     * on every group.
     * </p>
     *
     * <p>
     * If any <code>Trigger</code> missed one or more fire-times, then the
     * <code>Trigger</code>'s misfire instruction will be applied.
     * </p>
     *
     * @see #pauseAll()
     */
    public function resumeAll();

    /**
     * <p>
     * Get the names of all known <code>{@link org.quartz.Job}</code> groups.
     * </p>
     *
     * @return string[]
     */
    public function getJobGroupNames();

    /**
     * <p>
     * Get the names of all known <code>{@link org.quartz.Trigger}</code>
     * groups.
     * </p>
     *
     * @return string[]
     */
    public function getTriggerGroupNames();

    /**
     * <p>
     * Get all <code>{@link Trigger}</code> s that are associated with the
     * identified <code>{@link org.quartz.JobDetail}</code>.
     * </p>
     *
     * @param Key $jobKey
     *
     * @return Trigger[]
     */
    public function getTriggersOfJob(Key $jobKey);

    /**
     * <p>
     * Get the <code>{@link JobDetail}</code> for the <code>Job</code>
     * instance with the given name and group.
     * </p>
     *
     * @param Key $jobKey
     *
     * @return JobDetail
     */
    public function getJobDetail(Key $jobKey);

    /**
     * <p>
     * Get the <code>{@link Trigger}</code> instance with the given name and
     * group.
     * </p>
     *
     * @param Key $triggerKey
     *
     * @return Trigger
     */
    public function getTrigger(Key $triggerKey);

    /**
     * <p>
     * Get the current state of the identified <code>{@link Trigger}</code>.
     * </p>
     *
     * @param Key $triggerKey
     *
     * @return string
     */
    public function getTriggerState(Key $triggerKey);

    /**
     * <p>
     * Add (register) the given <code>Calendar</code> to the Scheduler.
     * </p>
     *
     * @param string   $calName
     * @param Calendar $calendar
     * @param bool     $replace
     * @param bool     $updateTriggers
     *
     * @throws SchedulerException
     *           if there is an internal Scheduler error, or a Calendar with
     *           the same name already exists, and <code>replace</code> is
     *           <code>false</code>.
     */
    public function addCalendar($calName, Calendar $calendar, $replace = false, $updateTriggers = false);

    /**
     * <p>
     * Delete the identified <code>Calendar</code> from the Scheduler.
     * </p>
     *
     * @param string $calName
     *
     * @return true if the Calendar was found and deleted.
     *
     * @throws SchedulerException
     *           if there is an internal Scheduler error.
     */
    public function deleteCalendar($calName);

    /**
     * <p>
     * Get the <code>{@link Calendar}</code> instance with the given name.
     * </p>
     *
     * @param string $calName
     *
     * @return Calendar
     */
    public function getCalendar($calName);

    /**
     * <p>
     * Get the names of all registered <code>{@link Calendar}s</code>.
     * </p>
     *
     * @return string
     */
    public function getCalendarNames();

    /**
     * Determine whether a {@link Job} with the given identifier already
     * exists within the scheduler.
     *
     * @param Key $jobKey the identifier to check for
     *
     * @return true if a Job exists with the given identifier
     *
     * @throws SchedulerException
     */
    public function checkJobExists(Key $jobKey);

    /**
     * Determine whether a {@link Trigger} with the given identifier already
     * exists within the scheduler.
     *
     * @param Key $triggerKey the identifier to check for
     *
     * @return true if a Trigger exists with the given identifier
     *
     * @throws SchedulerException
     */
    public function checkTriggerExists(Key $triggerKey);

    /**
     * Reset the current state of the identified <code>{@link Trigger}</code>
     * from {@link TriggerState#ERROR} to {@link TriggerState#NORMAL} or
     * {@link TriggerState#PAUSED} as appropriate.
     *
     * <p>Only affects triggers that are in ERROR state - if identified trigger is not
     * in that state then the result is a no-op.</p>
     *
     * <p>The result will be the trigger returning to the normal, waiting to
     * be fired state, unless the trigger's group has been paused, in which
     * case it will go into the PAUSED state.</p>
     *
     * @param Key $triggerKey
     */
    function resetTriggerFromErrorState(Key $triggerKey);

//    /**
//     * <p>
//     * Pause all of the <code>{@link Trigger}s</code> in the matching groups.
//     * </p>
//     *
//     */
//    public function pauseTriggers(GroupMatcher $matcher)

//    /**
//     * <p>
//     * Calls the equivalent method on the 'proxied' <code>QuartzScheduler</code>.
//     * </p>
//     */
//    public function pauseJobs(GroupMatcher $matcher)

//    /**
//     * <p>
//     * Calls the equivalent method on the 'proxied' <code>QuartzScheduler</code>.
//     * </p>
//     */
//    public function resumeTriggers(GroupMatcher $matcher)

//    /**
//     * <p>
//     * Calls the equivalent method on the 'proxied' <code>QuartzScheduler</code>.
//     * </p>
//     */
//    public function resumeJobs(GroupMatcher $matcher)

//    /**
//     * <p>
//     * Calls the equivalent method on the 'proxied' <code>QuartzScheduler</code>.
//     * </p>
//     *
//     * @return Key[]
//     */
//    public function getJobKeys(GroupMatcher $matcher)

//    /**
//     * <p>
//     * Calls the equivalent method on the 'proxied' <code>QuartzScheduler</code>.
//     * </p>
//     *
//     * @return Key[]
//     */
//    public function getTriggerKeys(GroupMatcher $matcher)
}
