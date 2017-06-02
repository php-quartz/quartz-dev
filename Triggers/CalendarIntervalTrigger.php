<?php
namespace Quartz\Triggers;
use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;
use Quartz\Core\IntervalUnit;
use Quartz\Core\SchedulerException;

/**
 * A concrete <code>{@link Trigger}</code> that is used to fire a <code>{@link org.quartz.JobDetail}</code>
 * based upon repeating calendar time intervals.
 *
 * <p>The trigger will fire every N (see {@link #getRepeatInterval()} ) units of calendar time
 * (see {@link #getRepeatIntervalUnit()}) as specified in the trigger's definition.
 * This trigger can achieve schedules that are not possible with {@link SimpleTrigger} (e.g
 * because months are not a fixed number of seconds) or {@link CronTrigger} (e.g. because
 * "every 5 months" is not an even divisor of 12).</p>
 *
 * <p>If you use an interval unit of <code>MONTH</code> then care should be taken when setting
 * a <code>startTime</code> value that is on a day near the end of the month.  For example,
 * if you choose a start time that occurs on January 31st, and have a trigger with unit
 * <code>MONTH</code> and interval <code>1</code>, then the next fire time will be February 28th,
 * and the next time after that will be March 28th - and essentially each subsequent firing will
 * occur on the 28th of the month, even if a 31st day exists.  If you want a trigger that always
 * fires on the last day of the month - regardless of the number of days in the month,
 * you should use <code>CronTrigger</code>.</p>
 */
class CalendarIntervalTrigger extends AbstractTrigger
{
    const INSTANCE = 'calendar-interval';

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link CalendarIntervalTrigger}</code> wants to be
     * fired now by <code>Scheduler</code>.
     * </p>
     */
    const MISFIRE_INSTRUCTION_FIRE_ONCE_NOW = 1;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link CalendarIntervalTrigger}</code> wants to have it's
     * next-fire-time updated to the next time in the schedule after the
     * current time (taking into account any associated <code>{@link Calendar}</code>,
     * but it does not want to be fired now.
     * </p>
     */
    const MISFIRE_INSTRUCTION_DO_NOTHING = 2;

    public function __construct()
    {
        parent::__construct(self::INSTANCE);
    }

    /**
     * <p>Get the interval unit - the time unit on with the interval applies.</p>
     *
     * @return string
     */
    public function getRepeatIntervalUnit()
    {
        return $this->getValue('repeatIntervalUnit');
    }

    /**
     * <p>Get the interval unit - the time unit on with the interval applies.</p>
     *
     * @param string $intervalUnit
     */
    public function setRepeatIntervalUnit($intervalUnit)
    {
        DateBuilder::validateIntervalUnit($intervalUnit);

        $this->setValue('repeatIntervalUnit', $intervalUnit);
    }

    /**
     * <p>
     * Get the the time interval that will be added to the <code>DateIntervalTrigger</code>'s
     * fire time (in the set repeat interval unit) in order to calculate the time of the
     * next trigger repeat.
     * </p>
     *
     * @return int
     */
    public function getRepeatInterval()
    {
        return $this->getValue('repeatInterval');
    }

    /**
     * <p>
     * set the the time interval that will be added to the <code>DateIntervalTrigger</code>'s
     * fire time (in the set repeat interval unit) in order to calculate the time of the
     * next trigger repeat.
     * </p>
     *
     * @param int $repeatInterval
     *
     * @throws \InvalidArgumentException if repeatInterval is < 1
     */
    public function setRepeatInterval($repeatInterval)
    {
        if (false == is_int($repeatInterval) || $repeatInterval < 0) {
            throw new \InvalidArgumentException('Repeat interval must be >= 1');
        }

        $this->setValue('repeatInterval', $repeatInterval);
    }

    /**
     * If intervals are a day or greater, this property (set to true) will
     * cause the firing of the trigger to always occur at the same time of day,
     * (the time of day of the startTime) regardless of daylight saving time
     * transitions.  Default value is false.
     *
     * <p>
     * For example, without the property set, your trigger may have a start
     * time of 9:00 am on March 1st, and a repeat interval of 2 days.  But
     * after the daylight saving transition occurs, the trigger may start
     * firing at 8:00 am every other day.
     * </p>
     *
     * <p>
     * If however, the time of day does not exist on a given day to fire
     * (e.g. 2:00 am in the United States on the days of daylight saving
     * transition), the trigger will go ahead and fire one hour off on
     * that day, and then resume the normal hour on other days.  If
     * you wish for the trigger to never fire at the "wrong" hour, then
     * you should set the property skipDayIfHourDoesNotExist.
     * </p>
     *
     * @return bool
     */
    public function isPreserveHourOfDayAcrossDaylightSavings()
    {
        return (bool) $this->getValue('preserveHourOfDayAcrossDaylightSavings');
    }

    /**
     * @param bool $preserveHourOfDayAcrossDaylightSavings
     */
    public function setPreserveHourOfDayAcrossDaylightSavings($preserveHourOfDayAcrossDaylightSavings)
    {
        $this->setValue('preserveHourOfDayAcrossDaylightSavings', (bool) $preserveHourOfDayAcrossDaylightSavings);
    }

    /**
     * If intervals are a day or greater, and
     * preserveHourOfDayAcrossDaylightSavings property is set to true, and the
     * hour of the day does not exist on a given day for which the trigger
     * would fire, the day will be skipped and the trigger advanced a second
     * interval if this property is set to true.  Defaults to false.
     *
     * <p>
     * <b>CAUTION!</b>  If you enable this property, and your hour of day happens
     * to be that of daylight savings transition (e.g. 2:00 am in the United
     * States) and the trigger's interval would have had the trigger fire on
     * that day, then you may actually completely miss a firing on the day of
     * transition if that hour of day does not exist on that day!  In such a
     * case the next fire time of the trigger will be computed as double (if
     * the interval is 2 days, then a span of 4 days between firings will
     * occur).
     * </p>
     *
     * @return bool
     */
    public function isSkipDayIfHourDoesNotExist()
    {
        return (bool) $this->getValue('skipDayIfHourDoesNotExist');
    }

    /**
     * @param bool $skipDayIfHourDoesNotExist
     */
    public function setSkipDayIfHourDoesNotExist($skipDayIfHourDoesNotExist)
    {
        $this->setValue('skipDayIfHourDoesNotExist', (bool) $skipDayIfHourDoesNotExist);
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        parent::validate();

        if ($this->getRepeatInterval() < 1) {
            throw new SchedulerException('Repeat Interval cannot be zero.');
        }
    }

    /**
     * @param int $misfireInstruction
     *
     * @return bool
     */
    protected function validateMisfireInstruction($misfireInstruction)
    {
        if ($misfireInstruction < self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY) {
            return false;
        }

        return $misfireInstruction <= self::MISFIRE_INSTRUCTION_DO_NOTHING;
    }

    /**
     * {@inheritdoc}
     */
    public function getFireTimeAfter(\DateTime $afterTime = null)
    {
        return $this->doGetFireTimeAfter($afterTime, false);
    }

    /**
     * @param \DateTime|null $afterTime
     * @param bool           $ignoreEndTime
     *
     * @return \DateTime|null
     */
    public function doGetFireTimeAfter(\DateTime $afterTime = null, $ignoreEndTime)
    {
        if (null == $afterTime) {
            $afterTime = new \DateTime();
        }

        $startSec = (int) $this->getStartTime()->format('U');
        $afterSec = (int) $afterTime->format('U');
        $endSec = $this->getEndTime() ? (int) $this->getEndTime()->format('U') : PHP_INT_MAX;

        if (false == $ignoreEndTime && ($endSec <= $afterSec)) {
            return null;
        }

        if ($afterSec < $startSec) {
            return clone $this->getStartTime();
        }

        $secondsAfterStart = 1 + ($afterSec - $startSec);

        $time = clone $this->getStartTime();
        $repeatInterval = $this->getRepeatInterval();
        $intervalUnit = $this->getRepeatIntervalUnit();

        if ($intervalUnit === IntervalUnit::SECOND) {
            $jumpCount = (int) ($secondsAfterStart / $repeatInterval);

            if (($secondsAfterStart % $repeatInterval) != 0) {
                $jumpCount++;
            }

            $addSec = $repeatInterval * $jumpCount;
            $time->add(new \DateInterval(sprintf('PT%dS', $addSec)));
        } elseif ($intervalUnit === IntervalUnit::MINUTE) {
            $jumpCount = (int) ($secondsAfterStart / ($repeatInterval * 60));

            if (($secondsAfterStart % ($repeatInterval * 60)) != 0) {
                $jumpCount++;
            }

            $addMin = $repeatInterval * $jumpCount;
            $time->add(new \DateInterval(sprintf('PT%dM', $addMin)));
        } elseif ($intervalUnit === IntervalUnit::HOUR) {
            $jumpCount = (int) ($secondsAfterStart / ($repeatInterval * 60 * 60));

            if (($secondsAfterStart % ($repeatInterval * 60 * 60)) != 0) {
                $jumpCount++;
            }

            $addHours = $repeatInterval * $jumpCount;
            $time->add(new \DateInterval(sprintf('PT%dH', $addHours)));
        } else { // intervals a day or greater ...
            $initialHourOfDay = $time->format('G');

            if ($intervalUnit === IntervalUnit::DAY) {
                // Because intervals greater than an hour have an non-fixed number
                // of seconds in them (due to daylight savings, variation number of
                // days in each month, leap year, etc. ) we can't jump forward an
                // exact number of seconds to calculate the fire time as we can
                // with the second, minute and hour intervals.   But, rather
                // than slowly crawling our way there by iteratively adding the
                // increment to the start time until we reach the "after time",
                // we can first make a big leap most of the way there...

                $jumpCount = (int) ($secondsAfterStart / ($repeatInterval * 24 * 60 * 60));
                // if we need to make a big jump, jump most of the way there,
                // but not all the way because in some cases we may over-shoot or under-shoot
                if ($jumpCount > 20) {
                    if ($jumpCount < 50) {
                        $jumpCount = (int) ($jumpCount * 0.8);
                    } elseif ($jumpCount < 500) {
                        $jumpCount = (int) ($jumpCount * 0.9);
                    } else {
                        $jumpCount = (int) ($jumpCount * 0.95);
                    }

                    $addDays = $repeatInterval * $jumpCount;
                    $time->add(new \DateInterval(sprintf('P%dD', $addDays)));
                }

                while ($time <= $afterTime && ((int) $time->format('Y')) < DateBuilder::MAX_YEAR() ) {
                    $time->add(new \DateInterval(sprintf('P%dD', $repeatInterval)));
                }

                while ($this->daylightSavingHourShiftOccurredAndAdvanceNeeded($time, $initialHourOfDay, $afterTime) &&
                    ((int) $time->format('Y')) < DateBuilder::MAX_YEAR()) {
                    $time->add(new \DateInterval(sprintf('P%dD', $repeatInterval)));
                }
            } elseif ($intervalUnit === IntervalUnit::WEEK) {
                // Because intervals greater than an hour have an non-fixed number
                // of seconds in them (due to daylight savings, variation number of
                // days in each month, leap year, etc. ) we can't jump forward an
                // exact number of seconds to calculate the fire time as we can
                // with the second, minute and hour intervals.   But, rather
                // than slowly crawling our way there by iteratively adding the
                // increment to the start time until we reach the "after time",
                // we can first make a big leap most of the way there...

                $jumpCount = (int) ($secondsAfterStart / ($repeatInterval * 7 * 24 * 60 * 60));
                // if we need to make a big jump, jump most of the way there,
                // but not all the way because in some cases we may over-shoot or under-shoot

                if ($jumpCount > 20) {
                    if ($jumpCount < 50) {
                        $jumpCount = (int) ($jumpCount * 0.8);
                    } elseif ($jumpCount < 500) {
                        $jumpCount = (int) ($jumpCount * 0.9);
                    } else {
                        $jumpCount = (int) ($jumpCount * 0.95);
                    }

                    $addWeeks = $repeatInterval * $jumpCount;
                    $time->add(new \DateInterval(sprintf('P%dW', $addWeeks)));
                }

                while ($time <= $afterTime && ((int) $time->format('Y')) < DateBuilder::MAX_YEAR() ) {
                    $time->add(new \DateInterval(sprintf('P%dW', $repeatInterval)));
                }

                while ($this->daylightSavingHourShiftOccurredAndAdvanceNeeded($time, $initialHourOfDay, $afterTime) &&
                    ((int) $time->format('Y')) < DateBuilder::MAX_YEAR()) {
                    $time->add(new \DateInterval(sprintf('P%dW', $repeatInterval)));
                }
            } elseif ($repeatInterval === IntervalUnit::MONTH) {
                // because of the large variation in size of months, and
                // because months are already large blocks of time, we will
                // just advance via brute-force iteration.

                while ($time <= $afterTime && ((int) $time->format('Y')) < DateBuilder::MAX_YEAR() ) {
                    $time->add(new \DateInterval(sprintf('P%dM', $repeatInterval)));
                }

                while ($this->daylightSavingHourShiftOccurredAndAdvanceNeeded($time, $initialHourOfDay, $afterTime) &&
                    ((int) $time->format('Y')) < DateBuilder::MAX_YEAR()) {
                    $time->add(new \DateInterval(sprintf('P%dM', $repeatInterval)));
                }
            } elseif ($repeatInterval === IntervalUnit::YEAR) {

                while ($time <= $afterTime && ((int) $time->format('Y')) < DateBuilder::MAX_YEAR() ) {
                    $time->add(new \DateInterval(sprintf('P%dY', $repeatInterval)));
                }

                while ($this->daylightSavingHourShiftOccurredAndAdvanceNeeded($time, $initialHourOfDay, $afterTime) &&
                    ((int) $time->format('Y')) < DateBuilder::MAX_YEAR()) {
                    $time->add(new \DateInterval(sprintf('P%dY', $repeatInterval)));
                }
            }
        }

        if (false == $ignoreEndTime && ($endSec <= ((int) $time->format('U')))) {
            return null;
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getFinalFireTime()
    {
        if (null == $this->getEndTime()) {
            return null;
        }

        $fTime = clone $this->getEndTime();
        $fTime->sub(new \DateInterval('PT1S'));
        // find the next fire time after that
        $fTime = $this->doGetFireTimeAfter($fTime, true);

        if ($fTime == $this->getEndTime()) {
            return $fTime;
        }

        // otherwise we have to back up one interval from the fire time after the end time

        $intervalUnit = $this->getRepeatIntervalUnit();

        if ($intervalUnit === IntervalUnit::SECOND) {
            $fTime->sub(new \DateInterval(sprintf('PT%dS', $this->getRepeatInterval())));
        } elseif ($intervalUnit === IntervalUnit::MINUTE) {
            $fTime->sub(new \DateInterval(sprintf('PT%dM', $this->getRepeatInterval())));
        } elseif ($intervalUnit === IntervalUnit::HOUR) {
            $fTime->sub(new \DateInterval(sprintf('PT%dH', $this->getRepeatInterval())));
        } elseif ($intervalUnit === IntervalUnit::DAY) {
            $fTime->sub(new \DateInterval(sprintf('P%dD', $this->getRepeatInterval())));
        } elseif ($intervalUnit === IntervalUnit::WEEK) {
            $fTime->sub(new \DateInterval(sprintf('P%dW', $this->getRepeatInterval())));
        } elseif ($intervalUnit === IntervalUnit::MONTH) {
            $fTime->sub(new \DateInterval(sprintf('P%dM', $this->getRepeatInterval())));
        } elseif ($intervalUnit === IntervalUnit::YEAR) {
            $fTime->sub(new \DateInterval(sprintf('P%dY', $this->getRepeatInterval())));
        }

        return $fTime;
    }

    /**
     * {@inheritdoc}
     */
    public function updateAfterMisfire(Calendar $cal = null)
    {
        $instr = $this->getMisfireInstruction();

        if ($instr === self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY) {
            return;
        }

        if ($instr === self::MISFIRE_INSTRUCTION_SMART_POLICY) {
            $instr = self::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW;
        }

        if ($instr === self::MISFIRE_INSTRUCTION_DO_NOTHING) {
            $newFireTime = $this->getFireTimeAfter(new \DateTime());

            while ($newFireTime && $cal && false == $cal->isTimeIncluded(((int) $newFireTime->format('U')))) {
                $newFireTime = $this->getFireTimeAfter($newFireTime);
            }

            $this->setNextFireTime($newFireTime);
        } elseif ($instr === self::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW) {
            // fire once now...
            $this->setNextFireTime(new \DateTime());
            // the new fire time afterward will magically preserve the original
            // time of day for firing for day/week/month interval triggers,
            // because of the way getFireTimeAfter() works - in its always restarting
            // computation from the start time.
        }
    }

    /**
     * @param \DateTime $newTime
     * @param int       $initialHourOfDay
     * @param \DateTime $afterTime
     *
     * @return bool
     */
    private function daylightSavingHourShiftOccurredAndAdvanceNeeded(\DateTime $newTime, $initialHourOfDay, \DateTime $afterTime)
    {
        if ($this->isPreserveHourOfDayAcrossDaylightSavings() && ((int) $newTime->format('G')) != $initialHourOfDay) {
            // construct same date but with new hours value
            // DateTime::setTime does not handle day light saving hour
            // we have to create new date to be sure date is correct
            $timeWithoutHour = $newTime->format('e Y m d i s');
            $newTime = \DateTime::createFromFormat('e Y m d i s G', $timeWithoutHour.' '.$initialHourOfDay);

            if (((int) $newTime->format('G')) != $initialHourOfDay) {
                return $this->isSkipDayIfHourDoesNotExist();
            } else {
                return $newTime <= $afterTime;
            }
        }

        return false;
    }
}
