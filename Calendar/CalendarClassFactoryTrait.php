<?php
namespace Quartz\Calendar;

use Quartz\Core\SchedulerException;

trait CalendarClassFactoryTrait
{
    /**
     * @param string $values
     *
     * @throws SchedulerException
     */
    private function getCalendarClass($values)
    {
        if (false == isset($values['instance'])) {
            throw new SchedulerException('Calendar has no "instance" field');
        }

        switch ($values['instance']) {
            case 'holiday':
                return HolidayCalendar::class;
            default:
                throw new SchedulerException(sprintf('Unknown calendar instance: "%s"', $values['instance']));
        }
    }
}
