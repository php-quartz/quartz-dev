<?php
namespace Quartz\Core;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * <code>TriggerBuilder</code> is used to instantiate {@link Trigger}s.
 *
 * <p>The builder will always try to keep itself in a valid state, with
 * reasonable defaults set for calling build() at any point.  For instance
 * if you do not invoke <i>withSchedule(..)</i> method, a default schedule
 * of firing once immediately will be used.  As another example, if you
 * do not invoked <i>withIdentity(..)</i> a trigger name will be generated
 * for you.</p>
 *
 * <p>Quartz provides a builder-style API for constructing scheduling-related
 * entities via a Domain-Specific Language (DSL).  The DSL can best be
 * utilized through the usage of static imports of the methods on the classes
 * <code>TriggerBuilder</code>, <code>JobBuilder</code>,
 * <code>DateBuilder</code>, <code>JobKey</code>, <code>TriggerKey</code>
 * and the various <code>ScheduleBuilder</code> implementations.</p>
 *
 * <p>Client code can then use the DSL to write code such as this:</p>
 * <pre>
 *         JobDetail job = newJob(MyJob.class)
 *             .withIdentity("myJob")
 *             .build();
 *
 *         Trigger trigger = newTrigger()
 *             .withIdentity(triggerKey("myTrigger", "myTriggerGroup"))
 *             .withSchedule(simpleSchedule()
 *                 .withIntervalInHours(1)
 *                 .repeatForever())
 *             .startAt(futureDate(10, MINUTES))
 *             .build();
 *
 *         scheduler.scheduleJob(job, trigger);
 * <pre>
 */
class TriggerBuilder
{
    /**
     * @var Key
     */
    private $key;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var \DateTime
     */
    private $endTime;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var string
     */
    private $calendarName;

    /**
     * @var Key
     */
    private $jobKey;

    /**
     * @var array
     */
    private $jobDataMap;

    /**
     * @var ScheduleBuilder
     */
    private $scheduleBuilder;

    private function __construct()
    {
        $this->startTime = new \DateTime();
        $this->priority = Trigger::DEFAULT_PRIORITY;
        $this->jobDataMap = [];
    }

    /**
     * Create a new TriggerBuilder with which to define a
     * specification for a Trigger.
     *
     * @return TriggerBuilder
     */
    public static function newTrigger() {
        return new TriggerBuilder();
    }


    /**
     * Produce the <code>Trigger</code>.
     *
     * @return Trigger that meets the specifications of the builder.
     */
    public function build()
    {
        if(null == $this->scheduleBuilder) {
            $this->scheduleBuilder = SimpleScheduleBuilder::simpleSchedule();
        }

        $trigger = $this->scheduleBuilder->build();

        $trigger->setCalendarName($this->calendarName);
        $trigger->setDescription($this->description);
        $trigger->setStartTime($this->startTime);
        $trigger->setEndTime($this->endTime);

        if(null == $this->key) {
            $this->key = new Key(Key::createUniqueName(null), null);
        }

        $trigger->setKey($this->key);

        if($this->jobKey) {
            $trigger->setJobKey($this->jobKey);
        }

        $trigger->setPriority($this->priority);

        if($this->jobDataMap) {
            $trigger->setJobDataMap($this->jobDataMap);
        }

        return $trigger;
    }

    /**
     * Use a TriggerKey with the given name and group to
     * identify the Trigger.
     *
     * <p>If none of the 'withIdentity' methods are set on the TriggerBuilder,
     * then a random, unique TriggerKey will be generated.</p>
     *
     * @param string $name the name element for the Trigger's TriggerKey
     * @param string $group the group element for the Trigger's TriggerKey
     *
     * @return TriggerBuilder
     *
     * @see TriggerKey
     * @see Trigger#getKey()
     */
    public function withIdentity($name, $group = null)
    {
        $this->key = new Key($name, $group);

        return $this;
    }

    /**
     * Use the given TriggerKey to identify the Trigger.
     *
     * <p>If none of the 'withIdentity' methods are set on the TriggerBuilder,
     * then a random, unique TriggerKey will be generated.</p>
     *
     * @param Key $triggerKey the TriggerKey for the Trigger to be built
     *
     * @return TriggerBuilder
     *
     * @see TriggerKey
     * @see Trigger#getKey()
     */
    public function withIdentityKey(Key $triggerKey)
    {
        $this->key = clone $triggerKey;

        return $this;
    }

    /**
     * Set the given (human-meaningful) description of the Trigger.
     *
     * @param string $triggerDescription the description for the Trigger
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getDescription()
     */
    public function withDescription($triggerDescription)
    {
        $this->description = $triggerDescription;

        return $this;
    }

    /**
     * Set the Trigger's priority.  When more than one Trigger have the same
     * fire time, the scheduler will fire the one with the highest priority
     * first.
     *
     * @param int $triggerPriority the priority for the Trigger
     *
     * @return TriggerBuilder
     *
     * @see Trigger#DEFAULT_PRIORITY
     * @see Trigger#getPriority()
     */
    public function withPriority($triggerPriority)
    {
        $this->priority = $triggerPriority;

        return $this;
    }

    /**
     * Set the name of the {@link Calendar} that should be applied to this
     * Trigger's schedule.
     *
     * @param Calendar $calName the name of the Calendar to reference.
     *
     * @return TriggerBuilder
     *
     * @see Calendar
     * @see Trigger#getCalendarName()
     */
    public function modifiedByCalendar($calName)
    {
        $this->calendarName = $calName;

        return $this;
    }

    /**
     * Set the time the Trigger should start at - the trigger may or may
     * not fire at this time - depending upon the schedule configured for
     * the Trigger.  However the Trigger will NOT fire before this time,
     * regardless of the Trigger's schedule.
     *
     * @param \DateTime $triggerStartTime the start time for the Trigger.
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getStartTime()
     * @see DateBuilder
     */
    public function startAt(\DateTime $triggerStartTime)
    {
        $this->startTime = $triggerStartTime;

        return $this;
    }

    /**
     * Set the time the Trigger should start at to the current moment -
     * the trigger may or may not fire at this time - depending upon the
     * schedule configured for the Trigger.
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getStartTime()
     */
    public function startNow()
    {
        $this->startTime = new \DateTime();

        return $this;
    }

    /**
     * Set the time at which the Trigger will no longer fire - even if it's
     * schedule has remaining repeats.
     *
     * @param \DateTime $triggerEndTime the end time for the Trigger.  If null, the end time is indefinite.
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getEndTime()
     * @see DateBuilder
     */
    public function endAt(\DateTime $triggerEndTime)
    {
        $this->endTime = $triggerEndTime;

        return $this;
    }

    /**
     * Set the {@link ScheduleBuilder} that will be used to define the
     * Trigger's schedule.
     *
     * <p>The particular <code>SchedulerBuilder</code> used will dictate
     * the concrete type of Trigger that is produced by the TriggerBuilder.</p>
     *
     * @param ScheduleBuilder $schedBuilder the SchedulerBuilder to use.
     *
     * @return TriggerBuilder
     *
     * @see ScheduleBuilder
     * @see SimpleScheduleBuilder
     * @see CronScheduleBuilder
     * @see CalendarIntervalScheduleBuilder
     */
    public function withSchedule(ScheduleBuilder $schedBuilder)
    {
        $this->scheduleBuilder = $schedBuilder;

        return $this;
    }

    /**
     * Set the identity of the Job which should be fired by the produced
     * Trigger.
     *
     * @param Key $keyOfJobToFire the identity of the Job to fire.
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getJobKey()
     */
    public function forJobKey(Key $keyOfJobToFire)
    {
        $this->jobKey = clone $keyOfJobToFire;

        return $this;
    }

    /**
     * Set the identity of the Job which should be fired by the produced
     * Trigger - a <code>JobKey</code> will be produced with the given
     * name and group.
     *
     * @param string $jobName the name of the job to fire.
     * @param string $jobGroup the group of the job to fire.
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getJobKey()
     */
    public function forJob($jobName, $jobGroup = null)
    {
        $this->jobKey = new Key($jobName, $jobGroup);

        return $this;
    }

    /**
     * Set the identity of the Job which should be fired by the produced
     * Trigger, by extracting the JobKey from the given job.
     *
     * @param JobDetail $jobDetail the Job to fire.
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getJobKey()
     */
    public function forJobDetail(JobDetail $jobDetail)
    {
        if(null == $key = $jobDetail->getKey()) {
            throw new InvalidArgumentException('The given job has not yet had a key assigned to it.');
        }

        $this->jobKey = clone $key;

        return $this;
    }

    /**
     * Add the given key-value pair to the Trigger's {@link JobDataMap}.
     *
     * @param string $dataKey
     * @param mixed  $value
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getJobDataMap()
     */
    public function usingJobData($dataKey, $value)
    {
        if (false == is_string($dataKey)) {
            throw new \InvalidArgumentException('dataKey must be a string');
        }

        if (false == is_scalar($value) || false == is_array($value)) {
            throw new \InvalidArgumentException('value must be an array or scalar');
        }

        $this->jobDataMap[$dataKey] = $value;

        return $this;
    }

    /**
     * Set the Trigger's {@link JobDataMap}, adding any values to it
     * that were already set on this TriggerBuilder using any of the
     * other 'usingJobData' methods.
     *
     * @param array $newJobDataMap
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getJobDataMap()
     */
    public function usingJobDataArray(array $newJobDataMap)
    {
        foreach ($newJobDataMap as $key => $value) {
            $this->usingJobData($key, $value);
        }

        return $this;
    }

    /**
     * Replace the {@code Trigger}'s {@link JobDataMap} with the
     * given {@code JobDataMap}.
     *
     * @param array $newJobDataMap
     *
     * @return TriggerBuilder
     *
     * @see Trigger#getJobDataMap()
     */
    public function setJobData(array $newJobDataMap)
    {
        $this->jobDataMap = $newJobDataMap;

        return $this;
    }
}