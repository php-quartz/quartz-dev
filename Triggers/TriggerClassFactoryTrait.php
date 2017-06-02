<?php
namespace Quartz\Triggers;

use Quartz\Core\SchedulerException;

trait TriggerClassFactoryTrait
{
    /**
     * @param string $values
     *
     * @throws SchedulerException
     */
    private function getTriggerClass($values)
    {
        if (false == isset($values['instance'])) {
            throw new SchedulerException('Trigger has no "instance" field');
        }

        switch ($values['instance']) {
            case SimpleTrigger::INSTANCE:
                return SimpleTrigger::class;
            case CronTrigger::INSTANCE:
                return CronTrigger::class;
            case CalendarIntervalTrigger::INSTANCE:
                return CalendarIntervalTrigger::class;
            default:
                throw new SchedulerException(sprintf('Unknown trigger instance: "%s"', $values['instance']));
        }
    }
}
