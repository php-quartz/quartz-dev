<?php
namespace Quartz\Events;

use Quartz\Core\SchedulerException;

class ErrorEvent extends Event
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var int
     */
    private $errorCount;

    /**
     * @var bool
     */
    private $interrupted;

    /**
     * @param string             $message
     * @param int                $errorCount
     * @param SchedulerException $exception
     */
    public function __construct($message, $errorCount, SchedulerException $exception = null)
    {
        $this->message = $message;
        $this->errorCount = $errorCount;
        $this->exception = $exception;
        $this->interrupted = false;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return int
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * @return boolean
     */
    public function isInterrupted()
    {
        return $this->interrupted;
    }

    /**
     * @param boolean $interrupted
     */
    public function setInterrupted($interrupted)
    {
        $this->interrupted = (bool) $interrupted;
    }
}
