<?php
namespace Quartz\Core;

class Scheduler
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
     * sec
     *
     * @var int
     */
    private $sleepTime;

    /**
     * @var int
     */
    private $maxCount;

    /**
     * @var int
     */
    private $timeWindow;

    public function __construct(JobStore $store, JobRunShellFactory $jobRunShellFactory, JobFactory $jobFactory)
    {
        $this->store = $store;
        $this->jobRunShellFactory = $jobRunShellFactory;
        $this->jobFactory = $jobFactory;

        $this->maxCount = 10;
        $this->sleepTime = 5;
        $this->timeWindow = 30;
    }

    public function start()
    {
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
     * @param Key $key
     *
     * @return null|JobDetail
     */
    public function retrieveJob(Key $key)
    {
        return $this->store->retrieveJob($key);
    }

    /**
     * @param string $name
     *
     * @return null|Calendar
     */
    public function retrieveCalendar($name)
    {
        return $this->store->retrieveCalendar($name);
    }

    /**
     * @return JobFactory
     */
    public function getJobFactory()
    {
        return $this->jobFactory;
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
     * @param JobDetail $jobDetail
     * @param Trigger   $trigger
     *
     * @return \DateTime
     *
     * @throws SchedulerException
     */
    public function scheduleJob(JobDetail $jobDetail, Trigger $trigger)
    {
        if ($jobDetail->getKey() == null) {
            throw new SchedulerException('Job\'s key cannot be null');
        }

        if ($trigger->getJobKey() == null) {
            $trigger->setJobKey($jobDetail->getKey());
        } else if (!$trigger->getJobKey()->equals($jobDetail->getKey())) {
            throw new SchedulerException('Trigger does not reference given job!');
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

        $this->store->storeJobAndTrigger($jobDetail, $trigger);

        return $firstFireTime;
    }
}
