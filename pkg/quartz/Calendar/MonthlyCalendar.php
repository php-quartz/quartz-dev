<?php
namespace Quartz\Calendar;

use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;

/**
 * <p>
 * This implementation of the Calendar excludes a set of days of the month. You
 * may use it to exclude every first day of each month for example. But you may define
 * any day of a month.
 * </p>
 */
class MonthlyCalendar extends BaseCalendar
{
    const INSTANCE = 'monthly';

    /**
     * {@inheritdoc}
     */
    public function __construct(Calendar $baseCalendar = null, \DateTimeZone $timeZone = null)
    {
        parent::__construct(self::INSTANCE, $baseCalendar, $timeZone);
    }

    /**
     * <p>
     * Get the array which defines the exclude-value of each day of month.
     * Only the first 31 elements of the array are relevant, with the 1 index
     * element representing the first day of the month.
     * </p>
     */
    public function getDaysExcluded()
    {
        return $this->getValue('excludeDays', []);
    }

    /**
     * <p>
     * Return true, if day is defined to be excluded.
     * </p>
     *
     * @param int $day The day of the month (from 1 to 31) to check.
     *
     * @return bool
     */
    public function isDayExcluded($day)
    {
        DateBuilder::validateDayOfMonth($day);

        $days = $this->getValue('excludeDays', []);

        return in_array($day, $days, true);
    }

    /**
     * <p>
     * Redefine the array of days excluded. The array must non-null and of size
     * greater or equal to 31. The 1 index element represents the first day of
     * the month.
     * </p>
     *
     * @param array $days
     */
    public function setDaysExcluded(array $days)
    {
        foreach ($days as $day) {
            DateBuilder::validateDayOfMonth($day);
        }

        $this->setValue('excludeDays', $days);
    }

    /**
     * <p>
     * Redefine a certain day of the month to be excluded (true) or included
     * (false).
     * </p>
     *
     * @param int  $day The day of the month (from 1 to 31) to set.
     * @param bool $exclude
     */
    public function setDayExcluded($day, $exclude)
    {
        DateBuilder::validateDayOfMonth($day);

        $days = $this->getValue('excludeDays', []);

        if ($exclude) {
            if (false === array_search($day, $days, true)) {
                $days[] = $day;
                sort($days, SORT_NUMERIC);
            }
        } else {
            if (false !== $index = array_search($day, $days, true)) {
                unset($days[$index]);
                $days = array_values($days);
            }
        }

        $this->setValue('excludeDays', $days);
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
     * @param $timeStamp
     *
     * @return bool
     */
    public function isTimeIncluded($timeStamp)
    {
        // Test the base calendar first. Only if the base calendar not already
        // excludes the time/date, continue evaluating this calendar instance.
        if (false == parent::isTimeIncluded($timeStamp)) {
            return false;
        }

        $date = $this->createDateTime($timeStamp);
        $day = (int) $date->format('j');

        return false == $this->isDayExcluded($day);
    }

    /**
     * <p>
     * Check if all days are excluded. That is no day is included.
     * </p>
     */
    public function areAllDaysExcluded()
    {
        return count($this->getValue('excludeDays', [])) >= 31;
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
        if ($this->areAllDaysExcluded()) {
            return 0;
        }

        $baseTime = parent::getNextIncludedTime($timeStamp);
        if ($baseTime > 0 && $baseTime > $timeStamp) {
            $timeStamp = $baseTime;
        }

        // Get timestamp for 00:00:00
        $date = $this->getStartOfDayDateTime($timeStamp);
        $day = (int) $date->format('j');

        if (false == $this->isDayExcluded($day)) {
            return $timeStamp; // return the original value
        }

        while ($this->isDayExcluded($day)) {
            $date->add(new \DateInterval('P1D'));
            $day = (int) $date->format('j');
        }

        return (int) $date->format('U');
    }
}
