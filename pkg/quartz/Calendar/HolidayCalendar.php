<?php
namespace Quartz\Calendar;

use Quartz\Core\Calendar;

/**
 * <p>
 * This implementation of the Calendar stores a list of holidays (full days
 * that are excluded from scheduling).
 * </p>
 *
 * <p>
 * The implementation DOES take the year into consideration, so if you want to
 * exclude July 4th for the next 10 years, you need to add 10 entries to the
 * exclude list.
 * </p>
 */
class HolidayCalendar extends BaseCalendar
{
    const INSTANCE = 'holiday';

    /**
     * {@inheritdoc}
     */
    public function __construct(Calendar $baseCalendar = null, \DateTimeZone $timeZone = null)
    {
        parent::__construct(self::INSTANCE, $baseCalendar, $timeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function isTimeIncluded($timeStamp)
    {
        if (parent::isTimeIncluded($timeStamp) == false) {
            return false;
        }

        $lookFor = \DateTime::createFromFormat('U', $timeStamp);

        if ($tz = $this->getTimeZone()) {
            $lookFor->setTimezone($tz);
        }

        $lookFor->setTime(0, 0, 0);

        $dates = $this->getValue('excludedDates');

        return false == isset($dates[$lookFor->format('U')]);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextIncludedTime($timeStamp)
    {
        // Call base calendar implementation first
        $baseTime = parent::getNextIncludedTime($timeStamp);
        if ($baseTime > 0 && $baseTime > $timeStamp) {
            $timeStamp = $baseTime;
        }

        // Get timestamp for 00:00:00
        $day = \DateTime::createFromFormat('U', $timeStamp);

        if ($tz = $this->getTimeZone()) {
            $day->setTimezone($tz);
        }

        $day->setTime(0, 0, 0);

        while (false == $this->isTimeIncluded((int) $day->format('U'))) {
            $day->add(new \DateInterval('P1D'));
        }

        return (int) $day->format('U');
    }

    /**
     * <p>
     * Add the given Date to the list of excluded days. Only the month, day and
     * year of the returned dates are significant.
     * </p>
     *
     * @param \DateTime $excludedDate
     */
    public function addExcludedDate(\DateTime $excludedDate)
    {
        $clone = clone $excludedDate;
        $clone->setTime(0, 0, 0);

        $dates = $this->getValue('excludedDates');
        $dates[$clone->format('U')] = true;

        $this->setValue('excludedDates', $dates);
    }

    /**
     * @param \DateTime $dateToRemove
     */
    public function removeExcludedDate(\DateTime $dateToRemove)
    {
        $clone = clone $dateToRemove;
        $clone->setTime(0, 0 ,0);

        $dates = $this->getValue('excludedDates');
        unset($dates[$clone->format('U')]);

        $this->setValue('excludedDates', $dates);
    }

    /**
     * <p>
     * Returns a list of Dates representing the excluded
     * days. Only the month, day and year of the returned dates are
     * significant.
     * </p>
     */
    public function getExcludedDates()
    {
        $dates = [];
        foreach ($this->getValue('excludedDates') as $date => $v) {
            $d = \DateTime::createFromFormat('U', $date);

            if ($tz = $this->getTimeZone()) {
                $d->setTimezone($tz);
            }

            $dates[] = $d;
        }

        return $dates;
    }
}
