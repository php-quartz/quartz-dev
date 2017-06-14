<?php
namespace Quartz\Scheduler;

use Quartz\Core\Calendar;
use Quartz\Core\JobDetail;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\JobFactory;
use Quartz\Core\Key;
use Quartz\Core\Scheduler;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;
use Quartz\Core\TriggerBuilder;
use Quartz\Events\Event;
use Quartz\Events\GroupsEvent;
use Quartz\Events\JobDetailEvent;
use Quartz\Events\JobExecutionContextEvent;
use Quartz\Events\KeyEvent;
use Quartz\Events\TriggerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StdScheduler implements Scheduler
{
    /**
     * @var JobStore
     */
    private $store;

    /**
     * @var JobRunShellFactory
     */
    private $jobRunShellFactory;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var int seconds
     */
    private $sleepTime;

    /**
     * @var int
     */
    private $maxCount;

    /**
     * @var int seconds
     */
    private $timeWindow;

    public function __construct(JobStore $store, JobRunShellFactory $jobRunShellFactory, JobFactory $jobFactory, EventDispatcherInterface $eventDispatcher)
    {
        $this->store = $store;
        $this->jobRunShellFactory = $jobRunShellFactory;
        $this->jobFactory = $jobFactory;
        $this->eventDispatcher = $eventDispatcher;

        $this->maxCount = 10;
        $this->sleepTime = 5;
        $this->timeWindow = 30;

        $store->initialize($this);
    }

    public function start()
    {
        $this->notifySchedulerListenersStarting();
        $this->store->schedulerStarted();
        $this->notifySchedulerListenersStarted();

        while (true) {
            $execStart = time();
            if ($triggers = $this->store->acquireNextTriggers($execStart + $this->timeWindow, $this->maxCount, 0)) {
                $firedTriggers = $this->store->triggersFired($triggers, $execStart + $this->sleepTime);

                foreach ($firedTriggers as $firedTrigger) {
                    $jobRunShell = $this->jobRunShellFactory->createJobRunShell($firedTrigger);
                    $jobRunShell->initialize($this);
                    $jobRunShell->execute($firedTrigger);
                }
            }
            $execEnd = time();

            $remainingWaitTime = $this->sleepTime - ($execEnd - $execStart);
            if ($remainingWaitTime > 0) {
                sleep($remainingWaitTime);
            }
        }
    }

    /**
     * @return JobFactory
     */
    public function getJobFactory()
    {
        return $this->jobFactory;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return JobStore
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * @param Trigger   $trigger
     * @param JobDetail $detail
     * @param string    $instructionCode
     */
    public function notifyJobStoreJobComplete(Trigger $trigger, JobDetail $detail = null, $instructionCode)
    {
        $this->store->triggeredJobComplete($trigger, $detail, $instructionCode);
    }

    /**
     * @param JobExecutionContext $context
     *
     * @return bool vetoed
     */
    public function notifyTriggerListenersFired(JobExecutionContext $context)
    {
        $this->notify(Event::TRIGGER_FIRED, $event = new JobExecutionContextEvent($context));

        return $event->isVetoed();
    }

    public function notifyTriggerListenersComplete(JobExecutionContext $context)
    {
        $this->notify(Event::TRIGGER_COMPLETE, new JobExecutionContextEvent($context));
    }

    public function notifyJobListenersWasVetoed(JobExecutionContext $context)
    {
        $this->notify(Event::JOB_EXECUTION_VETOED, new JobExecutionContextEvent($context));
    }

    public function notifyJobListenersToBeExecuted(JobExecutionContext $context)
    {
        $this->notify(Event::JOB_TO_BE_EXECUTED, new JobExecutionContextEvent($context));
    }

    public function notifyJobListenersWasExecuted(JobExecutionContext $context)
    {
        $this->notify(Event::JOB_WAS_EXECUTED, new JobExecutionContextEvent($context));
    }

    public function notifySchedulerListenersFinalized(Trigger $trigger)
    {
        $this->notify(Event::TRIGGER_FINALIZED, new TriggerEvent($trigger));
    }

    public function notifyTriggerListenersMisfired(Trigger $trigger)
    {
        $this->notify(Event::TRIGGER_MISFIRED, new TriggerEvent($trigger));
    }

    public function notifySchedulerListenersStarted()
    {
        $this->notify(Event::SCHEDULER_STARTED, new Event());
    }

    public function notifySchedulerListenersStarting()
    {
        $this->notify(Event::SCHEDULER_STARTING, new Event());
    }

    ///////////////////////////////////////////////////////////////////////////
    ///
    /// Scheduling-related Methods
    ///
    ///////////////////////////////////////////////////////////////////////////

    /**
     * @param string $name
     * @param Event  $event
     */
    private function notify($name, Event $event)
    {
        $this->eventDispatcher->dispatch($name, $event);
    }

    /**
     * Clears (deletes!) all scheduling data - all {@link Job}s, {@link Trigger}s
     * {@link Calendar}s.
     *
     * @throws SchedulerException
     */
    public function clear()
    {
        $this->store->clearAllSchedulingData();

        $this->notify(Event::SCHEDULING_DATA_CLEARED, new Event());
    }

    /**
     * @param Trigger   $trigger
     * @param JobDetail $jobDetail
     *
     * @return \DateTime
     *
     * @throws SchedulerException
     */
    public function scheduleJob(Trigger $trigger, JobDetail $jobDetail = null)
    {
        if ($jobDetail) {
            if ($jobDetail->getKey() == null) {
                throw new SchedulerException('Job\'s key cannot be null');
            }

            if ($trigger->getJobKey() == null) {
                $trigger->setJobKey(clone $jobDetail->getKey());
            } else if (false == $trigger->getJobKey()->equals($jobDetail->getKey())) {
                throw new SchedulerException('Trigger does not reference given job!');
            }
        }

        $trigger->validate();

        $calendar = null;
        if ($trigger->getCalendarName() != null) {
            $calendar = $this->store->retrieveCalendar($trigger->getCalendarName());
        }

        $firstFireTime = $trigger->computeFirstFireTime($calendar);

        if ($firstFireTime == null) {
            throw new SchedulerException(sprintf(
                'Based on configured schedule, the given trigger "%s" will never fire.', $trigger->getKey()
            ));
        }

        if ($jobDetail) {
            $this->store->storeJobAndTrigger($jobDetail, $trigger);

            $this->notify(Event::JOB_ADDED, new JobDetailEvent($jobDetail));
            $this->notify(Event::JOB_SCHEDULED, new TriggerEvent($trigger));
        } else {
            $this->store->storeTrigger($trigger);

            $this->notify(Event::JOB_SCHEDULED, new TriggerEvent($trigger));
        }

        return $firstFireTime;
    }

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
    public function addJob(JobDetail $jobDetail, $replace = false, $storeNonDurableWhileAwaitingScheduling = false)
    {
        if (false == $storeNonDurableWhileAwaitingScheduling && false == $jobDetail->isDurable()) {
            throw new SchedulerException('Jobs added with no trigger must be durable.');
        }

        $this->store->storeJob($jobDetail, $replace);

        $this->notify(Event::JOB_ADDED, new JobDetailEvent($jobDetail));
    }

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
    public function deleteJobs(array $jobKeys)
    {
        $result = $this->store->removeJobs($jobKeys);

        foreach ($jobKeys as $key) {
            $this->notify(Event::JOB_DELETED, new KeyEvent($key));
        }

        return $result;
    }

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
    public function unscheduleJobs(array $triggerKeys)
    {
        $result = $this->store->removeTriggers($triggerKeys);

        foreach ($triggerKeys as $key) {
            $this->notify(Event::JOB_UNSCHEDULED, new KeyEvent($key));
        }

        return $result;
    }

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
    public function unscheduleJob(Key $triggerKey)
    {
        if ($this->store->removeTrigger($triggerKey)) {
            $this->notify(Event::JOB_UNSCHEDULED, new KeyEvent($triggerKey));

            return true;
        }

        return false;
    }

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
    public function deleteJob(Key $jobKey)
    {
        $triggers = $this->store->getTriggersForJob($jobKey);

        $this->unscheduleJobs($triggers);

        if ($this->store->removeJob($jobKey)) {
            $this->notify(Event::JOB_DELETED, new KeyEvent($jobKey));

            return true;
        }

        return false;
    }

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
    public function rescheduleJob(Key $triggerKey, Trigger $newTrigger)
    {
        $oldTrigger = $this->store->retrieveTrigger($triggerKey);

        if (null == $oldTrigger) {
            return null;
        } else {
            $newTrigger->setJobKey(clone $oldTrigger->getJobKey());
        }

        $newTrigger->validate();

        $cal = null;
        if ($newTrigger->getCalendarName()) {
            $cal = $this->store->retrieveCalendar($newTrigger->getCalendarName());
        }

        $firstFireTime = $newTrigger->computeFirstFireTime($cal);

        if (null == $firstFireTime) {
            throw new SchedulerException('Based on configured schedule, the given trigger will never fire.');
        }

        if (false == $this->store->replaceTrigger($triggerKey, $newTrigger)) {
            return null;
        }

        $this->notify(Event::JOB_UNSCHEDULED, new KeyEvent(clone $oldTrigger->getKey()));
        $this->notify(Event::JOB_SCHEDULED, new TriggerEvent($newTrigger));

        return $firstFireTime;
    }

    /**
     * <p>
     * Trigger the identified <code>{@link org.quartz.Job}</code> (execute it
     * now) - with a non-volatile trigger.
     * </p>
     *
     * @param Key   $jobKey
     * @param array $jobDataMap
     */
    public function triggerJob(Key $jobKey, array $jobDataMap = [])
    {
        $trigger = TriggerBuilder::newTrigger()->forJobKey($jobKey)->build();
        $trigger->setJobDataMap($jobDataMap);
        $trigger->computeFirstFireTime();

        $this->store->storeTrigger($trigger);

        $this->notify(Event::JOB_SCHEDULED, new TriggerEvent($trigger));
    }

    /**
     * <p>
     * Pause the <code>{@link Trigger}</code> with the given name.
     * </p>
     *
     * @param Key $triggerKey
     */
    public function pauseTrigger(Key $triggerKey)
    {
        $this->store->pauseTrigger($triggerKey);

        $this->notify(Event::TRIGGER_PAUSED, new KeyEvent($triggerKey));
    }

    /**
     * <p>
     * Pause the <code>{@link org.quartz.JobDetail}</code> with the given
     * name - by pausing all of its current <code>Trigger</code>s.
     * </p>
     *
     * @param Key $jobKey
     */
    public function pauseJob(Key $jobKey)
    {
        $this->store->pauseJob($jobKey);

        $this->notify(Event::JOB_PAUSED, new KeyEvent($jobKey));
    }

    /**
     * @return string[]
     */
    public function getPausedTriggerGroups()
    {
        return $this->store->getPausedTriggerGroups();
    }

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
    public function resumeTrigger(Key $triggerKey)
    {
        $this->store->resumeTrigger($triggerKey);

        $this->notify(Event::TRIGGER_RESUMED, new KeyEvent($triggerKey));
    }

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
    public function resumeJob(Key $jobKey)
    {
        $this->store->resumeJob($jobKey);

        $this->notify(Event::JOB_RESUMED, new KeyEvent($jobKey));
    }

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
    public function pauseAll()
    {
        $this->store->pauseAll();

        $this->notify(Event::TRIGGERS_PAUSED, new GroupsEvent(null));
    }

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
    public function resumeAll()
    {
        $this->store->resumeAll();

        $this->notify(Event::TRIGGERS_RESUMED, new GroupsEvent(null));
    }

    /**
     * <p>
     * Get the names of all known <code>{@link org.quartz.Job}</code> groups.
     * </p>
     *
     * @return string[]
     */
    public function getJobGroupNames()
    {
        return $this->store->getJobGroupNames();
    }

    /**
     * <p>
     * Get the names of all known <code>{@link org.quartz.Trigger}</code>
     * groups.
     * </p>
     *
     * @return string[]
     */
    public function getTriggerGroupNames()
    {
        return $this->store->getTriggerGroupNames();
    }

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
    public function getTriggersOfJob(Key $jobKey)
    {
        return $this->store->getTriggersForJob($jobKey);
    }

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
    public function getJobDetail(Key $jobKey)
    {
        return $this->store->retrieveJob($jobKey);
    }

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
    public function getTrigger(Key $triggerKey)
    {
        return $this->store->retrieveTrigger($triggerKey);
    }

    /**
     * <p>
     * Get the current state of the identified <code>{@link Trigger}</code>.
     * </p>
     *
     * @param Key $triggerKey
     *
     * @return string
     */
    public function getTriggerState(Key $triggerKey)
    {
        return $this->store->getTriggerState($triggerKey);
    }

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
    public function addCalendar($calName, Calendar $calendar, $replace = false, $updateTriggers = false)
    {
        $this->store->storeCalendar($calName, $calendar, $replace, $updateTriggers);
    }

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
    public function deleteCalendar($calName)
    {
        return $this->store->removeCalendar($calName);
    }


    /**
     * <p>
     * Get the <code>{@link Calendar}</code> instance with the given name.
     * </p>
     *
     * @param string $calName
     *
     * @return Calendar
     */
    public function getCalendar($calName)
    {
        return $this->store->retrieveCalendar($calName);
    }

    /**
     * <p>
     * Get the names of all registered <code>{@link Calendar}s</code>.
     * </p>
     *
     * @return string
     */
    public function getCalendarNames()
    {
        return $this->store->getCalendarNames();
    }

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
    public function checkJobExists(Key $jobKey)
    {
        return $this->store->checkJobExists($jobKey);
    }

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
    public function checkTriggerExists(Key $triggerKey)
    {
        return $this->store->checkTriggerExists($triggerKey);
    }

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
    function resetTriggerFromErrorState(Key $triggerKey)
    {
        return $this->store->resetTriggerFromErrorState($triggerKey);
    }

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
