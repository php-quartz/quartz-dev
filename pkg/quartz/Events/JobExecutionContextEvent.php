<?php
namespace Quartz\Events;

use Quartz\Core\JobExecutionContext;

class JobExecutionContextEvent extends Event
{
    /**
     * @var JobExecutionContext
     */
    private $context;

    /**
     * @var bool
     */
    private $vetoed;

    /**
     * @param JobExecutionContext $context
     */
    public function __construct(JobExecutionContext $context)
    {
        $this->context = $context;
        $this->vetoed = false;
    }

    /**
     * @return JobExecutionContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return boolean
     */
    public function isVetoed()
    {
        return $this->vetoed;
    }

    /**
     * @param boolean $vetoed
     */
    public function setVetoed($vetoed)
    {
        $this->vetoed = (bool) $vetoed;
    }
}
