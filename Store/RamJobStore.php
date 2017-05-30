<?php
namespace Quartz\Store;

use Quartz\Core\Calendar;
use Quartz\Core\JobDetail;
use Quartz\Core\JobStore;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;
use Ramsey\Uuid\Uuid;

class RamJobStore implements JobStore
{
    /**
     * @var Calendar[]
     */
    private $calendars;

    /**
     * [
     *   'key' => JobDetail
     * ]
     *
     * @var JobDetail[]
     */
    private $jobsByKey;

    /**
     * [
     *   'group' => [
     *     'key' => JobDetail,
     *     .....
     *   ]
     * ]
     *
     * @var JobDetail[]
     */
    private $jobsByGroup;

    /**
     * [
     *   'trigger-key' => Trigger,
     * ]
     *
     * @var Trigger[]
     */
    private $triggersByKey;


    /**
     * [
     *   'job-key' => [
     *     'trigger-key' => Trigger,
     *     ........
     *   ]
     * ]
     *
     * @var Trigger[]
     */
    private $triggersByJob;

    /**
     * [
     *   'group' => [
     *     'trigger-key' => Trigger,
     *     .........
     *   ]
     * ]
     *
     * @var Trigger[]
     */
    private $triggersByGroup;

    /**
     * [
     *   'group' => true,
     * ]
     *
     * @var array
     */
    private $pausedTriggerGroups;

    /**
     * [
     *   'group' => true,
     * ]
     *
     * @var array
     */
    private $pausedJobGroups;

    /**
     * [
     *   'key' => true,
     * ]
     *
     * @var array
     */
    private $blockedJobs;

    /**
     * @var Trigger[]
     */
    private $waitingTriggers;

    /**
     * @var Trigger[]
     */
    private $acquiredTriggers;

    /**
     * @var Trigger[]
     */
    private $errorTriggers;

    /**
     * @var Trigger[]
     */
    private $pausedTriggers;

    /**
     * @var Trigger[]
     */
    private $blockedTriggers;

    /**
     * [
     *   'id' => Trigger,
     *   .............
     * ]
     *
     * @var Trigger[]
     */
    private $firedTriggers;

    /**
     * {@inheritdoc}
     */
    public function storeJobAndTrigger(JobDetail $newJob, Trigger $newTrigger)
    {
        $this->storeJob($newJob, false);
        $this->storeTrigger($newTrigger, false);
    }

    /**
     * {@inheritdoc}
     */
    public function storeJob(JobDetail $newJob, $replaceExisting)
    {
        if (isset($this->jobsByKey[(string) $newJob->getKey()])) {
            if (! $replaceExisting) {
                throw ObjectAlreadyExistsException::create($newJob);
            }
        }

        $this->jobsByGroup[$newJob->getKey()->getGroup()][(string) $newJob->getKey()] = $newJob;
        $this->jobsByKey[(string) $newJob->getKey()] = $newJob;
    }

    public function removeJob(Key $jobKey)
    {
        // TODO: Implement removeJob() method.
    }

    public function removeJobs(array $jobKeys)
    {
        // TODO: Implement removeJobs() method.
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveJob(Key $jobKey)
    {
        if (isset($this->jobsByKey[$strKey = (string) $jobKey])) {
            return $this->jobsByKey[$strKey];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeTrigger(Trigger $newTrigger, $replaceExisting)
    {
        if (isset($this->triggersByKey[(string) $newTrigger->getKey()])) {
            if (! $replaceExisting) {
                throw ObjectAlreadyExistsException::create($newTrigger);
            }
        }

        if ($this->retrieveJob($newTrigger->getJobKey()) == null) {
            throw new SchedulerException(sprintf(
                'The job ("%s") referenced by the trigger does not exist.', $newTrigger->getJobKey()
            ));
        }

        $this->triggersByJob[(string) $newTrigger->getJobKey()][(string) $newTrigger->getKey()] = $newTrigger;
        $this->triggersByGroup[(string) $newTrigger->getKey()->getGroup()][(string) $newTrigger->getKey()] = $newTrigger;
        $this->triggersByKey[(string) $newTrigger->getKey()] = $newTrigger;

        $state = Trigger::STATE_WAITING;
        if (isset($this->pausedTriggerGroups[$newTrigger->getKey()->getGroup()])
        || isset($this->pausedJobGroups[$newTrigger->getJobKey()->getGroup()])) {
            $state = Trigger::STATE_PAUSED;

            if (isset($this->blockedJobs[(string) $newTrigger->getJobKey()])) {
                $state = Trigger::STATE_PAUSED_BLOCKED;
            }
        } elseif (isset($this->blockedJobs[(string) $newTrigger->getJobKey()])) {
            $state = Trigger::STATE_BLOCKED;
        }

        $newTrigger->setValue('state', $state);

        switch ($state) {
            case Trigger::STATE_WAITING:
                $this->waitingTriggers[] = $newTrigger;
                break;
            case Trigger::STATE_PAUSED:
                $this->pausedTriggers[] = $newTrigger;
                break;
            case Trigger::STATE_BLOCKED:
                $this->blockedTriggers[] = $newTrigger;
                break;

        }
    }

    public function removeTrigger(Key $triggerKey)
    {
        // TODO: Implement removeTrigger() method.
    }

    public function removeTriggers(array $triggerKeys)
    {
        // TODO: Implement removeTriggers() method.
    }

    public function replaceTrigger(Key $triggerKey, Trigger $newTrigger)
    {
        // TODO: Implement replaceTrigger() method.
    }

    public function retrieveTrigger(Key $triggerKey)
    {
        // TODO: Implement retrieveTrigger() method.
    }

    public function checkJobExists(Key $jobKey)
    {
        // TODO: Implement checkJobExists() method.
    }

    public function checkTriggerExists(Key $triggerKey)
    {
        // TODO: Implement checkTriggerExists() method.
    }

    public function clearAllSchedulingData()
    {
        // TODO: Implement clearAllSchedulingData() method.
    }

    public function storeCalendar($name, Calendar $calendar, $replaceExisting, $updateTriggers)
    {
        // TODO: Implement storeCalendar() method.
    }

    public function removeCalendar($calName)
    {
        // TODO: Implement removeCalendar() method.
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveCalendar($calName)
    {
        if (isset($this->calendars[$calName])) {
            return $this->calendars[$calName];
        }
    }

    public function pauseTrigger(Key $triggerKey)
    {
        // TODO: Implement pauseTrigger() method.
    }

    public function pauseTriggers(GroupMatcher $matcher)
    {
        // TODO: Implement pauseTriggers() method.
    }

    public function pauseJob(Key $jobKey)
    {
        // TODO: Implement pauseJob() method.
    }

    public function pauseJobs(GroupMatcher $groupMatcher)
    {
        // TODO: Implement pauseJobs() method.
    }

    public function resumeTrigger(Key $triggerKey)
    {
        // TODO: Implement resumeTrigger() method.
    }

    public function resumeTriggers(GroupMatcher $matcher)
    {
        // TODO: Implement resumeTriggers() method.
    }

    public function getPausedTriggerGroups()
    {
        // TODO: Implement getPausedTriggerGroups() method.
    }

    public function resumeJob(Key $jobKey)
    {
        // TODO: Implement resumeJob() method.
    }

    public function resumeJobs(GroupMatcher $matcher)
    {
        // TODO: Implement resumeJobs() method.
    }

    public function pauseAll()
    {
        // TODO: Implement pauseAll() method.
    }

    public function resumeAll()
    {
        // TODO: Implement resumeAll() method.
    }

    /**
     * {@inheritdoc}
     */
    public function acquireNextTriggers($since, $until, $maxCount)
    {
        if (false == $this->waitingTriggers) {
            return [];
        }

        $acquired = [];
        foreach ($this->waitingTriggers as $key => $trigger) {
            if (null == $nextFireTime = $trigger->getNextFireTime()) {
                unset($this->waitingTriggers[$key]);
                $this->errorTriggers[$key] = $trigger;

                continue;
            }

            $nextFireTime = (int) $nextFireTime->format('U');

            if ($since <= $nextFireTime && $nextFireTime <= $until) {
                $trigger->setValue('state', Trigger::STATE_ACQUIRED);

                unset($this->waitingTriggers[$key]);
                $this->acquiredTriggers[$key] = $acquired[$key] = $trigger;
            }

            if (count($acquired) >= $maxCount) {
                break;
            }
        }

        return $acquired;
    }

    private $firedTriggerRecordId = 0;

    /**
     * @return string
     */
    protected function getFiredTriggerRecordId()
    {
        return (string) $this->firedTriggerRecordId++;
    }

    public function releaseAcquiredTrigger(Trigger $trigger)
    {
        // TODO: Implement releaseAcquiredTrigger() method.
    }

    /**
     * {@inheritdoc}
     */
    public function triggersFired(array $triggers)
    {
        /** @var Trigger $trigger */
        $result = [];
        foreach ($triggers as $trigger) {
            if (false == isset($this->triggersByKey[(string) $trigger->getKey()])) {
                continue;
            }

            if ($trigger->getValue('state') !== Trigger::STATE_ACQUIRED) {
                continue;
            }

            $calendar = null;
            if ($trigger->getCalendarName()) {
                if (null == $calendar = $this->retrieveCalendar($trigger->getCalendarName())) {
                    continue;
                }
            }

            $firedTrigger = clone $trigger;

            $trigger->triggered($calendar);

            $firedTrigger->setFireInstanceId(Uuid::uuid4()->toString());
            $firedTrigger->setFireTime(new \DateTime());
            $firedTrigger->setScheduledFireTime($firedTrigger->getNextFireTime());
            $firedTrigger->setNextFireTime($trigger->getNextFireTime());

            $this->firedTriggers[$firedTrigger->getFireInstanceId()] = $firedTrigger;
            unset($this->acquiredTriggers[(string) $trigger->getKey()]);

            $trigger->setValue('state', Trigger::STATE_EXECUTING);
            $firedTrigger->setValue('state', Trigger::STATE_EXECUTING);

            if ($trigger->getNextFireTime() !== null) {
                $trigger->setValue('state', Trigger::STATE_WAITING);
                $this->waitingTriggers[(string) $trigger->getKey()] = $trigger;
            }

            $result[] = $firedTrigger;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function triggeredJobComplete(Trigger $trigger, JobDetail $jobDetail, $triggerInstCode)
    {
        if (false == isset($this->firedTriggers[$trigger->getFireInstanceId()])) {
            return;
        }

        $firedTrigger = $this->firedTriggers[$trigger->getFireInstanceId()];
        $firedTrigger->setValue('state', $triggerInstCode);
    }
}
