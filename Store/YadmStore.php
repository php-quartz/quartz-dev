<?php
namespace Quartz\Store;

use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
use function Makasim\Values\set_values;
use function Makasim\Yadm\get_object_id;
use function Makasim\Yadm\set_object_id;
use Quartz\Core\Calendar;
use Quartz\Core\CompletedExecutionInstruction;
use Quartz\Core\JobDetail;
use Quartz\Core\JobPersistenceException;
use Quartz\Core\JobStore;
use Quartz\Core\Key;
use Quartz\Core\Trigger;
use Ramsey\Uuid\Uuid;

class YadmStore implements JobStore
{
    const LOCK_TRIGGER_ACCESS = '5923d7187aaf04aff436ebb3';
    const ALL_GROUPS_PAUSED = '_$_ALL_GROUPS_PAUSED_$_';

    /**
     * @var YadmStoreResource
     */
    private $res;

    /**
     * @var int
     */
    private $misfireThreshold = 60; // one minute

    /**
     * @param YadmStoreResource $res
     */
    public function __construct(YadmStoreResource $res)
    {
        $this->res = $res;
    }

    /**
     * {@inheritdoc}
     */
    public function storeJobAndTrigger(JobDetail $newJob, Trigger $newTrigger)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($newJob, $newTrigger) {
            $this->doStoreJob($newJob);
            $this->doStoreTrigger($newTrigger);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function storeJob(JobDetail $newJob, $replaceExisting = false)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($newJob, $replaceExisting) {
            $this->doStoreJob($newJob, $replaceExisting);
        });
    }

    /**
     * @param JobDetail $newJob
     * @param bool      $replaceExisting
     *
     * @throws JobPersistenceException
     * @throws ObjectAlreadyExistsException
     */
    protected function doStoreJob(JobDetail $newJob, $replaceExisting = false)
    {
        $jobKey = $newJob->getKey();
        $existingJob = $this->retrieveJob($jobKey);

        if ($existingJob && false == $replaceExisting) {
            throw ObjectAlreadyExistsException::create($newJob);
        }

        if ($existingJob) {
            $id = get_object_id($existingJob);
            $values = get_values($newJob);

            set_values($existingJob, $values);
            set_object_id($existingJob, $id);

            $result = $this->res->getJobStorage()->update($existingJob, [
                'name' => $jobKey->getName(),
                'group' => $jobKey->getGroup()
            ], ['upsert' => false]);

            if ($result && (false == $result->isAcknowledged() || false == $result->getModifiedCount())) {
                throw new JobPersistenceException(sprintf('Couldn\'t store job.  Update failed: "%s"', $jobKey));
            }
        } else {
            $result = $this->res->getJobStorage()->update($newJob, [
                'name' => $jobKey->getName(),
                'group' => $jobKey->getGroup()
            ], ['upsert' => true]);

            if ($result && (false == $result->isAcknowledged() || false == $result->getUpsertedCount())) {
                throw new JobPersistenceException(sprintf('Couldn\'t store job.  Insert failed: "%s"', $jobKey));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeJob(Key $jobKey)
    {
        return $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($jobKey) {
            return $this->doRemoveJob($jobKey);
        });
    }

    /**
     * @param Key $jobKey
     *
     * @return bool
     *
     * @throws JobPersistenceException
     */
    protected function doRemoveJob(Key $jobKey)
    {
        if (false == $job = $this->retrieveJob($jobKey)) {
            return false;
        }

        $result = $this->res->getTriggerStorage()->getCollection()->deleteMany([
            'jobName' => $jobKey->getName(),
            'jobGroup' => $jobKey->getGroup()
        ]);

        if (false == $result->isAcknowledged()) {
            throw new JobPersistenceException(sprintf('Couldn\'t remove job: "%s"', $jobKey));
        }

        $result = $this->res->getJobStorage()->delete($job);

        if (false == $result->isAcknowledged() || false == $result->getDeletedCount()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeJobs(array $jobKeys)
    {
        foreach ($jobKeys as $key) {
            if (false == $key instanceof Key) {
                throw new \InvalidArgumentException(sprintf('Expected Key instance but got: "%s"',
                    is_object($key) ? get_class($key) : gettype($key)));
            }
        }

        return $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($jobKeys) {
            $allFound = true;

            foreach ($jobKeys as $key) {
                $allFound = $this->doRemoveJob($key) && $allFound;
            }

            return $allFound;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveJob(Key $jobKey)
    {
        // no locks necessary for read...
        return $this->res->getJobStorage()->findOne([
            'name' => $jobKey->getName(),
            'group' => $jobKey->getGroup(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function storeTrigger(Trigger $newTrigger, $replaceExisting = false)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($newTrigger, $replaceExisting) {
            $this->doStoreTrigger($newTrigger, $replaceExisting, Trigger::STATE_WAITING);
        });
    }

    /**
     * @param Trigger $newTrigger
     * @param bool $replaceExisting
     * @param string $state
     *
     * @throws JobPersistenceException
     * @throws ObjectAlreadyExistsException
     */
    protected function doStoreTrigger(Trigger $newTrigger, $replaceExisting = false, $state = Trigger::STATE_WAITING)
    {
        $triggerKey = $newTrigger->getKey();
        $existingTrigger = $this->retrieveTrigger($triggerKey);

        if ($existingTrigger && false == $replaceExisting) {
            throw ObjectAlreadyExistsException::create($newTrigger);
        }

        $shouldBePaused = $this->isTriggerGroupPaused($triggerKey->getGroup());

        if (false == $shouldBePaused) {
            $shouldBePaused  = $this->isTriggerGroupPaused(self::ALL_GROUPS_PAUSED);

            if ($shouldBePaused) {
                $this->insertPausedTriggerGroup($triggerKey->getGroup());
            }
        }

        if ($shouldBePaused && ($state == Trigger::STATE_WAITING || $state == Trigger::STATE_ACQUIRED)) {
            $state = Trigger::STATE_PAUSED;
        }

        $newTrigger->setState($state);

//        if (null == $job) {
//            $job = $this->retrieveJob($newTrigger->getJobKey());
//        }
//
//        if (null == $job) {
//            throw new JobPersistenceException(sprintf('The job referenced by the trigger does not exist. trigger: "%s", job: "%s"',
//                $triggerKey, $newTrigger->getJobKey()));
//        }

        if ($existingTrigger) {
            $id = get_object_id($existingTrigger);
            $values = get_values($newTrigger);

            set_values($existingTrigger, $values);
            set_object_id($existingTrigger, $id);

            $result = $this->res->getTriggerStorage()->update($existingTrigger, [
                'name' => $triggerKey->getName(),
                'group' => $triggerKey->getGroup()
            ], ['upsert' => false]);

            if ($result && (false == $result->isAcknowledged() || false == $result->getModifiedCount())) {
                throw new JobPersistenceException(sprintf('Couldn\'t store trigger.  Update failed: "%s"', $triggerKey));
            }
        } else {
            $result = $this->res->getTriggerStorage()->update($newTrigger, [
                'name' => $triggerKey->getName(),
                'group' => $triggerKey->getGroup()
            ], ['upsert' => true]);

            if ($result && (false == $result->isAcknowledged() || false == $result->getUpsertedCount())) {
                throw new JobPersistenceException(sprintf('Couldn\'t store trigger.  Insert failed: "%s"', $triggerKey));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeTrigger(Key $triggerKey)
    {
        return $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($triggerKey) {
            return $this->doRemoveTrigger($triggerKey);
        });
    }

    /**
     * @param Key $triggerKey
     *
     * @return bool
     *
     * @throws JobPersistenceException
     */
    protected function doRemoveTrigger(Key $triggerKey)
    {
        if (false == $trigger = $this->retrieveTrigger($triggerKey)) {
            return false;
        }

        $result = $this->res->getTriggerStorage()->delete($trigger);

        $removedTrigger = true;
        if (false == $result->isAcknowledged() || false == $result->getDeletedCount()) {
            $removedTrigger = false;
        }

        $jobKey = $trigger->getJobKey();
        $job = $this->retrieveJob($jobKey);

        if ($job && false == $job->isDurable()) {
            $numTriggers = $this->res->getTriggerStorage()->count([
                'jobName' => $jobKey->getName(),
                'jobGroup' => $jobKey->getGroup(),
            ]);

            if ($numTriggers == 0) {
                // Don't call removeJob() because we don't want to check for triggers again.
                $this->res->getJobStorage()->delete($job);
            }
        }

        return $removedTrigger;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTriggers(array $triggerKeys)
    {
        foreach ($triggerKeys as $key) {
            if (false == $key instanceof Key) {
                throw new \InvalidArgumentException(sprintf('Expected Key instance but got: "%s"',
                    is_object($key) ? get_class($key) : gettype($key)));
            }
        }

        return $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($triggerKeys) {
            $allFound = true;

            foreach ($triggerKeys as $key) {
                $allFound = $this->doRemoveTrigger($key) && $allFound;
            }

            return $allFound;
        });
    }

    public function replaceTrigger(Key $triggerKey, Trigger $newTrigger)
    {
        // TODO: Implement replaceTrigger() method.
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveTrigger(Key $triggerKey)
    {
        // no locks necessary for read...
        return $this->res->getTriggerStorage()->findOne([
            'name' => $triggerKey->getName(),
            'group' => $triggerKey->getGroup(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function checkJobExists(Key $jobKey)
    {
        // no locks necessary for read...
        return (bool) $this->res->getJobStorage()->count([
            'name' => $jobKey->getName(),
            'group' => $jobKey->getGroup(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function checkTriggerExists(Key $triggerKey)
    {
        // no locks necessary for read...
        return (bool) $this->res->getTriggerStorage()->count([
            'name' => $triggerKey->getName(),
            'group' => $triggerKey->getGroup(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllSchedulingData()
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () {
            $this->doClearAllSchedulingData();
        });
    }

    public function doClearAllSchedulingData()
    {
        $this->res->getTriggerStorage()->getCollection()->drop();
        $this->res->getJobStorage()->getCollection()->drop();
        $this->res->getCalendarStorage()->getCollection()->drop();
        $this->res->getPausedTriggerCol()->drop();
        $this->res->getFiredTriggerStorage()->getCollection()->drop();
    }

    /**
     * {@inheritdoc}
     */
    public function storeCalendar($name, Calendar $calendar, $replaceExisting = false, $updateTriggers = false)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($name, $calendar, $replaceExisting, $updateTriggers) {
            $this->doStoreCalendar($name, $calendar, $replaceExisting, $updateTriggers);
        });
    }

    /**
     * @param string    $name
     * @param Calendar  $calendar
     * @param bool      $replaceExisting
     * @param bool      $updateTriggers
     *
     * @throws JobPersistenceException
     * @throws ObjectAlreadyExistsException
     */
    protected function doStoreCalendar($name, Calendar $calendar, $replaceExisting, $updateTriggers)
    {
        $existingCal = $this->retrieveCalendar($name);

        if ($existingCal && false == $replaceExisting) {
            throw new ObjectAlreadyExistsException(sprintf('Calendar with name already exists: "%s"', $name));
        }

        if ($existingCal) {
            $id = get_object_id($existingCal);
            $values = get_values($calendar);
            $values['name'] = $name;

            set_values($existingCal, $values);
            set_object_id($existingCal, $id);

            $result = $this->res->getCalendarStorage()->update($existingCal, ['name' => $name], ['upsert' => false]);

            if ($result && (false == $result->isAcknowledged() || false == $result->getModifiedCount())) {
                throw new JobPersistenceException(sprintf('Couldn\'t store calendar.  Update failed: "%s"', $name));
            }
        } else {
            set_value($calendar, 'name', $name);
            $result = $this->res->getCalendarStorage()->update($calendar, ['name' => $name], ['upsert' => true]);

            if ($result && (false == $result->isAcknowledged() || false == $result->getUpsertedCount())) {
                throw new JobPersistenceException(sprintf('Couldn\'t store calendar.  Insert failed: "%s"', $name));
            }
        }

        if ($existingCal && $updateTriggers) {
            /** @var Trigger $trigger */
            foreach ($this->res->getTriggerStorage()->find(['calendarName' => $name]) as $trigger) {
                $trigger->updateWithNewCalendar($existingCal, $this->misfireThreshold);
                $this->doStoreTrigger($trigger, true, Trigger::STATE_WAITING);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeCalendar($calName)
    {
        return $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($calName) {
            return $this->doRemoveCalendar($calName);
        });
    }

    /**
     * @param string $calName
     *
     * @return bool
     *
     * @throws JobPersistenceException
     */
    protected function doRemoveCalendar($calName)
    {
        if ($this->res->getTriggerStorage()->count(['calendarName' => $calName])) {
            throw new JobPersistenceException(sprintf('Calendar cannot be removed if it referenced by a trigger!. calendar: "%s"', $calName));
        }

        $result = $this->res->getCalendarStorage()->getCollection()->deleteOne(['name' => $calName]);

        return $result->isAcknowledged() && $result->getDeletedCount();
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveCalendar($calName)
    {
        // no locks necessary for read...
        return $this->res->getCalendarStorage()->findOne(['name' => $calName]);
    }

    /**
     * {@inheritdoc}
     */
    public function pauseTrigger(Key $triggerKey)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($triggerKey) {
            $this->doPauseTrigger($triggerKey);
        });
    }

    /**
     * @param Key $triggerKey
     *
     * @throws JobPersistenceException
     */
    protected function doPauseTrigger(Key $triggerKey)
    {
        if (false == $trigger = $this->retrieveTrigger($triggerKey)) {
            return;
        }

        if (false == in_array($trigger->getState(), [Trigger::STATE_WAITING, Trigger::STATE_ACQUIRED], true)) {
            return;
        }

        $trigger->setState(Trigger::STATE_PAUSED);
        $result = $this->res->getTriggerStorage()->update($trigger);

        if (false == $result->isAcknowledged() || false == $result->getModifiedCount()) {
            throw new JobPersistenceException(sprintf('Couldn\'t pause trigger: "%s"', $triggerKey));
        }
    }

    public function pauseTriggers(GroupMatcher $matcher)
    {
        // TODO: Implement pauseTriggers() method.
    }

    /**
     * {@inheritdoc}
     */
    public function pauseJob(Key $jobKey)
    {
        $this->executeInLock(self::ALL_GROUPS_PAUSED, function () use ($jobKey) {
            $this->doPauseJob($jobKey);
        });
    }

    /**
     * @param Key $jobKey
     */
    protected function doPauseJob(Key $jobKey)
    {
        foreach ($this->getTriggersForJob($jobKey) as $trigger) {
            $this->doPauseTrigger($trigger->getKey());
        }
    }

    public function pauseJobs(GroupMatcher $groupMatcher)
    {
        // TODO: Implement pauseJobs() method.
    }

    /**
     * {@inheritdoc}
     */
    public function resumeTrigger(Key $triggerKey)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($triggerKey) {
            $this->doResumeTrigger($triggerKey);
        });
    }

    /**
     * @param Key $triggerKey
     *
     * @throws JobPersistenceException
     */
    protected function doResumeTrigger(Key $triggerKey)
    {
        if (false == $trigger = $this->retrieveTrigger($triggerKey)) {
            return;
        }

        if (false == $trigger->getNextFireTime()) {
            return;
        }

        $misfired = false;
        if (((int) $trigger->getNextFireTime()->format('U')) < time()) {
            $misfired = $this->updateMisfiredTrigger($triggerKey, Trigger::STATE_WAITING);
        }

        if (false == $misfired) {
            $trigger->setState(Trigger::STATE_WAITING);
            $result = $this->res->getTriggerStorage()->update($trigger);

            if (false == $result->isAcknowledged() || false == $result->getModifiedCount()) {
                throw new JobPersistenceException(sprintf('Couldn\'t resume trigger: "%s"', $triggerKey));
            }
        }
    }

    /**
     * @param Key    $triggerKey
     * @param string $newStateIfNotComplete
     *
     * @return bool
     */
    protected function updateMisfiredTrigger(Key $triggerKey, $newStateIfNotComplete)
    {
        $trigger = $this->retrieveTrigger($triggerKey);

        $misfireTime = time();
        if ($this->misfireThreshold > 0) {
            $misfireTime -= $this->misfireThreshold;
        }

        if (((int) $trigger->getNextFireTime()->format('U')) > $misfireTime) {
            return false;
        }

        $this->doUpdateOfMisfiredTrigger($trigger, $newStateIfNotComplete);

        return true;
    }

    /**
     * @param Trigger $trig
     * @param string  $newStateIfNotComplete
     */
    protected function doUpdateOfMisfiredTrigger(Trigger $trig, $newStateIfNotComplete)
    {
        $calendar = null;
        if ($trig->getCalendarName()) {
            $calendar = $this->retrieveCalendar($trig->getCalendarName());
        }

        // schedSignaler.notifyTriggerListenersMisfired(trig);

        $trig->updateAfterMisfire($calendar);

        if (null == $trig->getNextFireTime()) {
            $this->doStoreTrigger($trig, true, Trigger::STATE_COMPLETE);
        } else {
            $this->doStoreTrigger($trig, true, $newStateIfNotComplete);
        }
    }

    public function resumeTriggers(GroupMatcher $matcher)
    {
        // TODO: Implement resumeTriggers() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPausedTriggerGroups()
    {
        return $this->res->getPausedTriggerCol()->distinct('groupName');
    }

    /**
     * {@inheritdoc}
     */
    public function resumeJob(Key $jobKey)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($jobKey) {
            $this->doResumeJob($jobKey);
        });
    }

    /**
     * @param Key $jobKey
     */
    protected function doResumeJob(Key $jobKey)
    {
        foreach ($this->getTriggersForJob($jobKey) as $trigger) {
            $this->doResumeTrigger($trigger->getKey());
        }
    }

    public function resumeJobs(GroupMatcher $matcher)
    {
        // TODO: Implement resumeJobs() method.
    }

    /**
     * {@inheritdoc}
     */
    public function pauseAll()
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () {
            $this->doPauseAll();
        });
    }

    protected function doPauseAll()
    {
        foreach ($this->getTriggerGroupNames() as $name) {
            $triggers = $this->res->getTriggerStorage()->find([
                'group' => $name,
            ]);

            /** @var Trigger $trigger */
            foreach ($triggers as $trigger) {
                $this->doPauseTrigger($trigger->getKey());
            }

            $this->insertPausedTriggerGroup($name);
        }

        $this->insertPausedTriggerGroup(self::ALL_GROUPS_PAUSED);
    }

    /**
     * {@inheritdoc}
     */
    public function resumeAll()
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () {
            $this->doResumeAll();
        });
    }

    protected function doResumeAll()
    {
        foreach ($this->getTriggerGroupNames() as $name) {
            $triggers = $this->res->getTriggerStorage()->find([
                'group' => $name,
            ]);

            /** @var Trigger $trigger */
            foreach ($triggers as $trigger) {
                $this->doResumeTrigger($trigger->getKey());
            }

            $this->deletePausedTriggerGroup($name);
        }

        $this->deletePausedTriggerGroup(self::ALL_GROUPS_PAUSED);
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggersForJob(Key $jobKey)
    {
        // no locks necessary for read...
        return $this->res->getTriggerStorage()->find([
            'jobName' => $jobKey->getName(),
            'jobGroup' => $jobKey->getGroup(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobGroupNames()
    {
        // no locks necessary for read...
        return $this->res->getJobStorage()->getCollection()->distinct('group');
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggerGroupNames()
    {
        // no locks necessary for read...
        return $this->res->getTriggerStorage()->getCollection()->distinct('group');
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggerState(Key $triggerKey)
    {
        if (null == $trigger = $this->retrieveTrigger($triggerKey)) {
            throw new JobPersistenceException(sprintf('There is no trigger with key: "%s"', (string) $triggerKey));
        }

        return $trigger->getState();
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarNames()
    {
        // no locks necessary for read...
        return $this->res->getCalendarStorage()->getCollection()->distinct('name');
    }

    /**
     * {@inheritdoc}
     */
    public function resetTriggerFromErrorState(Key $triggerKey)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function() use ($triggerKey) {
            $this->doResetTriggerFromErrorState($triggerKey);
        });
    }

    /**
     * @param Key $triggerKey
     *
     * @throws JobPersistenceException
     */
    public function doResetTriggerFromErrorState(Key $triggerKey)
    {
        if (null == $trigger = $this->retrieveTrigger($triggerKey)) {
            throw new JobPersistenceException(sprintf('There is no trigger with identity: "%s"', (string) $triggerKey));
        }

        if ($trigger->getState() !== Trigger::STATE_ERROR) {
            return;
        }

        $state = Trigger::STATE_WAITING;
        if ($this->isTriggerGroupPaused($triggerKey->getGroup())) {
            $state = Trigger::STATE_PAUSED;
        }

        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $trigger->getKey()->getName(),
            'group' => $trigger->getKey()->getGroup(),
        ], [
            '$set' => [
                'state' => $state,
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function acquireNextTriggers($noLaterThan, $maxCount, $timeWindow)
    {
        return $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($noLaterThan, $maxCount, $timeWindow) {
            return $this->doAcquireNextTriggers($noLaterThan, $maxCount, $timeWindow);
        });
    }

    /**
     * @param int $noLaterThan
     * @param int $maxCount
     * @param int $timeWindow
     *
     * @return Trigger[]
     *
     * @throws JobPersistenceException
     */
    protected function doAcquireNextTriggers($noLaterThan, $maxCount, $timeWindow)
    {
        $noEarlyThen = time() - $this->misfireThreshold;
        $acquiredTriggers = [];

        $triggers = $this->res->getTriggerStorage()->find([
            'state' => Trigger::STATE_WAITING,
            'nextFireTime.unix' => [
                '$gte' => $noEarlyThen,
                '$lte' => $noLaterThan + $timeWindow,
            ]
        ], [
            'limit' => $maxCount,
            'sort' => [
                'nextFireTime.unix' => 1,
                'priority' => -1,
            ]
        ]);

        $ids = [];
        foreach ($triggers as $trigger) {
            $ids[] = get_object_id($trigger);
            $acquiredTriggers[] = $trigger;
        }

        // find misfired triggers only if free space left
        if (($leftCount = $maxCount - count($ids)) > 0) {
            $misfiredTriggers = $this->res->getTriggerStorage()->find([
                'state' => Trigger::STATE_WAITING,
                'nextFireTime.unix' => [
                    '$lt' => $noEarlyThen,
                ]
            ], [
                'limit' => $leftCount,
                'sort' => [
                    'nextFireTime.unix' => 1,
                    'priority' => -1,
                ]
            ]);

            foreach ($misfiredTriggers as $trigger) {
                $ids[] = get_object_id($trigger);
                $acquiredTriggers[] = $trigger;
            }
        }

        // acquire found triggers
        if ($acquiredTriggers) {
            $result = $this->res->getTriggerStorage()->getCollection()->updateMany([
                '_id' => [
                    '$in' =>  $ids,
                ],
            ], [
                '$set' => [
                    'state' => Trigger::STATE_ACQUIRED,
                ]
            ]);

            if (false == $result->isAcknowledged() || false == $result->getModifiedCount()) {
                throw new JobPersistenceException('Couldn\'t acquire next trigger');
            }
        }

        return $acquiredTriggers;
    }

    public function releaseAcquiredTrigger(Trigger $trigger)
    {
        // TODO: Implement releaseAcquiredTrigger() method.
    }

    /**
     * {@inheritdoc}
     */
    public function triggersFired(array $triggers, $noLaterThan)
    {
        foreach ($triggers as $trigger) {
            if (false == $trigger instanceof Trigger) {
                throw new \InvalidArgumentException(sprintf('Expected array of "%s" but got: "%s"',
                    Trigger::class, is_object($trigger) ? get_class($trigger) : gettype($trigger)));
            }
        }

        $firedTriggers = [];
        foreach ($triggers as $trigger) {
            $_triggers = $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($trigger, $noLaterThan) {
                return $this->doTriggerFired($trigger, $noLaterThan);
            });

            if ($_triggers) {
                $firedTriggers = array_merge($firedTriggers, $_triggers);
            }
        }

        return $firedTriggers;
    }

    /**
     * @param Trigger $trigger
     * @param int     $noLaterThan
     *
     * @return Trigger[]
     *
     * @throws JobPersistenceException
     */
    public function doTriggerFired(Trigger $trigger, $noLaterThan)
    {
        // Make sure trigger wasn't deleted, paused, or completed...
        if ($trigger = $this->retrieveTrigger($trigger->getKey())) {
            if ($trigger->getState() !== Trigger::STATE_ACQUIRED) {
                return [];
            }
        }

        $cal = null;
        if ($trigger->getCalendarName()) {
            if (null == $cal = $this->retrieveCalendar($trigger->getCalendarName())) {
                return [];
            }
        }

        $firedTriggers = [];
        $fireTime = new \DateTime();
        $misfireTime = time() - $this->misfireThreshold;

        // update misfired trigger
        if (((int) $trigger->getNextFireTime()->format('U')) < $misfireTime) {
            $trigger->updateAfterMisfire($cal);
        }

        while (($nextFireTime = $trigger->getNextFireTime()) && (((int) $nextFireTime->format('U')) <= $noLaterThan )) {
            $firedTrigger = clone $trigger;
            $scheduledFireTime = clone $trigger->getNextFireTime();

            $trigger->triggered($cal);

            $firedTrigger->setFireInstanceId(Uuid::uuid4()->toString());
            $firedTrigger->setState(Trigger::STATE_EXECUTING); // @todo need extra statuses for fired triggers
            $firedTrigger->setFireTime(clone $fireTime);
            $firedTrigger->setScheduledFireTime($scheduledFireTime);
            $firedTrigger->setNextFireTime($trigger->getNextFireTime() ? clone $trigger->getNextFireTime() : null);

            $firedTriggers[] = $firedTrigger;
        }

        $state = Trigger::STATE_WAITING;
        if (false == $trigger->getNextFireTime()) {
            $state = Trigger::STATE_COMPLETE;
        }

        $this->doStoreTrigger($trigger, true, $state);

        foreach ($firedTriggers as $firedTrigger) {
            $this->res->getFiredTriggerStorage()->insert($firedTrigger);
        }

        return $firedTriggers;
    }

    /**
     * {@inheritdoc}
     */
    public function triggeredJobComplete(Trigger $trigger, JobDetail $jobDetail, $triggerInstCode)
    {
        $this->executeInLock(self::LOCK_TRIGGER_ACCESS, function () use ($trigger, $jobDetail, $triggerInstCode) {
            $this->doTriggeredJobComplete($trigger, $jobDetail, $triggerInstCode);
        });
    }

    /**
     * @param Trigger   $trigger
     * @param JobDetail $jobDetail
     * @param string    $triggerInstCode
     *
     * @throws JobPersistenceException
     */
    public function doTriggeredJobComplete(Trigger $trigger, JobDetail $jobDetail, $triggerInstCode)
    {
        if ($triggerInstCode === CompletedExecutionInstruction::DELETE_TRIGGER) {
            // remove trigger
            $this->doRemoveTrigger($trigger->getKey());
        } elseif ($triggerInstCode === CompletedExecutionInstruction::SET_TRIGGER_COMPLETE) {
            $this->res->getTriggerStorage()->getCollection()->updateOne([
                'name' => $trigger->getKey()->getName(),
                'group' => $trigger->getKey()->getGroup(),
            ], [
                '$set' => [
                    'state' => Trigger::STATE_COMPLETE,
                ]
            ]);
        } elseif ($triggerInstCode === CompletedExecutionInstruction::SET_TRIGGER_ERROR) {
            $this->res->getTriggerStorage()->getCollection()->updateOne([
                'name' => $trigger->getKey()->getName(),
                'group' => $trigger->getKey()->getGroup(),
            ], [
                '$set' => [
                    'state' => Trigger::STATE_ERROR,
                ]
            ]);
        } elseif ($triggerInstCode === CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE) {
            $this->res->getTriggerStorage()->getCollection()->updateMany([
                'jobName' => $trigger->getJobKey()->getName(),
                'jobGroup' => $trigger->getJobKey()->getGroup(),
            ], [
                '$set' => [
                    'state' => Trigger::STATE_COMPLETE,
                ]
            ]);
        } elseif ($triggerInstCode === CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR) {
            $this->res->getTriggerStorage()->getCollection()->updateMany([
                'jobName' => $trigger->getJobKey()->getName(),
                'jobGroup' => $trigger->getJobKey()->getGroup(),
            ], [
                '$set' => [
                    'state' => Trigger::STATE_ERROR,
                ]
            ]);
        }

        // remove fired triggers
        $result = $this->res->getFiredTriggerStorage()->getCollection()->deleteOne([
            'fireInstanceId' => $trigger->getFireInstanceId(),
        ]);

        if (false == $result->isAcknowledged() || false == $result->getDeletedCount()) {
            throw new JobPersistenceException(sprintf('Couldn\'t delete fired trigger: fireInstanceId "%s"', $trigger->getFireInstanceId()));
        }
    }

    /**
     * @param string   $lockName
     * @param callable $callback
     *
     * @return mixed
     */
    private function executeInLock($lockName, callable $callback)
    {
        $this->res->getManagementLock()->lock($lockName);

        try {
            return call_user_func($callback);
        } finally {
            $this->res->getManagementLock()->unlock($lockName);
        }
    }

    /**
     * @param string $groupName
     *
     * @return boolean
     */
    public function insertPausedTriggerGroup($groupName)
    {
        $result = $this->res->getPausedTriggerCol()->updateOne(
            ['groupName' => $groupName],
            ['$set' => ['groupName' => $groupName]],
            ['upsert' => true]
        );

        return $result->isAcknowledged() && $result->getUpsertedCount();
    }

    /**
     * @param string $groupName
     *
     * @return boolean
     */
    public function deletePausedTriggerGroup($groupName)
    {
        $result = $this->res->getPausedTriggerCol()->deleteOne(['groupName' => $groupName]);

        return $result->isAcknowledged() && $result->getDeletedCount();
    }

    /**
     * @param string $groupName
     *
     * @return boolean
     */
    public function isTriggerGroupPaused($groupName)
    {
        return (bool) $this->res->getPausedTriggerCol()->count(['groupName' => $groupName]);
    }
}
