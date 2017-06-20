<?php
namespace Quartz\Core;

use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class JobExecutionContext
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var Trigger
     */
    private $trigger;

    /**
     * @var Calendar
     */
    private $calendar;

    /**
     * @var JobDetail
     */
    private $jobDetail;

    /**
     * @var array
     */
    private $jobDataMap;

    /**
     * @var int
     */
    private $jobRunTime;

    /**
     * @var mixed
     */
    private $result;

    /**
     * @var \Exception|\Error
     */
    private $exception;

    /**
     * @var int
     */
    private $numRefires;

    /**
     * @var string
     */
    private $instruction;

    /**
     * @param Scheduler $scheduler
     * @param Trigger   $trigger
     * @param JobDetail $jobDetail
     * @param Calendar  $calendar
     */
    public function __construct(Scheduler $scheduler, Trigger $trigger, JobDetail $jobDetail, Calendar $calendar = null)
    {
        $this->scheduler = $scheduler;
        $this->trigger = $trigger;
        $this->jobDetail = $jobDetail;
        $this->calendar = $calendar;
        $this->numRefires = 0;
        $this->jobDataMap = array_merge($jobDetail->getJobDataMap(), $trigger->getJobDataMap());

        set_value($trigger, 'execution.jobDetail', get_values($jobDetail));
        set_value($trigger, 'execution.jobDataMap', $this->jobDataMap);
        $calendar && set_value($trigger, 'execution.calendar', get_values($calendar));
    }

    /**
     * <p>
     * Get a handle to the <code>Scheduler</code> instance that fired the
     * <code>Job</code>.
     * </p>
     *
     * @return Scheduler
     */
    public function getScheduler()
    {
        return $this->scheduler;
    }

    /**
     * <p>
     * Get a handle to the <code>Trigger</code> instance that fired the
     * <code>Job</code>.
     * </p>
     *
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * <p>
     * Get the <code>JobDetail</code> associated with the <code>Job</code>.
     * </p>
     *
     * @return JobDetail
     */
    public function getJobDetail()
    {
        return $this->jobDetail;
    }

    /**
     * <p>
     * Get a handle to the <code>Calendar</code> referenced by the <code>Trigger</code>
     * instance that fired the <code>Job</code>.
     * </p>
     *
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @return array
     */
    public function getMergedJobDataMap()
    {
        return $this->jobDataMap;
    }

    /**
     * The amount of time the job ran for (in milliseconds).  The returned
     * value will be null until the job has actually completed (or thrown an
     * exception), and is therefore generally only useful to
     * <code>JobListener</code>s and <code>TriggerListener</code>s.
     *
     * @return int Returns the jobRunTime in msec.
     */
    public function getJobRunTime()
    {
        return $this->jobRunTime;
    }

    /**
     * @param int $msec
     */
    public function setJobRunTime($msec)
    {
        $this->jobRunTime = $msec;

        set_value($this->trigger, 'execution.jobRunTime', $msec);
    }

    /**
     * Returns the result (if any) that the <code>Job</code> set before its
     * execution completed (the type of object set as the result is entirely up
     * to the particular job).
     *
     * <p>
     * The result itself is meaningless to Quartz, but may be informative
     * to <code>{@link JobListener}s</code> or
     * <code>{@link TriggerListener}s</code> that are watching the job's
     * execution.
     * </p>
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set the result (if any) of the <code>Job</code>'s execution (the type of
     * object set as the result is entirely up to the particular job).
     *
     * <p>
     * The result itself is meaningless to Quartz, but may be informative
     * to <code>{@link JobListener}s</code> or
     * <code>{@link TriggerListener}s</code> that are watching the job's
     * execution.
     * </p>
     *
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;

        set_value($this->trigger, 'execution.result', $result);
    }

    /**
     * @return \Error|\Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Error|\Exception $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;

        set_value($this->trigger, 'execution.exception', [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);
    }

    public function incrementRefireCount()
    {
        $this->numRefires++;

        set_value($this->trigger, 'execution.refireCount', $this->numRefires);
    }

    /**
     * {@inheritDoc}
     */
    public function getRefireCount()
    {
        return $this->numRefires;
    }

    public function setRefireImmediately()
    {
        $this->instruction = CompletedExecutionInstruction::RE_EXECUTE_JOB;

        set_value($this->trigger, 'execution.instruction', CompletedExecutionInstruction::RE_EXECUTE_JOB);
    }

    public function isRefireImmediately()
    {
        return $this->instruction === CompletedExecutionInstruction::RE_EXECUTE_JOB;
    }

    public function setUnscheduleFiringTrigger()
    {
        $this->instruction = CompletedExecutionInstruction::SET_TRIGGER_COMPLETE;

        set_value($this->trigger, 'execution.instruction', CompletedExecutionInstruction::SET_TRIGGER_COMPLETE);
    }

    public function isUnscheduleFiringTrigger()
    {
        return $this->instruction === CompletedExecutionInstruction::SET_TRIGGER_COMPLETE;
    }

    public function setUnscheduleAllTriggers()
    {
        $this->instruction = CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE;

        set_value($this->trigger, 'execution.instruction', CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE);
    }

    public function isUnscheduleAllTriggers()
    {
        return $this->instruction === CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE;
    }
}
