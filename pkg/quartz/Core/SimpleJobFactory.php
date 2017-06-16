<?php
namespace Quartz\Core;

class SimpleJobFactory implements JobFactory
{
    /**
     * @var Job[]
     */
    private $jobs;

    /**
     * [
     *   'jobClass' => Job,
     * ]
     *
     * @param Job[] $jobs
     */
    public function __construct(array $jobs = [])
    {
        $this->jobs = $jobs;
    }

    /**
     * {@inheritdoc}
     */
    public function newJob(JobDetail $jobDetail)
    {
        $job = null;
        if (isset($this->jobs[$jobDetail->getJobClass()])) {
            $job = $this->jobs[$jobDetail->getJobClass()];
        } elseif (class_exists($jobDetail->getJobClass())) {
            $class = $jobDetail->getJobClass();
            $job = new $class;
        }

        if (false == $job instanceof Job) {
            throw new SchedulerException(sprintf('Required instance of "%s", but got: "%s"',
                Job::class, is_object($job) ? get_class($job) : gettype($job)));
        }

        return $job;
    }
}
