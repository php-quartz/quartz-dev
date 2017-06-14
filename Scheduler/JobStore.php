<?php
namespace Quartz\Scheduler;

use Quartz\Core\Calendar;
use Quartz\Core\JobDetail;
use Quartz\Core\JobPersistenceException;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;
use Quartz\Core\ObjectAlreadyExistsException;

interface JobStore
{
    /**
     * @param StdScheduler $scheduler
     */
    public function initialize(StdScheduler $scheduler);

    /**
     * Called by the Scheduler to inform the <code>JobStore</code> that
     * the scheduler has started.
     */
    public function schedulerStarted();

    /////////////////////////////////////////////////////////////////////////////
    //
    // Job & Trigger Storage methods
    //
    /////////////////////////////////////////////////////////////////////////////

    /**
     * Store the given <code>{@link org.quartz.JobDetail}</code> and <code>{@link org.quartz.Trigger}</code>.
     *
     * @param JobDetail $newJob The <code>JobDetail</code> to be stored.
     * @param Trigger   $newTrigger The <code>Trigger</code> to be stored.
     *
     * @throws ObjectAlreadyExistsException
     *           if a <code>Job</code> with the same name/group already
     *           exists.
     */
    public function storeJobAndTrigger(JobDetail $newJob, Trigger $newTrigger);

    /**
     * Store the given <code>{@link org.quartz.JobDetail}</code>.
     *
     * @param JobDetail $newJob
     *          The <code>JobDetail</code> to be stored.
     * @param bool $replaceExisting
     *          If <code>true</code>, any <code>Job</code> existing in the
     *          <code>JobStore</code> with the same name & group should be
     *          over-written.
     * @throws ObjectAlreadyExistsException
     *           if a <code>Job</code> with the same name/group already
     *           exists, and replaceExisting is set to false.
     */
    public function storeJob(JobDetail $newJob, $replaceExisting = false);

    /**
     * Remove (delete) the <code>{@link org.quartz.Job}</code> with the given
     * key, and any <code>{@link org.quartz.Trigger}</code> s that reference
     * it.
     *
     * <p>
     * If removal of the <code>Job</code> results in an empty group, the
     * group should be removed from the <code>JobStore</code>'s list of
     * known group names.
     * </p>
     *
     * @return bool <code>true</code> if a <code>Job</code> with the given name &
     *         group was found and removed from the store.
     */
    public function removeJob(Key $jobKey);
    public function removeJobs(array $jobKeys);

    /**
     * Retrieve the <code>{@link org.quartz.JobDetail}</code> for the given
     * <code>{@link org.quartz.Job}</code>.
     *
     * @param Key $jobKey
     *
     * @return JobDetail The desired <code>Job</code>, or null if there is no match.
     */
    public function retrieveJob(Key $jobKey);

    /**
     * Store the given <code>{@link Quartz\Core\Trigger}</code>.
     *
     * @param Trigger $newTrigger
     *          The <code>Trigger</code> to be stored.
     * @param bool $replaceExisting
     *          If <code>true</code>, any <code>Trigger</code> existing in
     *          the <code>JobStore</code> with the same name & group should
     *          be over-written.
     * @throws ObjectAlreadyExistsException
     *           if a <code>Trigger</code> with the same name/group already
     *           exists, and replaceExisting is set to false.
     *
     * @see #pauseTriggers(org.quartz.impl.matchers.GroupMatcher)
     */
    public function storeTrigger(Trigger $newTrigger, $replaceExisting = false);

    /**
     * Remove (delete) the <code>{@link org.quartz.Trigger}</code> with the
     * given key.
     *
     * <p>
     * If removal of the <code>Trigger</code> results in an empty group, the
     * group should be removed from the <code>JobStore</code>'s list of
     * known group names.
     * </p>
     *
     * <p>
     * If removal of the <code>Trigger</code> results in an 'orphaned' <code>Job</code>
     * that is not 'durable', then the <code>Job</code> should be deleted
     * also.
     * </p>
     *
     * @return bool <code>true</code> if a <code>Trigger</code> with the given
     *         name & group was found and removed from the store.
     */
    public function removeTrigger(Key $triggerKey);
    public function removeTriggers(array $triggerKeys);

    /**
     * Remove (delete) the <code>{@link org.quartz.Trigger}</code> with the
     * given key, and store the new given one - which must be associated
     * with the same job.
     *
     * @param newTrigger
     *          The new <code>Trigger</code> to be stored.
     *
     * @return <code>true</code> if a <code>Trigger</code> with the given
     *         name & group was found and removed from the store.
     */
    public function replaceTrigger(Key $triggerKey, Trigger $newTrigger);

    /**
     * Retrieve the given <code>{@link org.quartz.Trigger}</code>.
     *
     * @return Trigger The desired <code>Trigger</code>, or null if there is no
     *         match.
     */
    public function retrieveTrigger(Key $triggerKey);

    /**
     * Determine whether a {@link Job} with the given identifier already
     * exists within the scheduler.
     *
     * @param Key $jobKey the identifier to check for
     *
     * @return bool true if a Job exists with the given identifier
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
     * @return bool true if a Trigger exists with the given identifier
     *
     * @throws SchedulerException
     */
    public function checkTriggerExists(Key $triggerKey);

    /**
     * Clear (delete!) all scheduling data - all {@link Job}s, {@link Trigger}s
     * {@link Calendar}s.
     *
     * @throws JobPersistenceException
     */
    public function clearAllSchedulingData();

    /**
     * Store the given <code>{@link Quartz\Core\Calendar}</code>.
     *
     * @param string   $name
     * @param Calendar $calendar The <code>Calendar</code> to be stored.
     * @param boolean  $replaceExisting
     *          If <code>true</code>, any <code>Calendar</code> existing
     *          in the <code>JobStore</code> with the same name & group
     *          should be over-written.
     * @param boolean  $updateTriggers
     *          If <code>true</code>, any <code>Trigger</code>s existing
     *          in the <code>JobStore</code> that reference an existing
     *          Calendar with the same name with have their next fire time
     *          re-computed with the new <code>Calendar</code>.
     * @throws ObjectAlreadyExistsException
     *           if a <code>Calendar</code> with the same name already
     *           exists, and replaceExisting is set to false.
     */
    public function storeCalendar($name, Calendar $calendar, $replaceExisting = false, $updateTriggers = false);

    /**
     * Remove (delete) the <code>{@link org.quartz.Calendar}</code> with the
     * given name.
     *
     * <p>
     * If removal of the <code>Calendar</code> would result in
     * <code>Trigger</code>s pointing to non-existent calendars, then a
     * <code>JobPersistenceException</code> will be thrown.</p>
     *       *
     * @param string $calName The name of the <code>Calendar</code> to be removed.
     * @return boolean <code>true</code> if a <code>Calendar</code> with the given name
     * was found and removed from the store.
     */
    public function removeCalendar($calName);

    /**
     * Retrieve the given <code>{@link Core\Trigger}</code>.
     *
     * @param string $calName The name of the <code>Calendar</code> to be retrieved.
     *
     * @return Calendar|null The desired <code>Calendar</code>, or null if there is no match.
     */
    public function retrieveCalendar($calName);

    /////////////////////////////////////////////////////////////////////////////
    //
    // Informational methods
    //
    /////////////////////////////////////////////////////////////////////////////

    /**
     * Get all of the Triggers that are associated to the given Job.
     *
     * <p>
     * If there are no matches, a zero-length array should be returned.
     * </p>
     *
     * @param Key $jobKey
     *
     * @return Trigger[]
     */
    public function getTriggersForJob(Key $jobKey);

    /**
     * Get the names of all of the <code>{@link org.quartz.Job}</code>
     * groups.
     *
     * <p>
     * If there are no known group names, the result should be a zero-length
     * array (not <code>null</code>).
     * </p>
     *
     * @return string[]
     */
    public function getJobGroupNames();

    /**
     * Get the names of all of the <code>{@link org.quartz.Trigger}</code>
     * groups.
     *
     * <p>
     * If there are no known group names, the result should be a zero-length
     * array (not <code>null</code>).
     * </p>
     *
     * @return string[]
     */
    public function getTriggerGroupNames();

    /**
     * Get the current state of the identified <code>{@link Trigger}</code>.
     *
     * @param Key $triggerKey
     *
     * @return string
     */
    public function getTriggerState(Key $triggerKey);

    /**
     * Get the names of all of the <code>{@link org.quartz.Calendar}</code> s
     * in the <code>JobStore</code>.
     *
     * <p>
     * If there are no Calendars in the given group name, the result should be
     * a zero-length array (not <code>null</code>).
     * </p>
     *
     * @return string[]
     */
    public function getCalendarNames();

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
    public function resetTriggerFromErrorState(Key $triggerKey);

    /////////////////////////////////////////////////////////////////////////////
    //
    // Trigger State manipulation methods
    //
    /////////////////////////////////////////////////////////////////////////////

    /**
     * Pause the <code>{@link org.quartz.Trigger}</code> with the given key.
     *
     * @see #resumeTrigger(TriggerKey)
     */
    public function  pauseTrigger(Key $triggerKey);

    /**
     * Pause all of the <code>{@link org.quartz.Trigger}s</code> in the
     * given group.
     *
     *
     * <p>
     * The JobStore should "remember" that the group is paused, and impose the
     * pause on any new triggers that are added to the group while the group is
     * paused.
     * </p>
     *
     * @see #resumeTriggers(GroupMatcher)
     */
//    public function  pauseTriggers(GroupMatcher $matcher);

    /**
     * Pause the <code>{@link org.quartz.Job}</code> with the given name - by
     * pausing all of its current <code>Trigger</code>s.
     *
     * @see #resumeJob(JobKey)
     */
    public function  pauseJob(Key $jobKey);

    /**
     * Pause all of the <code>{@link org.quartz.Job}s</code> in the given
     * group - by pausing all of their <code>Trigger</code>s.
     *
     * <p>
     * The JobStore should "remember" that the group is paused, and impose the
     * pause on any new jobs that are added to the group while the group is
     * paused.
     * </p>
     *
     * @see #resumeJobs(GroupMatcher)
     */
//    public function  pauseJobs(GroupMatcher $groupMatcher);

    /**
     * Resume (un-pause) the <code>{@link org.quartz.Trigger}</code> with the
     * given key.
     *
     * <p>
     * If the <code>Trigger</code> missed one or more fire-times, then the
     * <code>Trigger</code>'s misfire instruction will be applied.
     * </p>
     *
     * @see #pauseTrigger(TriggerKey)
     */
    public function  resumeTrigger(Key $triggerKey);

    /**
     * Resume (un-pause) all of the <code>{@link org.quartz.Trigger}s</code>
     * in the given group.
     *
     * <p>
     * If any <code>Trigger</code> missed one or more fire-times, then the
     * <code>Trigger</code>'s misfire instruction will be applied.
     * </p>
     *
     * @see #pauseTriggers(GroupMatcher)
     */
//    public function resumeTriggers(GroupMatcher $matcher);

    /**
     * @return string[]
     */
    public function getPausedTriggerGroups();

    /**
     * Resume (un-pause) the <code>{@link org.quartz.Job}</code> with the
     * given key.
     *
     * <p>
     * If any of the <code>Job</code>'s<code>Trigger</code> s missed one
     * or more fire-times, then the <code>Trigger</code>'s misfire
     * instruction will be applied.
     * </p>
     *
     * @see #pauseJob(JobKey)
     */
    public function  resumeJob(Key $jobKey);

    /**
     * Resume (un-pause) all of the <code>{@link org.quartz.Job}s</code> in
     * the given group.
     *
     * <p>
     * If any of the <code>Job</code> s had <code>Trigger</code> s that
     * missed one or more fire-times, then the <code>Trigger</code>'s
     * misfire instruction will be applied.
     * </p>
     *
     * @see #pauseJobs(GroupMatcher)
     */
//    public function  resumeJobs(GroupMatcher $matcher);

    /**
     * Pause all triggers - equivalent of calling <code>pauseTriggerGroup(group)</code>
     * on every group.
     *
     * <p>
     * When <code>resumeAll()</code> is called (to un-pause), trigger misfire
     * instructions WILL be applied.
     * </p>
     *
     * @see #resumeAll()
     * @see #pauseTriggers(GroupMatcher)
     */
    public function  pauseAll();

    /**
     * Resume (un-pause) all triggers - equivalent of calling <code>resumeTriggerGroup(group)</code>
     * on every group.
     *
     * <p>
     * If any <code>Trigger</code> missed one or more fire-times, then the
     * <code>Trigger</code>'s misfire instruction will be applied.
     * </p>
     *
     * @see #pauseAll()
     */
    public function resumeAll();


    /////////////////////////////////////////////////////////////////////////////
    //
    // Trigger-Firing methods
    //
    /////////////////////////////////////////////////////////////////////////////

    /**
     * Get a handle to the next trigger to be fired, and mark it as 'reserved'
     * by the calling scheduler.
     *
     * @param int $noLaterThan
     * @param int $maxCount
     * @param int $timeWindow
     *
     * @return Trigger[]
     */
    public function acquireNextTriggers($noLaterThan, $maxCount, $timeWindow);

    /**
     * Inform the <code>JobStore</code> that the scheduler no longer plans to
     * fire the given <code>Trigger</code>, that it had previously acquired
     * (reserved).
     */
    public function releaseAcquiredTrigger(Trigger $trigger);

    /**
     * Inform the <code>JobStore</code> that the scheduler is now firing the
     * given <code>Trigger</code> (executing its associated <code>Job</code>),
     * that it had previously acquired (reserved).
     *
     * @param Trigger[] $triggers
     * @param int       $noLaterThan
     *
     * @return Trigger[] may return null if all the triggers or their calendars no longer exist, or
     *                   if the trigger was not successfully put into the 'executing'
     *                   state.  Preference is to return an empty list if none of the triggers
     *                   could be fired.
     */
    public function triggersFired(array $triggers, $noLaterThan);

    /**
     * Inform the <code>JobStore</code> that the scheduler has completed the
     * firing of the given <code>Trigger</code> (and the execution of its
     * associated <code>Job</code> completed, threw an exception, or was vetoed),
     * and that the <code>{@link org.quartz.JobDataMap}</code>
     * in the given <code>JobDetail</code> should be updated if the <code>Job</code>
     * is stateful.
     */
    public function triggeredJobComplete(Trigger $trigger, JobDetail $jobDetail, $triggerInstCode);

    public function retrieveFireTrigger($fireInstanceId);
}
