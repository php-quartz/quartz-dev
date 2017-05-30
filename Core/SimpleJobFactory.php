<?php
namespace Quartz\Core;

class SimpleJobFactory implements JobFactory
{
    /**
     * {@inheritdoc}
     */
    public function newJob(JobDetail $jobDetail)
    {
        if (false == $jobClass = $jobDetail->getValue('jobClass')) {
            throw new SchedulerException('JobClass is not set');
        }

        return new $jobClass;
    }
}
