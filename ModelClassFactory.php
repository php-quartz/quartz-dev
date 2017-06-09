<?php
namespace Quartz;

use Quartz\Calendar\HolidayCalendar;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\CalendarIntervalTrigger;
use Quartz\Triggers\CronTrigger;
use Quartz\Triggers\DailyTimeIntervalTrigger;
use Quartz\Triggers\SimpleTrigger;

class ModelClassFactory
{
    /**
     * @param array $values
     *
     * @return string
     *
     * @throws SchedulerException
     */
    public static function getClass($values)
    {
        if (false == isset($values['instance'])) {
            throw new SchedulerException('Values has no "instance" field');
        }

        switch ($values['instance']) {
            // triggers
            case SimpleTrigger::INSTANCE:
                return SimpleTrigger::class;
            case CronTrigger::INSTANCE:
                return CronTrigger::class;
            case CalendarIntervalTrigger::INSTANCE:
                return CalendarIntervalTrigger::class;
            case DailyTimeIntervalTrigger::INSTANCE:
                return DailyTimeIntervalTrigger::class;
            // job
            case JobDetail::INSTANCE:
                return JobDetail::class;
            // calendars
            case HolidayCalendar::INSTANCE:
                return HolidayCalendar::class;
            // key
            case Key::INSTANCE:
                return Key::class;
            default:
                throw new SchedulerException(sprintf('Unknown values instance: "%s"', $values['instance']));
        }
    }
}
