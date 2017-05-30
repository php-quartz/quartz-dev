<?php
namespace Quartz\Triggers;

use Makasim\Values\CastTrait;
use Makasim\Values\ValuesTrait;
use Quartz\Core\CompletedExecutionInstruction;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;

abstract class AbstractTrigger implements Trigger
{
    use CastTrait;
    use ValuesTrait {
        setValue as public;
        getValue as public;
    }

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
        return $this->getValue('jobDataMap');
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
        return $this->getValue('startTime', null, \DateTime::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setStartTime(\DateTime $startTime)
    {
        $this->setValue('startTime', $startTime);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndTime()
    {
        return $this->getValue('endTime', null, \DateTime::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setEndTime(\DateTime $endTime = null)
    {
        $this->setValue('endTime', $endTime);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextFireTime()
    {
        return $this->getValue('nextFireTime', null, \DateTime::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setNextFireTime(\DateTime $nextFireTime = null)
    {
        $this->setValue('nextFireTime', $nextFireTime);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousFireTime()
    {
        return $this->getValue('previousFireTime', null, \DateTime::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setPreviousFireTime(\DateTime $previousFireTime)
    {
        $this->setValue('previousFireTime', $previousFireTime);
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
        $this->setValue('fireTime', $time);
    }

    public function getFireTime()
    {
        return $this->getValue('fireTime', null, \DateTime::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setScheduledFireTime(\DateTime $time)
    {
        $this->setValue('scheduledFireTime', $time);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledFireTime()
    {
        return $this->getValue('scheduledFireTime', null, \DateTime::class);
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
        $this->setValue('misfireInstruction', $misfireInstruction);
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

    function __clone()
    {
        unset($this->_id);
    }
}
