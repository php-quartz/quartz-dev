<?php
namespace Quartz\Calendar;

use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;

/**
 * This implementation of the Calendar excludes (or includes - see below) a
 * specified time range each day. For example, you could use this calendar to
 * exclude business hours (8AM - 5PM) every day. Each <CODE>DailyCalendar</CODE>
 * only allows a single time range to be specified, and that time range may not
 * cross daily boundaries (i.e. you cannot specify a time range from 8PM - 5AM).
 * If the property <CODE>invertTimeRange</CODE> is <CODE>false</CODE> (default),
 * the time range defines a range of times in which triggers are not allowed to
 * fire. If <CODE>invertTimeRange</CODE> is <CODE>true</CODE>, the time range
 * is inverted &ndash; that is, all times <I>outside</I> the defined time range
 * are excluded.
 * <P>
 * Note when using <CODE>DailyCalendar</CODE>, it behaves on the same principals
 * as, for example, {@link WeeklyCalendar}. <CODE>WeeklyCalendar</CODE> defines a set of days that are
 * excluded <I>every week</I>. Likewise, <CODE>DailyCalendar</CODE> defines a
 * set of times that are excluded <I>every day</I>.
 */
class DailyCalendar extends BaseCalendar
{
    const INSTANCE = 'daily';

    /**
     * {@inheritdoc}
     */
    public function __construct(Calendar $baseCalendar = null, \DateTimeZone $timeZone = null)
    {
        parent::__construct(self::INSTANCE, $baseCalendar, $timeZone);

        $this->setInvertTimeRange(false);
    }

    /**
     * {@inheritdoc}
     */
    public function isTimeIncluded($timeStamp)
    {
        // Test the base calendar first. Only if the base calendar not already
        // excludes the time/date, continue evaluating this calendar instance.
        if (false == parent::isTimeIncluded($timeStamp)) {
            return false;
        }

        $startOfDay = (int) $this->getStartOfDayDateTime($timeStamp)->format('U');
        $endOfDay = (int) $this->getEndOfDayDateTime($timeStamp)->format('U');

        $startRange = $this->getTimeRangeStartingTimeInSeconds($timeStamp);
        $endRange = $this->getTimeRangeEndingTimeInSeconds($timeStamp);

        if (false == $this->getInvertTimeRange()) {
            return (($timeStamp > $startOfDay && $timeStamp < $startRange) ||
                ($timeStamp > $endRange && $timeStamp < $endOfDay));
        } else {
            return ($timeStamp >= $startRange && $timeStamp <= $endRange);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNextIncludedTime($timeStamp)
    {
        $nextIncludedTime = $timeStamp + 1;

        while (false == $this->isTimeIncluded($nextIncludedTime)) {
            if (false == $this->getInvertTimeRange()) {
                //If the time is in a range excluded by this calendar, we can
                // move to the end of the excluded time range and continue
                // testing from there. Otherwise, if nextIncludedTime is
                // excluded by the baseCalendar, ask it the next time it
                // includes and begin testing from there. Failing this, add one
                // millisecond and continue testing.
                if ($nextIncludedTime >= $this->getTimeRangeStartingTimeInSeconds($nextIncludedTime) &&
                    $nextIncludedTime <= $this->getTimeRangeEndingTimeInSeconds($nextIncludedTime)) {

                    $nextIncludedTime = $this->getTimeRangeEndingTimeInSeconds($nextIncludedTime) + 1;
                } elseif ($this->getBaseCalendar() && false == $this->getBaseCalendar()->isTimeIncluded($nextIncludedTime)) {
                    $nextIncludedTime = $this->getBaseCalendar()->getNextIncludedTime($nextIncludedTime);
                } else {
                    $nextIncludedTime++;
                }
            } else {
                //If the time is in a range excluded by this calendar, we can
                // move to the end of the excluded time range and continue
                // testing from there. Otherwise, if nextIncludedTime is
                // excluded by the baseCalendar, ask it the next time it
                // includes and begin testing from there. Failing this, add one
                // second and continue testing.
                if ($nextIncludedTime < $this->getTimeRangeStartingTimeInSeconds($nextIncludedTime)) {
                    $nextIncludedTime = $this->getTimeRangeStartingTimeInSeconds($nextIncludedTime);
                } elseif ($nextIncludedTime > $this->getTimeRangeEndingTimeInSeconds($nextIncludedTime)) {
                    //(move to start of next day)
                    $nextIncludedTime = (int) $this->getEndOfDayDateTime($nextIncludedTime)->format('U');
                    $nextIncludedTime++;
                } elseif ($this->getBaseCalendar() && false == $this->getBaseCalendar()->isTimeIncluded($nextIncludedTime)) {
                    $nextIncludedTime = $this->getBaseCalendar()->getNextIncludedTime($nextIncludedTime);
                } else {
                    $nextIncludedTime++;
                }
            }
        }

        return $nextIncludedTime;
    }

    /**
     * Returns the start time of the time range (in seconds) of the day
     *
     * @param int $timeStamp a time containing the desired date for the starting time of the time range.
     *
     * @return int a date/time (in seconds) representing the start time of the time range for the specified date.
     */
    public function getTimeRangeStartingTimeInSeconds($timeStamp)
    {
        $date = $this->createDateTime($timeStamp);
        $date->setTime($this->getStartHour(), $this->getStartMinute(), $this->getStartSecond());

        return (int) $date->format('U');
    }

    /**
     * Returns the end time of the time range (in seconds) of the day
     *
     * @param int $timeStamp a time containing the desired date for the ending time of the time range.
     *
     * @return int a date/time (in seconds) representing the end time of the time range for the specified date.
     */
    public function getTimeRangeEndingTimeInSeconds($timeStamp)
    {
        $date = $this->createDateTime($timeStamp);
        $date->setTime($this->getEndHour(), $this->getEndMinute(), $this->getEndSecond());

        return (int) $date->format('U');
    }

    /**
     * Sets the time range for the <CODE>DailyCalendar</CODE> to the times
     * represented in the specified values.
     *
     * @param int $startHour the hour of the start of the time range
     * @param int $startMinute the minute of the start of the time range
     * @param int $startSecond the second of the start of the time range
     * @param int $endHour the hour of the end of the time range
     * @param int $endMinute the minute of the end of the time range
     * @param int $endSecond the second of the end of the time range
     */
    public function setTimeRange($startHour, $startMinute, $startSecond,
                                 $endHour, $endMinute, $endSecond) {
        $startHour = (int) $startHour;
        $startMinute = (int) $startMinute;
        $startSecond = (int) $startSecond;

        $endHour = (int) $endHour;
        $endMinute = (int) $endMinute;
        $endSecond = (int) $endSecond;

        DateBuilder::validateHour($startHour);
        DateBuilder::validateMinute($startMinute);
        DateBuilder::validateSecond($startSecond);

        DateBuilder::validateHour($endHour);
        DateBuilder::validateMinute($endMinute);
        DateBuilder::validateSecond($endSecond);

        $startDate = $this->createDateTime(time());
        $startDate->setTime($startHour, $startMinute, $startSecond);

        $endDate = $this->createDateTime(time());
        $endDate->setTime($endHour, $endMinute, $endSecond);

        if ($startDate >= $endDate) {
            throw new \InvalidArgumentException(sprintf('Invalid time range: %d:%d:%d - %d:%d:%d',
                $startHour, $startMinute, $startSecond, $endHour, $endMinute, $endSecond));
        }

        $this->setStartHour($startHour);
        $this->setStartMinute($startMinute);
        $this->setStartSecond($startSecond);

        $this->setEndHour($endHour);
        $this->setEndMinute($endMinute);
        $this->setEndSecond($endSecond);
    }

    /**
     * Indicates whether the time range represents an inverted time range (see
     * class description).
     *
     * @return bool a boolean indicating whether the time range is inverted
     */
    public function getInvertTimeRange()
    {
        return $this->getValue('invertTimeRange');
    }

    /**
     * Indicates whether the time range represents an inverted time range (see
     * class description).
     *
     * @param bool $invert the new value for the <CODE>invertTimeRange</CODE> flag.
     */
    public function setInvertTimeRange($invert)
    {
        $this->setValue('invertTimeRange', (bool) $invert);
    }

    private function setStartHour($startHour)
    {
        $this->setValue('startHour', $startHour);
    }

    private function getStartHour()
    {
        return $this->getValue('startHour');
    }

    private function setStartMinute($startMinute)
    {
        $this->setValue('startMinute', $startMinute);
    }

    private function getStartMinute()
    {
        return $this->getValue('startMinute');
    }

    private function setStartSecond($startSecond)
    {
        $this->setValue('startSecond', $startSecond);
    }

    private function getStartSecond()
    {
        return $this->getValue('startSecond');
    }

    private function setEndHour($endHour)
    {
        $this->setValue('endHour', $endHour);
    }

    private function getEndHour()
    {
        return $this->getValue('endHour');
    }

    private function setEndMinute($endMinute)
    {
        $this->setValue('endMinute', $endMinute);
    }

    private function getEndMinute()
    {
        return $this->getValue('endMinute');
    }

    private function setEndSecond($endSecond)
    {
        $this->setValue('endSecond', $endSecond);
    }

    private function getEndSecond()
    {
        return $this->getValue('endSecond');
    }
}
