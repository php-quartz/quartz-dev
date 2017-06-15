<?php
namespace Quartz\Events;

class TickEvent extends Event
{
    /**
     * @var bool
     */
    private $interrupted;

    public function __construct()
    {
        $this->interrupted = false;
    }

    /**
     * @return boolean
     */
    public function isInterrupted()
    {
        return $this->interrupted;
    }

    /**
     * @param boolean $interrupt
     */
    public function setInterrupted($interrupt)
    {
        $this->interrupted = (bool) $interrupt;
    }
}
