<?php
namespace Quartz\Events;

use Quartz\Core\JobDetail;

class JobDetailEvent extends Event
{
    /**
     * @var JobDetail
     */
    private $jobDetail;

    /**
     * @param JobDetail $jobDetail
     */
    public function __construct(JobDetail $jobDetail)
    {
        $this->jobDetail = $jobDetail;
    }

    /**
     * @return JobDetail
     */
    public function getJobDetail()
    {
        return $this->jobDetail;
    }
}
