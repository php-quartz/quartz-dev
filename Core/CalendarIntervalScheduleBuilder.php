<?php
namespace Quartz\Core;

use Quartz\Triggers\CalendarIntervalTrigger;

/**
 * <code>CalendarIntervalScheduleBuilder</code> is a {@link ScheduleBuilder}
 * that defines calendar time (day, week, month, year) interval-based
 * schedules for <code>Trigger</code>s.
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
 *             .withSchedule(withIntervalInDays(3))
 *             .startAt(futureDate(10, MINUTES))
 *             .build();
 *
 *         scheduler.scheduleJob(job, trigger);
 * <pre>
 */
class CalendarIntervalScheduleBuilder extends ScheduleBuilder
{
    /**
     * @var int
     */
    private $interval = 1;

    /**
     * @var int
     */
    private $intervalUnit;

    /**
     * @var int
     */
    private $misfireInstruction;

    /**
     * @var \DateTimeZone
     */
    private $timeZone;

    /**
     * @var bool
     */
    private $preserveHourOfDayAcrossDaylightSavings;

    /**
     * @var bool
     */
    private $skipDayIfHourDoesNotExist;

    protected function __construct()
    {
        $this->intervalUnit = IntervalUnit::DAY;
        $this->misfireInstruction = CalendarIntervalTrigger::MISFIRE_INSTRUCTION_SMART_POLICY;
        $this->preserveHourOfDayAcrossDaylightSavings = false;
        $this->skipDayIfHourDoesNotExist = false;
        $this->timeZone = new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * Create a CalendarIntervalScheduleBuilder.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public static function calendarIntervalSchedule()
    {
        return new static();
    }

    /**
     * Build the actual Trigger -- NOT intended to be invoked by end users,
     * but will rather be invoked by a TriggerBuilder which this
     * ScheduleBuilder is given to.
     *
     * @return CalendarIntervalTrigger
     */
    public function build()
    {
        $trigger = new CalendarIntervalTrigger();
        $trigger->setRepeatInterval($this->interval);
        $trigger->setRepeatIntervalUnit($this->intervalUnit);
        $trigger->setMisfireInstruction($this->misfireInstruction);
        $trigger->setTimeZone($this->timeZone);
        $trigger->setPreserveHourOfDayAcrossDaylightSavings($this->preserveHourOfDayAcrossDaylightSavings);
        $trigger->setSkipDayIfHourDoesNotExist($this->skipDayIfHourDoesNotExist);

        return $trigger;
    }

    /**
     * Specify the time unit and interval for the Trigger to be produced.
     *
     * @param int $timeInterval the interval at which the trigger should repeat.
     * @param int $unit  the time unit (IntervalUnit) of the interval.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withInterval($timeInterval, $unit)
    {
        DateBuilder::validateIntervalUnit($unit);
        $this->validateInterval($timeInterval);

        $this->interval = $timeInterval;
        $this->intervalUnit = $unit;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.SECOND that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInSeconds the number of seconds at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInSeconds($intervalInSeconds)
    {
        $this->validateInterval($intervalInSeconds);

        $this->interval = $intervalInSeconds;
        $this->intervalUnit = IntervalUnit::SECOND;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.MINUTE that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInMinutes the number of minutes at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInMinutes($intervalInMinutes)
    {
        $this->validateInterval($intervalInMinutes);
        $this->interval = $intervalInMinutes;
        $this->intervalUnit = IntervalUnit::MINUTE;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.HOUR that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInHours the number of hours at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInHours($intervalInHours)
    {
        $this->validateInterval($intervalInHours);
        $this->interval = $intervalInHours;
        $this->intervalUnit = IntervalUnit::HOUR;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.DAY that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInDays the number of days at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInDays($intervalInDays)
    {
        $this->validateInterval($intervalInDays);
        $this->interval = $intervalInDays;
        $this->intervalUnit = IntervalUnit::DAY;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.WEEK that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInWeeks the number of weeks at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInWeeks($intervalInWeeks)
    {
        $this->validateInterval($intervalInWeeks);
        $this->interval = $intervalInWeeks;
        $this->intervalUnit = IntervalUnit::WEEK;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.MONTH that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInMonths the number of months at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInMonths($intervalInMonths)
    {
        $this->validateInterval($intervalInMonths);
        $this->interval = $intervalInMonths;
        $this->intervalUnit = IntervalUnit::MONTH;

        return $this;
    }

    /**
     * Specify an interval in the IntervalUnit.YEAR that the produced
     * Trigger will repeat at.
     *
     * @param int $intervalInYears the number of years at which the trigger should repeat.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withIntervalInYears($intervalInYears)
    {
        $this->validateInterval($intervalInYears);
        $this->interval = $intervalInYears;
        $this->intervalUnit = IntervalUnit::YEAR;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link Trigger#MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY} instruction.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withMisfireHandlingInstructionIgnoreMisfires()
    {
        $this->misfireInstruction = Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link CalendarIntervalTrigger#MISFIRE_INSTRUCTION_DO_NOTHING} instruction.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withMisfireHandlingInstructionDoNothing()
    {
        $this->misfireInstruction = CalendarIntervalTrigger::MISFIRE_INSTRUCTION_DO_NOTHING;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link CalendarIntervalTrigger#MISFIRE_INSTRUCTION_FIRE_ONCE_NOW} instruction.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function withMisfireHandlingInstructionFireAndProceed()
    {
        $this->misfireInstruction = CalendarIntervalTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW;

        return $this;
    }
    /**
     * The <code>TimeZone</code> in which to base the schedule.
     *
     * @param \DateTimeZone $timezone the time-zone for the schedule.
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function inTimeZone(\DateTimeZone $timezone)
    {
        $this->timeZone = $timezone;

        return $this;
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
     * @param bool $preserveHourOfDay
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function preserveHourOfDayAcrossDaylightSavings($preserveHourOfDay)
    {
        $this->preserveHourOfDayAcrossDaylightSavings = (bool) $preserveHourOfDay;

        return $this;
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
     * @param bool $skipDay
     *
     * @return CalendarIntervalScheduleBuilder
     */
    public function skipDayIfHourDoesNotExist($skipDay)
    {
        $this->skipDayIfHourDoesNotExist = $skipDay;

        return $this;
    }

    private function validateInterval($timeInterval)
    {
        if($timeInterval <= 0) {
            throw new \InvalidArgumentException('Interval must be a positive value.');
        }
    }
}
