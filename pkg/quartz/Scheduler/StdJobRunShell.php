<?php
namespace Quartz\Scheduler;

use Quartz\Core\CompletedExecutionInstruction;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Trigger;

class StdJobRunShell implements JobRunShell
{
    /**
     * @var StdScheduler
     */
    private $scheduler;

    /**
     * {@inheritdoc}
     */
    public function initialize(StdScheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Trigger $trigger)
    {
        if (false == $jobDetail = $this->scheduler->getJobDetail($trigger->getJobKey())) {
            $trigger->setErrorMessage(sprintf('Job was not found with key: "%s"', (string) $trigger->getJobKey()));
            $this->scheduler->notifyJobStoreJobComplete($trigger, null, CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR);

            return;
        }

        $calendar = null;
        if ($trigger->getCalendarName()) {
            if (false == $calendar = $this->scheduler->getCalendar($trigger->getCalendarName())) {
                $trigger->setErrorMessage(sprintf('Calendar was not found with name: "%s"', (string) $trigger->getCalendarName()));
                $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR);

                return;
            }
        }

        try {
            $job = $this->scheduler->getJobFactory()->newJob($jobDetail);
        } catch (\Exception $e) {
            $trigger->setErrorMessage(sprintf('Job instance was not created: "%s"', (string) $jobDetail->getKey()));
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
                $trigger->setErrorMessage(sprintf('Sleep time is too long. "%d"', $sleepTime));
                $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, CompletedExecutionInstruction::NOOP);
            }

            sleep($scheduledFireTime - $now);
        }

        $startTime = microtime(true);
        while (true) {
            if ($this->scheduler->notifyTriggerListenersFired($context)) {
                // trigger vetoed
                $this->scheduler->notifyJobListenersWasVetoed($context);

                $instructionCode = $trigger->executionComplete($context);
                $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, $instructionCode);

                if (null == $trigger->getNextFireTime()) {
                    $this->scheduler->notifySchedulerListenersFinalized($trigger);
                }

                break;
            }

            $this->scheduler->notifyJobListenersToBeExecuted($context);

            try {
                $job->execute($context);
            } catch (\Exception $e) {
                $context->setException($e);
            } catch (\Error $e) {
                $context->setException($e);
            }

            $endTime = microtime(true);
            $context->setJobRunTime(($endTime - $startTime) * 1000);

            $this->scheduler->notifyJobListenersWasExecuted($context);

            $instructionCode = $trigger->executionComplete($context);

            $this->scheduler->notifyTriggerListenersComplete($context);

            if ($instructionCode === CompletedExecutionInstruction::RE_EXECUTE_JOB) {
                $context->incrementRefireCount();

                continue;
            }

            $this->scheduler->notifyJobStoreJobComplete($trigger, $jobDetail, $instructionCode);

            break;
        }
    }
}
