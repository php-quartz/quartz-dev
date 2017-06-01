<?php
namespace Quartz\Core;

class SyncJobRunShell implements JobRunShell
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * {@inheritdoc}
     */
    public function initialize(Scheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Trigger $trigger)
    {
        if (false == $jobDetail = $this->scheduler->getJobDetail($trigger->getJobKey())) {
            // @todo set error description into trigger
            $this->scheduler->notifyJobStoreJobComplete($trigger, null, CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR);

            return;
        }

        $calendar = null;
        if ($trigger->getCalendarName()) {
            if (false == $calendar = $this->scheduler->getCalendar($trigger->getCalendarName())) {
                // @todo set error description into trigger
                $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR);

                return;
            }
        }

        try {
            $job = $this->scheduler->getJobFactory()->newJob($jobDetail);
        } catch (\Exception $e) {
            // @todo set error description into trigger
            $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR);

            return;
        }

        $context = new JobExecutionContext($this->scheduler, $trigger, $jobDetail, $calendar);

        $now = time();
        $scheduledFireTime = (int) $context->getTrigger()->getScheduledFireTime()->format('U');

        // sleep until execution time is came up
        if ($scheduledFireTime > $now) {
            $sleepTime = $scheduledFireTime - $now;

            if ($sleepTime > 120) { // 2 min
//                throw new SchedulerException(sprintf('Sleep time is too long. "%d"', $sleepTime));
                // @todo set error description
                $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, CompletedExecutionInstruction::NOOP);
            }

            sleep($scheduledFireTime - $now);
        }

        $startTime = microtime(true);
        while (true) {
            try {
                $job->execute($context);
            } catch (\Exception $e) {
                $context->setException($e);
            } catch (\Error $e) {
                $context->setException($e);
            }

            $endTime = microtime(true);
            $context->setJobRunTime(($endTime - $startTime) * 1000);

            $instructionCode = $trigger->executionComplete($context);

            if ($instructionCode === CompletedExecutionInstruction::RE_EXECUTE_JOB) {
                $context->incrementRefireCount();

                continue;
            }

            $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, $instructionCode);

            break;
        }
    }
}
