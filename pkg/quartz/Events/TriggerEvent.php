<?php
namespace Quartz\Events;

use Quartz\Core\Trigger;

class TriggerEvent extends Event
{
    /**
     * @var Trigger
     */
    private $trigger;

    /**
     * @param Trigger $trigger
     */
    public function __construct(Trigger $trigger)
    {
        $this->trigger = $trigger;
    }

    /**
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }
}
