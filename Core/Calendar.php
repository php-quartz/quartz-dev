<?php
namespace Quartz\Core;

interface Calendar
{
    /**
     * <p>
     * Set a new base calendar or remove the existing one.
     * </p>
     *
     * @param Calendar $baseCalendar
     */
    public function setBaseCalendar(Calendar $baseCalendar);

    /**
     * <p>
     * Get the base calendar. Will be null, if not set.
     * </p>
     *
     * @return Calendar
     */
    public function getBaseCalendar();

    /**
     * <p>
     * Determine whether the given time (in milliseconds) is 'included' by the
     * Calendar.
     * </p>
     *
     * @param int $timeStamp
     *
     * @return bool
     */
    public function isTimeIncluded($timeStamp);

    /**
     * <p>
     * Determine the next time (in milliseconds) that is 'included' by the
     * Calendar after the given time.
     * </p>
     *
     * @param int $timeStamp
     *
     * @return int timestamp
     */
    public function getNextIncludedTime($timeStamp);

    /**
     * <p>
     * Return the description given to the <code>Calendar</code> instance by
     * its creator (if any).
     * </p>
     *
     * @return string|null if no description was set.
     */
    public function getDescription();

    /**
     * <p>
     * Set a description for the <code>Calendar</code> instance - may be
     * useful for remembering/displaying the purpose of the calendar, though
     * the description has no meaning to Quartz.
     * </p>
     *
     * @param string $description
     */
    public function setDescription($description);
}
