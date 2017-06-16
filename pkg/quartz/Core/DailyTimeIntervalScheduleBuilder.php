<?php
namespace Quartz\Core;

use Quartz\Triggers\DailyTimeIntervalTrigger;

/**
 * A {@link ScheduleBuilder} implementation that build schedule for DailyTimeIntervalTrigger.
 *
 * <p>This builder provide an extra convenient method for you to set the trigger's endTimeOfDay. You may
 * use either endingDailyAt() or endingDailyAfterCount() to set the value. The later will auto calculate
 * your endTimeOfDay by using the interval, intervalUnit and startTimeOfDay to perform the calculation.
 *
 * <p>When using endingDailyAfterCount(), you should note that it is used to calculating endTimeOfDay. So
 * if your startTime on the first day is already pass by a time that would not add up to the count you
 * expected, until the next day comes. Remember that DailyTimeIntervalTrigger will use startTimeOfDay
 * and endTimeOfDay as fresh per each day!
 *
 * <p>Quartz provides a builder-style API for constructing scheduling-related
 * entities via a Domain-Specific Language (DSL).  The DSL can best be
 * utilized through the usage of static imports of the methods on the classes
 * <code>TriggerBuilder</code>, <code>JobBuilder</code>,
 * <code>DateBuilder</code>, <code>JobKey</code>, <code>TriggerKey</code>
 * and the various <code>ScheduleBuilder</code> implementations.</p>
 *
 * <p>Client code can then use the DSL to write code such as this:</p>
 * <pre>
 *         JobDetail job = newJob(MyJob.class)
 *             .withIdentity("myJob")
 *             .build();
 *
 *         Trigger trigger = newTrigger()
 *             .withIdentity(triggerKey("myTrigger", "myTriggerGroup"))
 *             .withSchedule(onDaysOfTheWeek(MONDAY, THURSDAY))
 *             .startAt(futureDate(10, MINUTES))
 *             .build();
 *
 *         scheduler.scheduleJob(job, trigger);
 * <pre>
 */
class DailyTimeIntervalScheduleBuilder extends ScheduleBuilder
{
    /**
     * @var int
     */
    private $interval;

    /**
     * @var int IntervalUnit
     */
    private $intervalUnit;

    /**
     * @var int[]
     */
    private $daysOfWeek;

    /**
     * @var \DateTime
     */
    private $startTimeOfDay;

    /**
     * @var \DateTime
     */
    private $endTimeOfDay;

    /**
     * @var int
     */
    private $repeatCount;

    /**
     * @var int
     */
    private $misfireInstruction;

    protected function __construct()
    {
        $this->interval = 1;
        $this->intervalUnit = IntervalUnit::MINUTE;
        $this->repeatCount = DailyTimeIntervalTrigger::REPEAT_INDEFINITELY;
        $this->misfireInstruction = Trigger::MISFIRE_INSTRUCTION_SMART_POLICY;
    }

    /**
     * Create a DailyTimeIntervalScheduleBuilder.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public static function dailyTimeIntervalSchedule()
    {
        return new DailyTimeIntervalScheduleBuilder();
    }

    /**
     * Build the actual Trigger -- NOT intended to be invoked by end users,
     * but will rather be invoked by a TriggerBuilder which this
     * ScheduleBuilder is given to.
     *
     * @return DailyTimeIntervalTrigger
     */
    public function build()
    {
        $trigger = new DailyTimeIntervalTrigger();
        $trigger->setRepeatInterval($this->interval);
        $trigger->setRepeatIntervalUnit($this->intervalUnit);
        $trigger->setMisfireInstruction($this->misfireInstruction);
        $trigger->setRepeatCount($this->repeatCount);

        if (null != $this->daysOfWeek) {
            $trigger->setDaysOfWeek($this->daysOfWeek);
        } else {
            $trigger->setDaysOfWeek(DailyTimeIntervalTrigger::ALL_DAYS_OF_THE_WEEK);
        }

        $now = new \DateTime();

        if (null != $this->startTimeOfDay) {
            $trigger->setStartTimeOfDay($this->startTimeOfDay);
        } else {
            $startTimeOfDay = clone $now;
            $startTimeOfDay->setTime(0, 0, 0);

            $trigger->setStartTimeOfDay($startTimeOfDay);
        }

        if(null != $this->endTimeOfDay) {
            $trigger->setEndTimeOfDay($this->endTimeOfDay);
        } else {
            $endTimeOfDay = clone $now;
            $endTimeOfDay->setTime(23, 59, 59);
        }

        return $trigger;
    }

    /**
     * Specify the time unit and interval for the Trigger to be produced.
     *
     * @param int $timeInterval the interval at which the trigger should repeat.
     * @param int $intervalUnit the time unit (IntervalUnit) of the interval. The only intervals that are valid for this type of
     * trigger are {@link IntervalUnit#SECOND}, {@link IntervalUnit#MINUTE}, and {@link IntervalUnit#HOUR}.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withInterval($timeInterval, $intervalUnit)
    {
        if (false == ($intervalUnit == IntervalUnit::SECOND ||
                      $intervalUnit == IntervalUnit::MINUTE ||
                      $intervalUnit == IntervalUnit::HOUR)) {
            throw new \InvalidArgumentException('Invalid repeat IntervalUnit (must be SECOND, MINUTE or HOUR).');
        }

        $this->validateInterval($timeInterval);

        $this->interval = $timeInterval;
        $this->intervalUnit = $intervalUnit;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.SECOND that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInSeconds the number of seconds at which the trigger should repeat.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withIntervalInSeconds($intervalInSeconds)
    {
        $this->withInterval($intervalInSeconds, IntervalUnit::SECOND);

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.MINUTE that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInMinutes the number of minutes at which the trigger should repeat.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withIntervalInMinutes($intervalInMinutes)
    {
        $this->withInterval($intervalInMinutes, IntervalUnit::MINUTE);

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.HOUR that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInHours the number of hours at which the trigger should repeat.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withIntervalInHours($intervalInHours)
    {
        $this->withInterval($intervalInHours, IntervalUnit::HOUR);

        return $this;
    }

    /**
     * Set the trigger to fire on the given days of the week.
     *
     * @param array $onDaysOfWeek a Set containing the integers representing the days of the week, per the values 1-7 as defined by
     * {@link DateBuilder::SUNDAY} - {@link DateBuilder::SATURDAY}.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function onDaysOfTheWeek(array $onDaysOfWeek)
    {
        if(false == $onDaysOfWeek) {
            throw new \InvalidArgumentException('Days of week must be an non-empty set.');
        }

        foreach ($onDaysOfWeek as $day) {
            DateBuilder::validateDayOfWeek($day);
        }

        $this->daysOfWeek = $onDaysOfWeek;

        return $this;
    }

    /**
     * Set the trigger to fire on the days from Monday through Friday.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function onMondayThroughFriday()
    {
        $this->daysOfWeek = [
            DateBuilder::MONDAY,
            DateBuilder::THURSDAY,
            DateBuilder::WEDNESDAY,
            DateBuilder::THURSDAY,
            DateBuilder::FRIDAY
        ];

        return $this;
    }

    /**
     * Set the trigger to fire on the days Saturday and Sunday.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function onSaturdayAndSunday()
    {
        $this->daysOfWeek = [
            DateBuilder::SATURDAY,
            DateBuilder::SUNDAY
        ];

        return $this;
    }

    /**
     * Set the trigger to fire on all days of the week.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function onEveryDay()
    {
        $this->daysOfWeek = DailyTimeIntervalTrigger::ALL_DAYS_OF_THE_WEEK;

        return $this;
    }

    /**
     * Set the trigger to begin firing each day at the given time.
     *
     * @param \DateTime $timeOfDay only time part is relevant
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function startingDailyAt(\DateTime $timeOfDay)
    {
        $this->startTimeOfDay = $timeOfDay;

        return $this;
    }

    /**
     * Set the startTimeOfDay for this trigger to end firing each day at the given time.
     *
     * @param \DateTime $timeOfDay only time part is relevant
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function endingDailyAt(\DateTime $timeOfDay)
    {
        $this->endTimeOfDay = $timeOfDay;

        return $this;
    }

    /**
     * Calculate and set the endTimeOfDay using count, interval and starTimeOfDay. This means
     * that these must be set before this method is call.
     *
     * @param int $count
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function endingDailyAfterCount($count)
    {
        if (false == is_int($count) || $count <= 0) {
            throw new \InvalidArgumentException('Ending daily after count must be a positive number!');
        }

        if (null == $this->startTimeOfDay) {
            throw new \InvalidArgumentException('You must set the startDailyAt() before calling this endingDailyAfterCount()!');
        }

        $today = new \DateTime();
        $startTimeOfDayDate = $this->getTimeOfDayForDate($today, $this->startTimeOfDay);
        $maxEndTimeOfDayDate = (clone $today)->setTime(23, 59, 59);
        $remainingSecondsInDay = ((int) $maxEndTimeOfDayDate->format('U')) - ((int) $startTimeOfDayDate->format('U'));

        if ($this->intervalUnit == IntervalUnit::SECOND) {
            $intervalInSeconds = $this->interval;
        } elseif ($this->intervalUnit == IntervalUnit::MINUTE) {
            $intervalInSeconds = $this->interval * 60;
        } elseif ($this->intervalUnit == IntervalUnit::HOUR) {
            $intervalInSeconds = $this->interval * 60 * 24;
        } else {
            throw new \InvalidArgumentException(sprintf('The IntervalUnit is invalid for this trigger: "%s"', $this->intervalUnit));
        }

        if ($remainingSecondsInDay - $intervalInSeconds <= 0) {
            throw new \InvalidArgumentException('The startTimeOfDay is too late with given Interval and IntervalUnit values.');
        }

        $maxNumOfCount = ($remainingSecondsInDay / $intervalInSeconds);
        if ($count > $maxNumOfCount) {
            throw new \InvalidArgumentException(sprintf('The given count is too large!: count: "%s", max: "%s"', $count, $maxNumOfCount));
        }

        $incrementInSeconds = ($count - 1) * $intervalInSeconds;
        $endTimeOfDayDate = clone $startTimeOfDayDate;
        $endTimeOfDayDate->add(new \DateInterval(sprintf('PT%dS', $incrementInSeconds)));

        if ($endTimeOfDayDate > $maxEndTimeOfDayDate) {
            throw new \InvalidArgumentException(sprintf('The given count is too large!: count: "%s", max: "%s"', $count, $maxNumOfCount));
        }

        $this->endTimeOfDay = $endTimeOfDayDate;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link Trigger#MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY} instruction.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withMisfireHandlingInstructionIgnoreMisfires()
    {
        $this->misfireInstruction = DailyTimeIntervalTrigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link DailyTimeIntervalTrigger#MISFIRE_INSTRUCTION_DO_NOTHING} instruction.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withMisfireHandlingInstructionDoNothing()
    {
        $this->misfireInstruction = DailyTimeIntervalTrigger::MISFIRE_INSTRUCTION_DO_NOTHING;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link DailyTimeIntervalTrigger#MISFIRE_INSTRUCTION_FIRE_ONCE_NOW} instruction.
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withMisfireHandlingInstructionFireAndProceed()
    {
        $this->misfireInstruction = DailyTimeIntervalTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW;

        return $this;
    }

    /**
     * Set number of times for interval to repeat.
     *
     * <p>Note: if you want total count = 1 (at start time) + repeatCount</p>
     *
     * @param int $repeatCount;
     *
     * @return DailyTimeIntervalScheduleBuilder
     */
    public function withRepeatCount($repeatCount)
    {
        $this->repeatCount = $repeatCount;

        return $this;
    }

    /**
     * @param int $timeInterval
     */
    private function validateInterval($timeInterval)
    {
        if($timeInterval <= 0) {
            throw new \InvalidArgumentException('Interval must be a positive value.');
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
}
