<?php
namespace Quartz\Calendar;

use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;

/**
 * <p>
 * This implementation of the Calendar excludes a set of days of the week. You
 * may use it to exclude weekends for example. But you may define any day of
 * the week.  By default it excludes SATURDAY and SUNDAY.
 * </p>
 */
class WeeklyCalendar extends BaseCalendar
{
    const INSTANCE = 'weekly';

    /**
     * {@inheritdoc}
     */
    public function __construct(Calendar $baseCalendar = null, \DateTimeZone $timeZone = null)
    {
        parent::__construct(self::INSTANCE, $baseCalendar, $timeZone);

        $this->setDaysExcluded([
            DateBuilder::MONDAY => false,
            DateBuilder::TUESDAY => false,
            DateBuilder::WEDNESDAY => false,
            DateBuilder::THURSDAY => false,
            DateBuilder::FRIDAY => false,
            DateBuilder::SATURDAY => true,
            DateBuilder::SUNDAY => true,
        ]);
    }

    /**
     * <p>
     * Get the array with the week days
     * </p>
     */
    public function getDaysExcluded()
    {
        return $this->getValue('excludeDays');
    }

    /**
     * <p>
     * Return true, if wday (see Calendar.get()) is defined to be exluded. E. g.
     * saturday and sunday.
     * </p>
     *
     * @param int $wday
     *
     * @return bool
     */
    public function isDayExcluded($wday)
    {
        DateBuilder::validateDayOfWeek($wday);

        $excludeDays = $this->getValue('excludeDays');

        return $excludeDays[$wday];
    }

    /**
     * <p>
     * Redefine the array of days excluded. The array must of size greater or
     * equal 7. Calendar's constants like MONDAY should be used as
     * index. A value of true is regarded as: exclude it.
     * </p>
     *
     * @param array $weekDays
     */
    public function setDaysExcluded(array $weekDays)
    {
        if (7 !== count($weekDays)) {
            throw new \InvalidArgumentException(sprintf('Not all week days were set: "%s"', implode(',', array_keys($weekDays))));
        }

        foreach ($weekDays as $weekDay => $excluded) {
            DateBuilder::validateDayOfWeek($weekDay);

            if (false == is_bool($excluded)) {
                throw new \InvalidArgumentException('Array must contain only bool values. True - excludes day of week.');
            }
        }

        $this->setValue('excludeDays', $weekDays);
        $this->setValue('excludeAll', $this->areAllDaysExcluded());
    }

    /**
     * <p>
     * Redefine a certain day of the week to be excluded (true) or included
     * (false). Use Calendar's constants like MONDAY to determine the
     * wday.
     * </p>
     *
     * @param int  $wday
     * @param bool $exclude
     */
    public function setDayExcluded($wday, $exclude)
    {
        DateBuilder::validateDayOfWeek($wday);

        $weekDays = $this->getDaysExcluded();
        $weekDays[$wday] = (bool) $exclude;

        $this->setValue('excludeDays', $weekDays);
        $this->setValue('excludeAll', $this->areAllDaysExcluded());
    }

    /**
     * <p>
     * Check if all week days are excluded. That is no day is included.
     * </p>
     *
     * @return boolean
     */
    public function areAllDaysExcluded()
    {
        foreach ($this->getDaysExcluded() as $excluded) {
            if (false == $excluded) {
                return false;
            }
        }

        return true;
    }

    /**
     * <p>
     * Determine whether the given time (in milliseconds) is 'included' by the
     * Calendar.
     * </p>
     *
     * <p>
     * Note that this Calendar is only has full-day precision.
     * </p>
     *
     * @param int $timeStamp
     *
     * @return bool
     */
    public function isTimeIncluded($timeStamp)
    {
        if ($this->getValue('excludeAll')) {
            return false;
        }

        // Test the base calendar first. Only if the base calendar not already
        // excludes the time/date, continue evaluating this calendar instance.
        if (false == parent::isTimeIncluded($timeStamp)) {
            return false;
        }

        $date = \DateTime::createFromFormat('U', $timeStamp);

        if ($tz = $this->getTimeZone()) {
            $date->setTimezone($tz);
        }

        $wday = (int) $date->format('N');

        return false == $this->isDayExcluded($wday);
    }

    /**
     * <p>
     * Determine the next time (in milliseconds) that is 'included' by the
     * Calendar after the given time. Return the original value if timeStamp is
     * included. Return 0 if all days are excluded.
     * </p>
     *
     * <p>
     * Note that this Calendar is only has full-day precision.
     * </p>
     *
     * @param int $timeStamp
     *
     * @return int
     */
    public function getNextIncludedTime($timeStamp)
    {
        if ($this->getValue('excludeAll')) {
            return 0;
        }

        // Call base calendar implementation first
        $baseTime = parent::getNextIncludedTime($timeStamp);
        if ($baseTime > 0 && $baseTime > $timeStamp) {
            $timeStamp = $baseTime;
        }

        // Get timestamp for 00:00:00
        $date = \DateTime::createFromFormat('U', $timeStamp);

        if ($tz = $this->getTimeZone()) {
            $date->setTimezone($tz);
        }

        $date->setTime(0, 0, 0);

        $wday = (int) $date->format('N');

        if (false == $this->isDayExcluded($wday)) {
            return $timeStamp; // return the original value
        }

        while ($this->isDayExcluded($wday)) {
            $date->add(new \DateInterval('P1D'));
            $wday = (int) $date->format('N');
        }

        return (int) $date->format('U');
    }
}
