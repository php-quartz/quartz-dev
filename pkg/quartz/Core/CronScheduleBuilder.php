<?php
namespace Quartz\Core;

use G4\Cron\CronExpression;
use Quartz\Triggers\CronTrigger;

/**
 * <code>CronScheduleBuilder</code> is a {@link ScheduleBuilder} that defines
 * {@link CronExpression}-based schedules for <code>Trigger</code>s.
 *
 * <p>
 * Quartz provides a builder-style API for constructing scheduling-related
 * entities via a Domain-Specific Language (DSL). The DSL can best be utilized
 * through the usage of static imports of the methods on the classes
 * <code>TriggerBuilder</code>, <code>JobBuilder</code>,
 * <code>DateBuilder</code>, <code>JobKey</code>, <code>TriggerKey</code> and
 * the various <code>ScheduleBuilder</code> implementations.
 * </p>
 *
 * <p>
 * Client code can then use the DSL to write code such as this:
 * </p>
 *
 * <pre>
 * JobDetail job = newJob(MyJob.class).withIdentity(&quot;myJob&quot;).build();
 *
 * Trigger trigger = newTrigger()
 *         .withIdentity(triggerKey(&quot;myTrigger&quot;, &quot;myTriggerGroup&quot;))
 *         .withSchedule(dailyAtHourAndMinute(10, 0))
 *         .startAt(futureDate(10, MINUTES)).build();
 *
 * scheduler.scheduleJob(job, trigger);
 *
 * <pre>
 */
class CronScheduleBuilder extends ScheduleBuilder
{
    /**
     * @var CronExpression
     */
    private $cronExpression;

    /**
     * @var int
     */
    private $misfireInstruction;

    /**
     * @param CronExpression $cronExpression
     */
    protected function __construct(CronExpression $cronExpression)
    {
        $this->cronExpression = $cronExpression;
        $this->misfireInstruction = CronTrigger::MISFIRE_INSTRUCTION_SMART_POLICY;
    }

    /**
     * Build the actual Trigger -- NOT intended to be invoked by end users, but
     * will rather be invoked by a TriggerBuilder which this ScheduleBuilder is
     * given to.
     *
     * @see TriggerBuilder#withSchedule(ScheduleBuilder)
     */
    public function build()
    {
        $trigger = new CronTrigger();

        $trigger->setCronExpression($this->cronExpression);
        $trigger->setMisfireInstruction($this->misfireInstruction);
//        $trigger->setTimeZone(cronExpression.getTimeZone());

        return $trigger;
    }

    /**
     * Create a CronScheduleBuilder with the given cron-expression string -
     * which is presumed to b e valid cron expression (and hence only a
     * RuntimeException will be thrown if it is not).
     *
     * @param string $cronExpression the cron expression string to base the schedule on.
     *
     * @return CronScheduleBuilder
     *
     * @throws \RuntimeException wrapping a ParseException if the expression is invalid
     */
    public static function cronSchedule($cronExpression)
    {
        try {
            return self::cronScheduleExpression(CronExpression::factory($cronExpression));
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(sprintf('CronExpression is invalid: "%s"', $e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException(sprintf('CronExpression is invalid: "%s"', $e->getMessage()));
        }
    }

    /**
     * Create a CronScheduleBuilder with the given cron-expression.
     *
     * @param CronExpression $cronExpression the cron expression to base the schedule on.
     *
     * @return CronScheduleBuilder
     *
     * @see CronExpression
     */
    public static function cronScheduleExpression(CronExpression $cronExpression)
    {
        return new CronScheduleBuilder($cronExpression);
    }


    /**
     * Create a CronScheduleBuilder with a cron-expression that sets the
     * schedule to fire every day at the given time (hour and minute).
     *
     * @param int $hour the hour of day to fire
     * @param int $minute the minute of the given hour to fire
     *
     * @return CronScheduleBuilder
     *
     * @see CronExpression
     */
    public static function dailyAtHourAndMinute($hour, $minute)
    {
        DateBuilder::validateHour($hour);
        DateBuilder::validateMinute($minute);

        $cronExpression = sprintf('0 %d %d ? * *', $minute, $hour);

        return self::cronSchedule($cronExpression);
    }

    /**
     * Create a CronScheduleBuilder with a cron-expression that sets the
     * schedule to fire at the given day at the given time (hour and minute) on
     * the given days of the week.
     *
     * @param int $hour the hour of day to fire
     * @param int $minute the minute of the given hour to fire
     * @param int[] $daysOfWeek the days of the week to fire
     *
     * @return CronScheduleBuilder
     *
     * @see CronExpression
     * @see DateBuilder#MONDAY
     * @see DateBuilder#TUESDAY
     * @see DateBuilder#WEDNESDAY
     * @see DateBuilder#THURSDAY
     * @see DateBuilder#FRIDAY
     * @see DateBuilder#SATURDAY
     * @see DateBuilder#SUNDAY
     */
    public static function atHourAndMinuteOnGivenDaysOfWeek($hour, $minute, ...$daysOfWeek)
    {
        if (false == $daysOfWeek) {
            throw new \InvalidArgumentException('You must specify at least one day of week.');
        }

        foreach ($daysOfWeek as $dayOfWeek) {
            DateBuilder::validateDayOfWeek($dayOfWeek);
        }

        DateBuilder::validateHour($hour);
        DateBuilder::validateMinute($minute);

        $cronExpression = sprintf('0 %d %d ? * %s', $minute, $hour, implode(',', $daysOfWeek));

        return self::cronSchedule($cronExpression);
    }

    /**
     * Create a CronScheduleBuilder with a cron-expression that sets the
     * schedule to fire one per week on the given day at the given time (hour
     * and minute).
     *
     * @param int $dayOfWeek the day of the week to fire
     * @param int $hour the hour of day to fire
     * @param int $minute the minute of the given hour to fire
     *
     * @return CronScheduleBuilder
     *
     * @see CronExpression
     * @see DateBuilder#MONDAY
     * @see DateBuilder#TUESDAY
     * @see DateBuilder#WEDNESDAY
     * @see DateBuilder#THURSDAY
     * @see DateBuilder#FRIDAY
     * @see DateBuilder#SATURDAY
     * @see DateBuilder#SUNDAY
     */
    public static function weeklyOnDayAndHourAndMinute($dayOfWeek, $hour, $minute)
    {
        DateBuilder::validateDayOfWeek($dayOfWeek);
        DateBuilder::validateHour($hour);
        DateBuilder::validateMinute($minute);

        $cronExpression = sprintf('0 %d %d ? * %d', $minute, $hour, $dayOfWeek);

        return self::cronSchedule($cronExpression);
    }

    /**
     * Create a CronScheduleBuilder with a cron-expression that sets the
     * schedule to fire one per month on the given day of month at the given
     * time (hour and minute).
     *
     * @param int $dayOfMonth the day of the month to fire
     * @param int $hour the hour of day to fire
     * @param int $minute the minute of the given hour to fire
     *
     * @return CronScheduleBuilder
     *
     * @see CronExpression
     */
    public static function monthlyOnDayAndHourAndMinute($dayOfMonth, $hour, $minute)
    {
        DateBuilder::validateDayOfMonth($dayOfMonth);
        DateBuilder::validateHour($hour);
        DateBuilder::validateMinute($minute);

        $cronExpression = sprintf("0 %d %d %d * ?", $minute, $hour, $dayOfMonth);

        return self::cronSchedule($cronExpression);
    }

    /**
     * If the Trigger misfires, use the
     * {@link Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY} instruction.
     *
     * @return CronScheduleBuilder
     *
     * @see Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY
     */
    public function withMisfireHandlingInstructionIgnoreMisfires()
    {
        $this->misfireInstruction = Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link CronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING} instruction.
     *
     * @return CronScheduleBuilder
     *
     * @see CronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING
     */
    public function withMisfireHandlingInstructionDoNothing()
    {
        $this->misfireInstruction = CronTrigger::MISFIRE_INSTRUCTION_DO_NOTHING;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW} instruction.
     *
     * @return CronScheduleBuilder
     *
     * @see CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW
     */
    public function withMisfireHandlingInstructionFireAndProceed()
    {
        $this->misfireInstruction = CronTrigger::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW;

        return $this;
    }
}