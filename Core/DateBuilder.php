<?php
namespace Quartz\Core;

/**
 * <code>DateBuilder</code> is used to conveniently create
 * <code>java.util.Date</code> instances that meet particular criteria.
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
 *             .withSchedule(simpleSchedule()
 *                 .withIntervalInHours(1)
 *                 .repeatForever())
 *             .startAt(futureDate(10, MINUTES))
 *             .build();
 *
 *         scheduler.scheduleJob(job, trigger);
 * <pre>
 */
class DateBuilder
{
    const SUNDAY = 1;
    const MONDAY = 2;
    const TUESDAY = 3;
    const WEDNESDAY = 4;
    const THURSDAY = 5;
    const FRIDAY = 6;
    const SATURDAY = 7;

    public static function MAX_YEAR()
    {
        static $maxYear;

        if (null == $maxYear) {
            $maxYear = ((int) date('Y')) + 100;
        }

        return $maxYear;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function validateDayOfWeek($dayOfWeek)
    {
        if ($dayOfWeek < self::SUNDAY || $dayOfWeek > self::SATURDAY) {
            throw new \InvalidArgumentException('Invalid day of week.');
        }
    }

    public static function validateHour($hour)
    {
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException('Invalid hour (must be >= 0 and <= 23).');
        }
    }

    public static function validateMinute($minute)
    {
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException('Invalid minute (must be >= 0 and <= 59).');
        }
    }

    public static function validateSecond($second)
    {
        if ($second < 0 || $second > 59) {
            throw new \InvalidArgumentException('Invalid second (must be >= 0 and <= 59).');
        }
    }

    public static function validateDayOfMonth($day)
    {
        if ($day < 1 || $day > 31) {
            throw new \InvalidArgumentException('Invalid day of month.');
        }
    }

    public static function validateMonth($month)
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Invalid month (must be >= 1 and <= 12.');
        }
    }

    public static function validateYear($year)
    {
        if ($year < 0 || $year > self::MAX_YEAR()) {
            throw new \InvalidArgumentException('Invalid year (must be >= 0 and <= ' . self::MAX_YEAR());
        }
    }
}
