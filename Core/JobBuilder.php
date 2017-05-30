<?php
namespace Quartz\Core;
use Quartz\JobDetail\JobDetail;

/**
 * <code>JobBuilder</code> is used to instantiate {@link JobDetail}s.
 *
 * <p>The builder will always try to keep itself in a valid state, with
 * reasonable defaults set for calling build() at any point.  For instance
 * if you do not invoke <i>withIdentity(..)</i> a job name will be generated
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
class JobBuilder
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
     * @var string
     */
    private $jobClass;

    /**
     * @var bool
     */
    private $durability;

//    /**
//     * is not implemented
//     *
//     * @var bool
//     */
//    private $shouldRecover;

    /**
     * @var array
     */
    private $jobDataMap;

    private function __construct()
    {
        $this->jobDataMap = [];
    }

    /**
     * Create a JobBuilder with which to define a <code>JobDetail</code>,
     * and set the class name of the <code>Job</code> to be executed.
     *
     * @param string $jobClass
     *
     * @return JobBuilder
     */
    public static function newJob($jobClass = null)
    {
        $builder = new static();
        $builder->ofType($jobClass);

        return $builder;
    }

    /**
     * Produce the <code>JobDetail</code> instance defined by this
     * <code>JobBuilder</code>.
     *
     * @return JobDetail.
     */
    public function build()
    {
        $job = new JobDetail();

//        $job->setJobClass($this->jobClass);
        if ($this->jobClass) {
            $job->setValue('jobClass', $this->jobClass);
        }

        if(null == $this->key) {
            $this->key = new Key(Key::createUniqueName(null), null);
        }

        $job->setKey($this->key);
        $job->setDescription($this->description);
        $job->setDurable($this->durability);
//        $job->setRequestsRecovery($this->shouldRecover);
        $job->setJobDataMap($this->jobDataMap);

        return $job;
    }

    /**
     * Use a <code>JobKey</code> with the given name and group to
     * identify the JobDetail.
     *
     * <p>If none of the 'withIdentity' methods are set on the JobBuilder,
     * then a random, unique JobKey will be generated.</p>
     *
     * @param string $name the name element for the Job's JobKey
     * @param string $group the group element for the Job's JobKey
     *
     * @return JobBuilder
     *
     * @see JobKey
     * @see JobDetail#getKey()
     */
    public function withIdentity($name, $group = null)
    {
        $this->key = new Key($name, $group);

        return $this;
    }

    /**
     * Use a <code>JobKey</code> to identify the JobDetail.
     *
     * <p>If none of the 'withIdentity' methods are set on the JobBuilder,
     * then a random, unique JobKey will be generated.</p>
     *
     * @param Key $jobKey the Job's JobKey
     *
     * @return JobBuilder
     *
     * @see JobKey
     * @see JobDetail#getKey()
     */
    public function withIdentityKey(Key $jobKey)
    {
        $this->key = $jobKey;

        return $this;
    }

    /**
     * Set the given (human-meaningful) description of the Job.
     *
     * @param string $jobDescription the description for the Job
     *
     * @return JobBuilder
     *
     * @see JobDetail#getDescription()
     */
    public function withDescription($jobDescription)
    {
        $this->description = $jobDescription;

        return $this;
    }

    /**
     * Set the class which will be instantiated and executed when a
     * Trigger fires that is associated with this JobDetail.
     *
     * @param string $jobClazz a class implementing the Job interface.
     *
     * @return JobBuilder
     *
     * @see JobDetail#getJobClass()
     */
    public function ofType($jobClazz)
    {
        $this->jobClass = $jobClazz;

        return $this;
    }

//    /**
//     * Instructs the <code>Scheduler</code> whether or not the <code>Job</code>
//     * should be re-executed if a 'recovery' or 'fail-over' situation is
//     * encountered.
//     *
//     * <p>
//     * If not explicitly set, the default value is <code>false</code>.
//     * </p>
//     *
//     * @param bool $jobShouldRecover the desired setting
//     *
//     * @return JobBuilder
//     */
//    public function requestRecovery($jobShouldRecover = true)
//    {
//        $this->shouldRecover = $jobShouldRecover;
//
//        return $this;
//    }

    /**
     * Whether or not the <code>Job</code> should remain stored after it is
     * orphaned (no <code>{@link Trigger}s</code> point to it).
     *
     * <p>
     * If not explicitly set, the default value is <code>false</code>.
     * </p>
     *
     * @param bool $jobDurability the value to set for the durability property.
     *
     * @return JobBuilder
     *
     * @see JobDetail#isDurable()
     */
    public function storeDurably($jobDurability = true)
    {
        $this->durability = $jobDurability;

        return $this;
    }

    /**
     * Add all the data from the given {@link JobDataMap} to the
     * {@code JobDetail}'s {@code JobDataMap}.
     *
     * @param string $dataKey
     * @param mixed  $value
     *
     * @return JobBuilder
     *
     * @see JobDetail#getJobDataMap()
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
     * Add all the data from the given {@link JobDataMap} to the
     * {@code JobDetail}'s {@code JobDataMap}.
     *
     * @param array $newJobDataMap
     *
     * @return JobBuilder
     *
     * @see JobDetail#getJobDataMap()
     */
    public function usingJobDataArray(array $newJobDataMap)
    {
        foreach ($newJobDataMap as $key => $value) {
            $this->usingJobData($key, $value);
        }

        return $this;
    }

    /**
     * Replace the {@code JobDetail}'s {@link JobDataMap} with the
     * given {@code JobDataMap}.
     *
     * @param array $newJobDataMap
     *
     * @return JobBuilder
     *
     * @see JobDetail#getJobDataMap()
     */
    public function setJobData(array $newJobDataMap)
    {
        $this->jobDataMap = $newJobDataMap;

        return $this;
    }
}