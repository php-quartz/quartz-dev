<?php
namespace Quartz\Triggers;

use Formapro\Values\Cast\CastDateTime;
use Formapro\Values\ValuesTrait;
use Quartz\Core\Calendar;
use Quartz\Core\CompletedExecutionInstruction;
use Quartz\Core\DateBuilder;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Key;
use Quartz\Core\Model;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;

abstract class AbstractTrigger implements Model, Trigger
{
    use ValuesTrait;

    /**
     * @var Key
     */
    private $key;

    /**
     * @var Key
     */
    private $jobKey;

    /**
     * @param string $instance
     */
    public function __construct($instance)
    {
        $this->setInstance($instance);
        $this->setPriority(self::DEFAULT_PRIORITY);
        $this->setMisfireInstruction(self::MISFIRE_INSTRUCTION_SMART_POLICY);
    }

    /**
     * @param string $instance
     */
    protected function setInstance($instance)
    {
        $this->setValue('instance', $instance);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        if (null == $this->key) {
            $this->key = new Key($this->getValue('name'), $this->getValue('group'));
        }

        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey(Key $key)
    {
        $this->key = $key;

        $this->setValue('name', $key->getName());
        $this->setValue('group', $key->getGroup());
    }

    /**
     * {@inheritdoc}
     */
    public function getJobKey()
    {
        if (null == $this->jobKey) {
            if ($this->getValue('jobName')) {
                $this->jobKey = new Key($this->getValue('jobName'), $this->getValue('jobGroup'));
            }
        }

        return $this->jobKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setJobKey(Key $key)
    {
        $this->jobKey = $key;

        $this->setValue('jobName', $key->getName());
        $this->setValue('jobGroup', $key->getGroup());
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->getValue('description');
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description = null)
    {
        $this->setValue('description', $description);
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarName()
    {
        return $this->getValue('calendarName');
    }

    /**
     * {@inheritdoc}
     */
    public function setCalendarName($calendarName = null)
    {
        $this->setValue('calendarName', $calendarName);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobDataMap()
    {
        return $this->getValue('jobDataMap', []);
    }

    /**
     * {@inheritdoc}
     */
    public function setJobDataMap(array $jobDataMap)
    {
        $this->setValue('jobDataMap', $jobDataMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->getValue('priority');
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->setValue('priority', $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime()
    {
        return CastDateTime::from($this->getValue('startTime'));
    }

    /**
     * {@inheritdoc}
     */
    public function setStartTime(\DateTime $startTime)
    {
        $endTime = $this->getEndTime();

        if ($endTime && $endTime < $startTime) {
            throw new \InvalidArgumentException('End time cannot be before start time');
        }

        $this->setValue('startTime', CastDateTime::to($startTime));
    }

    /**
     * {@inheritdoc}
     */
    public function getEndTime()
    {
        return CastDateTime::from($this->getValue('endTime'));
    }

    /**
     * {@inheritdoc}
     */
    public function setEndTime(\DateTime $endTime = null)
    {
        $startTime = $this->getStartTime();

        if ($startTime && $endTime && $startTime > $endTime) {
            throw new \InvalidArgumentException('End time cannot be before start time');
        }

        $this->setValue('endTime', CastDateTime::to($endTime));
    }

    /**
     * {@inheritdoc}
     */
    public function getNextFireTime()
    {
        return CastDateTime::from($this->getValue('nextFireTime'));
    }

    /**
     * {@inheritdoc}
     */
    public function setNextFireTime(\DateTime $nextFireTime = null)
    {
        $this->setValue('nextFireTime', CastDateTime::to($nextFireTime));
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousFireTime()
    {
        return CastDateTime::from($this->getValue('previousFireTime'));
    }

    /**
     * {@inheritdoc}
     */
    public function setPreviousFireTime(\DateTime $previousFireTime)
    {
        $this->setValue('previousFireTime', CastDateTime::to($previousFireTime));
    }

    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return $this->getValue('state');
    }

    /**
     * {@inheritdoc}
     */
    public function setState($state)
    {
        $this->setValue('state', $state);
    }

    /**
     * @return int
     */
    public function getTimesTriggered()
    {
        return $this->getValue('timesTriggered', 0);
    }

    /**
     * @param int $timesTriggered
     */
    public function setTimesTriggered($timesTriggered)
    {
        $this->setValue('timesTriggered', $timesTriggered);
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        if ($this->getValue('name') == null) {
            throw new SchedulerException('Trigger\'s name cannot be null');
        }

        if ($this->getValue('group') == null) {
            throw new SchedulerException("Trigger's group cannot be null");
        }

        if ($this->getValue('jobName') == null) {
            throw new SchedulerException('Trigger\'s related Job\'s name cannot be null');
        }

        if ($this->getValue('jobGroup') == null) {
            throw new SchedulerException('Trigger\'s related Job\'s group cannot be null');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setFireInstanceId($id)
    {
        $this->setValue('fireInstanceId', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getFireInstanceId()
    {
        return $this->getValue('fireInstanceId');
    }

    /**
     * {@inheritdoc}
     */
    public function setFireTime(\DateTime $time)
    {
        $this->setValue('fireTime', CastDateTime::to($time));
    }

    /**
     * {@inheritdoc}
     */
    public function getFireTime()
    {
        return CastDateTime::from($this->getValue('fireTime'));
    }

    /**
     * {@inheritdoc}
     */
    public function setScheduledFireTime(\DateTime $time)
    {
        $this->setValue('scheduledFireTime', CastDateTime::to($time));
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledFireTime()
    {
        return CastDateTime::from($this->getValue('scheduledFireTime');
    }

    /**
     * {@inheritdoc}
     */
    public function getMisfireInstruction()
    {
        return $this->getValue('misfireInstruction');
    }

    /**
     * {@inheritdoc}
     */
    public function setMisfireInstruction($misfireInstruction)
    {
        if (false == $this->validateMisfireInstruction($misfireInstruction)) {
            throw new \InvalidArgumentException('The misfire instruction code is invalid for this type of trigger.');
        }

        $this->setValue('misfireInstruction', $misfireInstruction);
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorMessage($message)
    {
        $this->setValue('errorMessage', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMessage()
    {
        return $this->getValue('errorMessage');
    }

    /**
     * @param int $candidateMisfireInstruction
     *
     * @return bool
     */
    protected abstract function validateMisfireInstruction($candidateMisfireInstruction);

    /**
     * <p>
     * Gets the time zone within which time calculations related to this
     * trigger will be performed.
     * </p>
     *
     * <p>
     * If null, the system default TimeZone will be used.
     * </p>
     *
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        if (false == $timeZone = $this->getValue('timeZone')) {
            $timeZone = date_default_timezone_get();
        }

        return new \DateTimeZone($timeZone);
    }

    /**
     * <p>
     * Sets the time zone within which time calculations related to this
     * trigger will be performed.
     * </p>
     *
     * @param \DateTimeZone $timeZone the desired TimeZone, or null for the system default.
     */
    public function setTimeZone(\DateTimeZone $timeZone)
    {
        $this->setValue('timeZone', $timeZone->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function executionComplete(JobExecutionContext $context)
    {
        if ($context->isRefireImmediately()) {
            return CompletedExecutionInstruction::RE_EXECUTE_JOB;
        }

        if ($context->isUnscheduleFiringTrigger()) {
            return CompletedExecutionInstruction::SET_TRIGGER_COMPLETE;
        }

        if ($context->isUnscheduleAllTriggers()) {
            return CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE;
        }

        if (false == $this->mayFireAgain()) {
            return CompletedExecutionInstruction::DELETE_TRIGGER;
        }

        return CompletedExecutionInstruction::NOOP;
    }

    /**
     * {@inheritdoc}
     */
    public function mayFireAgain()
    {
        return (bool) $this->getNextFireTime();
    }

    /**
     * {@inheritdoc}
     */
    public function computeFirstFireTime(Calendar $calendar = null)
    {
        $nextFireTime = clone $this->getStartTime();
        $nextFireTime->sub(new \DateInterval('PT1S'));
        $nextFireTime = $this->getFireTimeAfter($nextFireTime);

        $yearToGiveUpSchedulingAt = DateBuilder::MAX_YEAR();

        while ($nextFireTime && $calendar && false == $calendar->isTimeIncluded(((int) $nextFireTime->format('U')))) {
            $nextFireTime = $this->getFireTimeAfter($nextFireTime);

            if ($nextFireTime == null) {
                break;
            }

            //avoid infinite loop
            if (((int) $nextFireTime->format('Y')) > $yearToGiveUpSchedulingAt) {
                $nextFireTime = null;
            }
        }

        $this->setNextFireTime($nextFireTime);

        return $nextFireTime;
    }

    /**
     * {@inheritdoc}
     */
    public function triggered(Calendar $calendar = null)
    {
        $this->setTimesTriggered($this->getTimesTriggered() + 1);
        $this->setPreviousFireTime($nextFireTime = $this->getNextFireTime());
        $nextFireTime = $this->getFireTimeAfter($nextFireTime);

        $yearToGiveUpSchedulingAt = DateBuilder::MAX_YEAR();

        while ($nextFireTime && $calendar && false == $calendar->isTimeIncluded(((int) $nextFireTime->format('U')))) {
            $nextFireTime = $this->getFireTimeAfter($nextFireTime);

            if ($nextFireTime == null) {
                break;
            }

            //avoid infinite loop
            if (((int) $nextFireTime->format('Y')) > $yearToGiveUpSchedulingAt) {
                $nextFireTime = null;
            }
        }

        $this->setNextFireTime($nextFireTime);
    }

    /**
     * {@inheritdoc}
     */
    public function updateWithNewCalendar(Calendar $cal = null, $misfireThreshold)
    {
        $nextFireTime = $this->getFireTimeAfter($this->getPreviousFireTime());

        $now = new \DateTime();
        $yearToGiveUpSchedulingAt = DateBuilder::MAX_YEAR();

        while ($nextFireTime && $cal && false == $cal->isTimeIncluded(((int) $nextFireTime->format('U')))) {
            $nextFireTime = $this->getFireTimeAfter($nextFireTime);

            if (null == $nextFireTime) {
                break;
            }

            //avoid infinite loop
            if (((int) $nextFireTime->format('Y')) > $yearToGiveUpSchedulingAt) {
                $nextFireTime = null;
            }

            if ($nextFireTime && $nextFireTime < $now) {
                $diff = ((int) $now->format('U')) - ((int) $nextFireTime->format('U'));

                if ($diff >= $misfireThreshold) {
                    $nextFireTime = $this->getFireTimeAfter($nextFireTime);
                }
            }
        }

        $this->setNextFireTime($nextFireTime);
    }

    function __clone()
    {
        unset($this->_id);
    }
}
