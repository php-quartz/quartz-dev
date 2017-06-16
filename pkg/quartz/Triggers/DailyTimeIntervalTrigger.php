<?php
namespace Quartz\Triggers;

use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;
use Quartz\Core\IntervalUnit;
use Quartz\Core\SchedulerException;

/**
 * A concrete implementation of DailyTimeIntervalTrigger that is used to fire a <code>{@link org.quartz.JobDetail}</code>
 * based upon daily repeating time intervals.
 *
 * <p>The trigger will fire every N (see {@link #setRepeatInterval(int)} ) seconds, minutes or hours
 * (see {@link #setRepeatIntervalUnit(org.quartz.DateBuilder.IntervalUnit)}) during a given time window on specified days of the week.</p>
 *
 * <p>For example#1, a trigger can be set to fire every 72 minutes between 8:00 and 11:00 everyday. It's fire times would
 * be 8:00, 9:12, 10:24, then next day would repeat: 8:00, 9:12, 10:24 again.</p>
 *
 * <p>For example#2, a trigger can be set to fire every 23 minutes between 9:20 and 16:47 Monday through Friday.</p>
 *
 * <p>On each day, the starting fire time is reset to startTimeOfDay value, and then it will add repeatInterval value to it until
 * the endTimeOfDay is reached. If you set daysOfWeek values, then fire time will only occur during those week days period. Again,
 * remember this trigger will reset fire time each day with startTimeOfDay, regardless of your interval or endTimeOfDay!</p>
 *
 * <p>The default values for fields if not set are: startTimeOfDay defaults to 00:00:00, the endTimeOfDay default to 23:59:59,
 * and daysOfWeek is default to every day. The startTime default to current time-stamp now, while endTime has not value.</p>
 *
 * <p>If startTime is before startTimeOfDay, then startTimeOfDay will be used and startTime has no affect other than to specify
 * the first day of firing. Else if startTime is
 * after startTimeOfDay, then the first fire time for that day will be the next interval after the startTime. For example, if
 * you set startingTimeOfDay=9am, endingTimeOfDay=11am, interval=15 mins, and startTime=9:33am, then the next fire time will
 * be 9:45pm. Note also that if you do not set startTime value, the trigger builder will default to current time, and current time
 * maybe before or after the startTimeOfDay! So be aware how you set your startTime.</p>
 *
 * <p>This trigger also supports "repeatCount" feature to end the trigger fire time after
 * a certain number of count is reached. Just as the SimpleTrigger, setting repeatCount=0
 * means trigger will fire once only! Setting any positive count then the trigger will repeat
 * count + 1 times. Unlike SimpleTrigger, the default value of repeatCount of this trigger
 * is set to REPEAT_INDEFINITELY instead of 0 though.
 */
class DailyTimeIntervalTrigger extends AbstractTrigger
{
    const INSTANCE = 'daily-time-interval';

    const ALL_DAYS_OF_THE_WEEK = [
        DateBuilder::MONDAY,
        DateBuilder::TUESDAY,
        DateBuilder::WEDNESDAY,
        DateBuilder::THURSDAY,
        DateBuilder::FRIDAY,
        DateBuilder::SATURDAY,
        DateBuilder::SUNDAY,
    ];

    /**
     * <p>
     * Used to indicate the 'repeat count' of the trigger is indefinite. Or in
     * other words, the trigger should repeat continually until the trigger's
     * ending timestamp.
     * </p>
     */
    const REPEAT_INDEFINITELY = -1;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link DailyTimeIntervalTrigger}</code> wants to be
     * fired now by <code>Scheduler</code>.
     * </p>
     */
    const MISFIRE_INSTRUCTION_FIRE_ONCE_NOW = 1;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link DailyTimeIntervalTrigger}</code> wants to have it's
     * next-fire-time updated to the next time in the schedule after the
     * current time (taking into account any associated <code>{@link Calendar}</code>,
     * but it does not want to be fired now.
     * </p>
     */
    const MISFIRE_INSTRUCTION_DO_NOTHING = 2;

    public function __construct()
    {
        parent::__construct(self::INSTANCE);

        $this->setRepeatInterval(1);
        $this->setRepeatIntervalUnit(IntervalUnit::SECOND);
        $this->setRepeatCount(self::REPEAT_INDEFINITELY);
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
     * <p>Set the interval unit - the time unit on with the interval applies.</p>
     *
     * @param int $intervalUnit The repeat interval unit. The only intervals that are valid for this type of trigger are
     *
     * {@link IntervalUnit::SECOND}, {@link IntervalUnit::MINUTE}, and {@link IntervalUnit::HOUR}.
     */
    public function setRepeatIntervalUnit($intervalUnit)
    {
        if (false == ($intervalUnit === IntervalUnit::SECOND ||
            $intervalUnit === IntervalUnit::MINUTE ||
            $intervalUnit === IntervalUnit::HOUR)) {
            throw new \InvalidArgumentException('Invalid repeat IntervalUnit (must be SECOND, MINUTE or HOUR).');
        }

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
     * set the the time interval that will be added to the <code>DailyTimeIntervalTrigger</code>'s
     * fire time (in the set repeat interval unit) in order to calculate the time of the
     * next trigger repeat.
     * </p>
     *
     * @param int $repeatInterval
     *
     * @exception \InvalidArgumentException if repeatInterval is < 1
     */
    public function setRepeatInterval($repeatInterval)
    {
        if (false == is_int($repeatInterval) || $repeatInterval < 0) {
            throw new \InvalidArgumentException('Repeat interval must be >= 1');
        }

        $this->setValue('repeatInterval', $repeatInterval);
    }

    /**
     * The days of the week upon which to fire.
     *
     * @return int[] a Set containing the integers representing the days of the week, per the values 1-7 as defined by
     *
     * {@link DateBuilder::SUNDAY} - {@link DateBuilder::SATURDAY}.
     */
    public function getDaysOfWeek()
    {
        return $this->getValue('daysOfWeek', self::ALL_DAYS_OF_THE_WEEK);
    }

    /**
     * @param int[] $daysOfWeek
     */
    public function setDaysOfWeek(array $daysOfWeek)
    {
        if (false == $daysOfWeek) {
            throw new \InvalidArgumentException('DaysOfWeek set must be a set that contains at least one day.');
        }

        foreach ($daysOfWeek as $dayOfWeek) {
            DateBuilder::validateDayOfWeek($dayOfWeek);
        }

        $this->setValue('daysOfWeek', $daysOfWeek);
    }

    /**
     * The time of day to start firing at the given interval.
     *
     * @return \DateTime only time part is relevant
     */
    public function getStartTimeOfDay()
    {
        return $this->getValue('startTimeOfDay', null, \DateTime::class);
    }

    /**
     * The time of day to complete firing at the given interval.
     *
     * @param \DateTime $startTimeOfDay only time part is relevant
     */
    public function setStartTimeOfDay(\DateTime $startTimeOfDay)
    {
        $startTimeOfDay = clone $startTimeOfDay;
        $startTimeOfDay->setDate(1970, 1, 1); // zero date

        if ($this->getEndTimeOfDay() && $this->getEndTimeOfDay() < $startTimeOfDay) {
            throw new \InvalidArgumentException('End time of day cannot be before start time of day');
        }

        $this->setValue('startTimeOfDay', $startTimeOfDay);
    }

    /**
     * @return \DateTime|null only time part is relevant
     */
    public function getEndTimeOfDay()
    {
        return $this->getValue('endTimeOfDay', null, \DateTime::class);
    }

    /**
     * @param \DateTime $endTimeOfDay only time part is relevant
     */
    public function setEndTimeOfDay(\DateTime $endTimeOfDay)
    {
        $endTimeOfDay = clone $endTimeOfDay;
        $endTimeOfDay->setDate(1970, 1, 1); // zero date

        if ($this->getStartTimeOfDay() && $endTimeOfDay < $this->getStartTimeOfDay()) {
            throw new \InvalidArgumentException('End time of day cannot be before start time of day');
        }

        $this->setValue('endTimeOfDay', $endTimeOfDay);
    }
    /**
     * <p>
     * Get the the number of times for interval this trigger should
     * repeat, after which it will be automatically deleted.
     * </p>
     *
     * @return int
     *
     * @see #REPEAT_INDEFINITELY
     */
    public function getRepeatCount()
    {
        return $this->getValue('repeatCount');
    }

    /**
     * @param int $repeatCount
     */
    public function setRepeatCount($repeatCount)
    {
        if ($repeatCount < 0 && $repeatCount !== self::REPEAT_INDEFINITELY) {
            throw new \InvalidArgumentException('Repeat count must be >= 0, use the constant REPEAT_INDEFINITELY for infinite.');
        }

        $this->setValue('repeatCount', $repeatCount);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateMisfireInstruction($misfireInstruction)
    {
        return $misfireInstruction >= self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY &&
               $misfireInstruction <= self::MISFIRE_INSTRUCTION_DO_NOTHING;
    }

    /**
     * <p>
     * Validates whether the properties of the <code>JobDetail</code> are
     * valid for submission into a <code>Scheduler</code>.
     *
     * @throws SchedulerException if a required property (such as Name, Group, Class) is not set.
     */
    public function validate()
    {
        parent::validate();

        $intervalUnit = $this->getRepeatIntervalUnit();
        $repeatInterval = $this->getRepeatInterval();

        if (false == in_array($intervalUnit, [IntervalUnit::SECOND, IntervalUnit::MINUTE, IntervalUnit::HOUR], true)) {
            throw new SchedulerException('Invalid repeat IntervalUnit (must be SECOND, MINUTE or HOUR).');
        }

        if ($intervalUnit < 1) {
            throw new SchedulerException('Repeat Interval cannot be zero.');
        }

        // Ensure interval does not exceed 24 hours
        $secondsInHour = 24 * 60 * 60;
        if ($intervalUnit === IntervalUnit::SECOND && $repeatInterval > $secondsInHour) {
            throw new SchedulerException(sprintf(
                'repeatInterval can not exceed 24 hours ("%s" seconds). Given "%d"', $secondsInHour, $repeatInterval));
        }

        if ($intervalUnit === IntervalUnit::MINUTE && $repeatInterval > ($secondsInHour / 60)) {
            throw new SchedulerException(sprintf(
                'repeatInterval can not exceed 24 hours ("%d" minutes). Given "%d"', $secondsInHour / 60, $repeatInterval));
        }

        if ($intervalUnit === IntervalUnit::HOUR && $repeatInterval > 24) {
            throw new SchedulerException(sprintf('repeatInterval can not exceed 24 hours. Given "%d" hours.', $repeatInterval));
        }

        // Ensure timeOfDay is in order.
        if ($this->getEndTimeOfDay() && $this->getStartTimeOfDay() > $this->getEndTimeOfDay()) {
            throw new SchedulerException(sprintf(
                'StartTimeOfDay "%s" should not come after endTimeOfDay "%s"',
                $this->getStartTimeOfDay()->format('Y:m:d H:i:s'), $this->getEndTimeOfDay()->format('Y:m:d H:i:s'))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFireTimeAfter(\DateTime $afterTime = null)
    {
        // Check repeatCount limit
        if ($this->getRepeatCount() != self::REPEAT_INDEFINITELY && $this->getTimesTriggered() > $this->getRepeatCount()) {
            return null;
        }

        // a. Increment afterTime by a second, so that we are comparing against a time after it!
        if (null == $afterTime) {
            $afterTime = new \DateTime();
            $afterTime->add(new \DateInterval('PT1S'));
        } else {
            $afterTime = clone $afterTime;
            $afterTime->add(new \DateInterval('PT1S'));
        }

        // make sure afterTime is at least startTime
        if ($afterTime < $this->getStartTime()) {
            $afterTime = clone $this->getStartTime();
        }

        // b.Check to see if afterTime is after endTimeOfDay or not. If yes, then we need to advance to next day as well.
        $afterTimePastEndTimeOfDay = false;
        if ($this->getEndTimeOfDay()) {
            $afterTimePastEndTimeOfDay = $afterTime > $this->getTimeOfDayForDate($afterTime, $this->getEndTimeOfDay());
        }
        // c. now we need to move to the next valid day of week if either:
        // the given time is past the end time of day, or given time is not on a valid day of week
        if (null == $fireTime = $this->advanceToNextDayOfWeekIfNecessary($afterTime, $afterTimePastEndTimeOfDay)) {
            return null;
        }

        // d. Calculate and save fireTimeEndDate variable for later use
        if (null == $this->getEndTimeOfDay()) {
            $fireTimeEndDate = clone $fireTime;
            $fireTimeEndDate->setTime(23, 59, 59);
        } else {
            $fireTimeEndDate = $this->getTimeOfDayForDate($fireTime, $this->getEndTimeOfDay());
        }

        // e. Check fireTime against startTime or startTimeOfDay to see which go first.
        $fireTimeStartDate = $this->getTimeOfDayForDate($fireTime, $this->getStartTimeOfDay());
        if ($fireTime < $fireTimeStartDate) {
            return $fireTimeStartDate;
        }

        // f. Continue to calculate the fireTime by incremental unit of intervals.
        // recall that if fireTime was less that fireTimeStartDate, we didn't get this far
        $fireSec = (int) $fireTime->format('U');
        $startSec = (int) $fireTimeStartDate->format('U');
        $secondsAfterStart = $fireSec - $startSec;
        $fireTime = clone $fireTimeStartDate;
        $repeatInterval = $this->getRepeatInterval();
        $repeatUnit = $this->getRepeatIntervalUnit();

        if ($repeatUnit === IntervalUnit::SECOND) {
            $jumpCount = (int) ($secondsAfterStart / $repeatInterval);
            if (($secondsAfterStart % $repeatInterval) != 0) {
                $jumpCount++;
            }

            $fireTime->add(new \DateInterval(sprintf('PT%dS', $repeatInterval * $jumpCount)));
        } elseif ($repeatUnit === IntervalUnit::MINUTE) {
            $jumpCount = (int) ($secondsAfterStart / ($repeatInterval * 60));
            if (($secondsAfterStart % ($repeatInterval * 60)) != 0) {
                $jumpCount++;
            }

            $fireTime->add(new \DateInterval(sprintf('PT%dM', $repeatInterval * $jumpCount)));
        } elseif ($repeatUnit === IntervalUnit::HOUR) {
            $jumpCount = (int) ($secondsAfterStart / ($repeatInterval * 60 * 60));

            if (($secondsAfterStart % ($repeatInterval * 60 * 60)) != 0) {
                $jumpCount++;
            }

            $fireTime->add(new \DateInterval(sprintf('PT%dH', $repeatInterval * $jumpCount)));
        }

        // g. Ensure this new fireTime is within the day, or else we need to advance to next day.
        if ($fireTime > $fireTimeEndDate) {
            $fireTime = $this->advanceToNextDayOfWeekIfNecessary($fireTime,
                $this->isSameDay($fireTime, $fireTimeEndDate));

            // make sure we hit the startTimeOfDay on the new day
            $fireTime = $this->getTimeOfDayForDate($fireTime, $this->getStartTimeOfDay());
        }

        // i. Return calculated fireTime.
        return $fireTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getFinalFireTime()
    {
        if (null == $this->getEndTime()) {
            return null;
        }

        // We have an endTime, we still need to check to see if there is a endTimeOfDay if that's applicable.
        if ($endTime = $this->getEndTime()) {
            $endTimeOfDay = $this->getTimeOfDayForDate($endTime, $this->getEndTimeOfDay());

            if ($endTime < $endTimeOfDay) {
                $endTime = $endTimeOfDay;
            }
        }

        return $endTime;
    }

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
     * Return a date with date from $date object and time from $time object
     *
     * @param \DateTime $date
     * @param \DateTime $time
     *
     * @return \DateTime
     */
    public function getTimeOfDayForDate(\DateTime $date, \DateTime $time)
    {
        $hours = (int) $time->format('G');
        $minutes = (int) $time->format('i');
        $seconds = (int) $time->format('s');

        $dateTime = clone $date;
        $dateTime->setTime($hours, $minutes, $seconds);

        return $dateTime;
    }

    /**
     * Given fireTime time determine if it is on a valid day of week. If so, simply return it unaltered,
     * if not, advance to the next valid week day, and set the time of day to the start time of day
     *
     * @param \DateTime $fireTime - given next fireTime.
     * @param bool      $forceToAdvanceNextDay - flag to whether to advance day without check existing week day. This scenario
     * can happen when a caller determine fireTime has passed the endTimeOfDay that fireTime should move to next day anyway.
     * @return \DateTime a next day fireTime.
     */
    private function advanceToNextDayOfWeekIfNecessary(\DateTime $fireTime, $forceToAdvanceNextDay)
    {
        // a. Advance or adjust to next dayOfWeek if need to first, starting next day with startTimeOfDay.
        $sTimeOfDay = $this->getStartTimeOfDay();
        $fireTimeStartDate = $this->getTimeOfDayForDate($fireTime, $sTimeOfDay);
        $dayOfWeekOfFireTime = (int) $fireTimeStartDate->format('N');

        // b2. We need to advance to another day if isAfterTimePassEndTimeOfDay is true, or dayOfWeek is not set.
        $daysOfWeekToFire = $this->getDaysOfWeek();
        if ($forceToAdvanceNextDay || false == in_array($dayOfWeekOfFireTime, $daysOfWeekToFire, true)) {
            // Advance one day at a time until next available date.
            for ($i = 1; $i <= 7; $i++) {
                $fireTimeStartDate->add(new \DateInterval('P1D'));
                $dayOfWeekOfFireTime = (int) $fireTimeStartDate->format('N');
                if (in_array($dayOfWeekOfFireTime, $daysOfWeekToFire, true)) {
                    $fireTime = $fireTimeStartDate;

                    break;
                }
            }
        }

        // Check fireTime not pass the endTime
        $endTime = $this->getEndTime();
        if ($endTime && $fireTime > $endTime) {
            return null;
        }

        return $fireTime;
    }

    /**
     * @param \DateTime $d1
     * @param \DateTime $d2
     *
     * @return bool
     */
    private function isSameDay(\DateTime $d1, \DateTime $d2)
    {
        return $d1->format('Y') == $d2->format('Y') && $d1->format('z') == $d2->format('z');
    }
}
