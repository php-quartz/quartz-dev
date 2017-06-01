<?php
namespace Quartz\Core;

// base for -> SimpleTrigger, CronTrigger, DailyTimeIntervalTrigger, CalendarIntervalTrigger
// incorporates Trigger, MutableTrigger, OperableTrigger and TriggerFiredBundle
interface Trigger
{
    const DEFAULT_PRIORITY = 5;

    // STATES
    const STATE_WAITING = "WAITING";
    const STATE_ACQUIRED = "ACQUIRED";
    const STATE_EXECUTING = "EXECUTING";
    const STATE_COMPLETE = "COMPLETE";
    const STATE_ERROR = "ERROR";
    const STATE_PAUSED = "PAUSED";
    const STATE_DELETED = "DELETED"; // is not used

    /**
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>updateAfterMisfire()</code> method will be called
     * on the <code>Trigger</code> to determine the mis-fire instruction,
     * which logic will be trigger-implementation-dependent.
     *
     * <p>
     * In order to see if this instruction fits your needs, you should look at
     * the documentation for the <code>getSmartMisfirePolicy()</code> method
     * on the particular <code>Trigger</code> implementation you are using.
     * </p>
     */
    const MISFIRE_INSTRUCTION_SMART_POLICY = 0;

    /**
     * Instructs the <code>{@link Scheduler}</code> that the
     * <code>Trigger</code> will never be evaluated for a misfire situation,
     * and that the scheduler will simply try to fire it as soon as it can,
     * and then update the Trigger as if it had fired at the proper time.
     *
     * <p>NOTE: if a trigger uses this instruction, and it has missed
     * several of its scheduled firings, then several rapid firings may occur
     * as the trigger attempt to catch back up to where it would have been.
     * For example, a SimpleTrigger that fires every 15 seconds which has
     * misfired for 5 minutes will fire 20 times once it gets the chance to
     * fire.</p>
     */
    const MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY = -1;

    /**
     * @return Key
     */
    public function getKey();

    /**
     * @param Key $key
     */
    public function setKey(Key $key);

    /**
     * @return Key
     */
    public function getJobKey();

    /**
     * @param Key $key
     */
    public function setJobKey(Key $key);

    /**
     * Return the description given to the <code>Trigger</code> instance by
     * its creator (if any).
     *
     * @return null if no description was set.
     */
    public function getDescription();

    /**
     * <p>
     * Set a description for the <code>Trigger</code> instance - may be
     * useful for remembering/displaying the purpose of the trigger, though the
     * description has no meaning to Quartz.
     * </p>
     *
     * @param string $description
     */
    public function setDescription($description = null);

    /**
     * Get the name of the <code>{@link Calendar}</code> associated with this
     * Trigger.
     *
     * @return <code>null</code> if there is no associated Calendar.
     */
    public function getCalendarName();

    /**
     * <p>
     * Associate the <code>{@link Calendar}</code> with the given name with
     * this Trigger.
     * </p>
     *
     * @param string $calendarName use <code>null</code> to dis-associate a Calendar.
     *
     */
    public function setCalendarName($calendarName = null);

    /**
     * Get the <code>JobDataMap</code> that is associated with the
     * <code>Trigger</code>.
     *
     * <p>
     * Changes made to this map during job execution are not re-persisted, and
     * in fact typically result in an <code>IllegalStateException</code>.
     * </p>
     *
     * @return array
     */
    public function getJobDataMap();

    /**
     * <p>
     * Set the <code>JobDataMap</code> to be associated with the
     * <code>Trigger</code>.
     * </p>
     *
     * @param array $jobDataMap
     */
    public function setJobDataMap(array $jobDataMap);

    /**
     * The priority of a <code>Trigger</code> acts as a tiebreaker such that if
     * two <code>Trigger</code>s have the same scheduled fire time, then the
     * one with the higher priority will get first access to a worker
     * thread.
     *
     * <p>
     * If not explicitly set, the default value is <code>5</code>.
     * </p>
     *
     * @return int
     */
    public function getPriority();

    /**
     * The priority of a <code>Trigger</code> acts as a tie breaker such that if
     * two <code>Trigger</code>s have the same scheduled fire time, then Quartz
     * will do its best to give the one with the higher priority first access
     * to a worker thread.
     *
     * <p>
     * If not explicitly set, the default value is <code>5</code>.
     * </p>
     *
     * @see #DEFAULT_PRIORITY
     *
     * @param int $priority
     */
    public function setPriority($priority);

    /**
     * Get the time at which the <code>Trigger</code> should occur.
     *
     * @return \DateTime
     */
    public function getStartTime();

    /**
     * <p>
     * The time at which the trigger's scheduling should start.  May or may not
     * be the first actual fire time of the trigger, depending upon the type of
     * trigger and the settings of the other properties of the trigger.  However
     * the first actual first time will not be before this date.
     * </p>
     * <p>
     * Setting a value in the past may cause a new trigger to compute a first
     * fire time that is in the past, which may cause an immediate misfire
     * of the trigger.
     * </p>
     *
     * @param \DateTime $startTime
     */
    public function setStartTime(\DateTime $startTime);

    /**
     * Get the time at which the <code>Trigger</code> should quit repeating -
     * regardless of any remaining repeats (based on the trigger's particular
     * repeat settings).
     *
     * @return \DateTime
     */
    public function getEndTime();

    /**
     * <p>
     * Set the time at which the <code>Trigger</code> should quit repeating -
     * regardless of any remaining repeats (based on the trigger's particular
     * repeat settings).
     * </p>
     *
     * @param \DateTime $endTime
     */
    public function setEndTime(\DateTime $endTime = null);

    /**
     * Used by the <code>{@link Scheduler}</code> to determine whether or not
     * it is possible for this <code>Trigger</code> to fire again.
     *
     * <p>
     * If the returned value is <code>false</code> then the <code>Scheduler</code>
     * may remove the <code>Trigger</code> from the <code>{@link org.quartz.spi.JobStore}</code>.
     * </p>
     *
     * @return bool
     */
    public function mayFireAgain();

    /**
     * Returns the next time at which the <code>Trigger</code> is scheduled to fire. If
     * the trigger will not fire again, <code>null</code> will be returned.  Note that
     * the time returned can possibly be in the past, if the time that was computed
     * for the trigger to next fire has already arrived, but the scheduler has not yet
     * been able to fire the trigger (which would likely be due to lack of resources
     * e.g. threads).
     *
     * <p>The value returned is not guaranteed to be valid until after the <code>Trigger</code>
     * has been added to the scheduler.
     * </p>
     *
     * @return \DateTime
     *
     * @see TriggerUtils#computeFireTimesBetween(org.quartz.spi.OperableTrigger, Calendar, java.util.Date, java.util.Date)
     */
    public function getNextFireTime();
    public function setNextFireTime(\DateTime $nextFireTime = null);

    /**
     * Returns the previous time at which the <code>Trigger</code> fired.
     * If the trigger has not yet fired, <code>null</code> will be returned.
     */
    public function getPreviousFireTime();
    public function setPreviousFireTime(\DateTime $previousFireTime);

    /**
     * @return int
     */
    public function getState();

    /**
     * @param int $state
     */
    public function setState($state);

    /**
     * <p>
     * Validates whether the properties of the <code>JobDetail</code> are
     * valid for submission into a <code>Scheduler</code>.
     *
     * @throws SchedulerException if a required property (such as Name, Group, Class) is not set.
     *
     * @return void
     */
    public function validate();

    /**
     * Returns the next time at which the <code>Trigger</code> will fire,
     * after the given time. If the trigger will not fire after the given time,
     * <code>null</code> will be returned.
     *
     * @param \DateTime $afterTime
     *
     * @return \DateTime|null
     */
    public function getFireTimeAfter(\DateTime $afterTime = null);

    /**
     * Returns the last time at which the <code>Trigger</code> will fire, if
     * the Trigger will repeat indefinitely, null will be returned.
     *
     * <p>
     * Note that the return time *may* be in the past.
     * </p>
     */
    public function getFinalFireTime();

    /**
     * Get the instruction the <code>Scheduler</code> should be given for
     * handling misfire situations for this <code>Trigger</code>- the
     * concrete <code>Trigger</code> type that you are using will have
     * defined a set of additional <code>MISFIRE_INSTRUCTION_XXX</code>
     * constants that may be set as this property's value.
     *
     * <p>
     * If not explicitly set, the default value is <code>MISFIRE_INSTRUCTION_SMART_POLICY</code>.
     * </p>
     *
     * @see #MISFIRE_INSTRUCTION_SMART_POLICY
     * @see SimpleTrigger
     * @see CronTrigger
     */
    public function getMisfireInstruction();

    /**
     * <p>
     * Set the instruction the <code>Scheduler</code> should be given for
     * handling misfire situations for this <code>Trigger</code>- the
     * concrete <code>Trigger</code> type that you are using will have
     * defined a set of additional <code>MISFIRE_INSTRUCTION_XXX</code>
     * constants that may be passed to this method.
     * </p>
     *
     * <p>
     * If not explicitly set, the default value is <code>MISFIRE_INSTRUCTION_SMART_POLICY</code>.
     * </p>
     *
     * @see #MISFIRE_INSTRUCTION_SMART_POLICY
     * @see #updateAfterMisfire(Calendar)
     * @see SimpleTrigger
     * @see CronTrigger
     */
    public function setMisfireInstruction($misfireInstruction);

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     *
     * <p>
     * Called when the <code>{@link Scheduler}</code> has decided to 'fire'
     * the trigger (execute the associated <code>Job</code>), in order to
     * give the <code>Trigger</code> a chance to update itself for its next
     * triggering (if any).
     * </p>
     *
     * @param Calendar $calendar
     *
     * @see #executionComplete(JobExecutionContext, JobExecutionException)
     */
    public function triggered(Calendar $calendar = null);

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     *
     * <p>
     * Called by the scheduler at the time a <code>Trigger</code> is first
     * added to the scheduler, in order to have the <code>Trigger</code>
     * compute its first fire time, based on any associated calendar.
     * </p>
     *
     * <p>
     * After this method has been called, <code>getNextFireTime()</code>
     * should return a valid answer.
     * </p>
     *
     * return the first time at which the <code>Trigger</code> will be fired
     *         by the scheduler, which is also the same value <code>getNextFireTime()</code>
     *         will return (until after the first firing of the <code>Trigger</code>).
     *         </p>
     *
     * @return \DateTime
     */
    public function computeFirstFireTime(Calendar $calendar = null);

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     *
     * <p>
     * To be implemented by the concrete class.
     * </p>
     *
     * <p>
     * The implementation should update the <code>Trigger</code>'s state
     * based on the given new version of the associated <code>Calendar</code>
     * (the state should be updated so that it's next fire time is appropriate
     * given the Calendar's new settings).
     * </p>
     *
     * @param Calendar $cal
     * @param int      $misfireThreshold
     */
    public function updateWithNewCalendar(Calendar $cal = null, $misfireThreshold);

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     *
     * <p>
     * To be implemented by the concrete classes that extend this class.
     * </p>
     *
     * <p>
     * The implementation should update the <code>Trigger</code>'s state
     * based on the MISFIRE_INSTRUCTION_XXX that was selected when the <code>Trigger</code>
     * was created.
     * </p>
     *
     * @param Calendar $cal
     */
    public function updateAfterMisfire(Calendar $cal = null);

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     *
     * <p>
     * Called after the <code>{@link Scheduler}</code> has executed the
     * <code>{@link org.quartz.JobDetail}</code> associated with the <code>Trigger</code>
     * in order to get the final instruction code from the trigger.
     * </p>
     *
     * @param JobExecutionContext $context
     *          is the <code>JobExecutionContext</code> that was used by the
     *          <code>Job</code>'s<code>execute(xx)</code> method.
     * @return string one of the <code>CompletedExecutionInstruction</code> constants.
     *
     * @see CompletedExecutionInstruction
     * @see #triggered(Calendar)
     */
    public function executionComplete(JobExecutionContext $context);

    # TriggerFiredBundle interface

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     *
     * <p>
     * Usable by <code>{@link org.quartz.spi.JobStore}</code>
     * implementations, in order to facilitate 'recognizing' instances of fired
     * <code>Trigger</code> s as their jobs complete execution.
     * </p>
     *
     * @param string $id
     */
    public function setFireInstanceId($id);

    /**
     * <p>
     * This method should not be used by the Quartz client.
     * </p>
     */
    public function getFireInstanceId();

    /**
     * @param \DateTime $time
     */
    public function setFireTime(\DateTime $time);

    /**
     * @return \DateTime|null
     */
    public function getFireTime();

    /**
     * @param \DateTime $time
     */
    public function setScheduledFireTime(\DateTime $time);

    /**
     * @return \DateTime|null
     */
    public function getScheduledFireTime();
}
