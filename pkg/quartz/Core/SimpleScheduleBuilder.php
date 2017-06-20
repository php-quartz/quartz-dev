<?php
namespace Quartz\Core;

use Quartz\Triggers\SimpleTrigger;

/**
 * <code>SimpleScheduleBuilder</code> is a {@link ScheduleBuilder}
 * that defines strict/literal interval-based schedules for
 * <code>Trigger</code>s.
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
class SimpleScheduleBuilder extends ScheduleBuilder
{
    /**
     * @var int
     */
    private $interval;

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
        $this->interval = 0;
        $this->repeatCount = 0;
        $this->misfireInstruction = SimpleTrigger::MISFIRE_INSTRUCTION_SMART_POLICY;
    }

    /**
     * Create a SimpleScheduleBuilder.
     *
     * @return SimpleScheduleBuilder
     */
    public static function simpleSchedule()
    {
        return new static();
    }

    /**
     * Create a SimpleScheduleBuilder set to repeat forever with an interval
     * of the given number of minutes.
     *
     * @param int $minutes
     *
     * @return SimpleScheduleBuilder
     */
    public static function repeatMinutelyForever($minutes = 1)
    {
        return static::simpleSchedule()
            ->withIntervalInMinutes($minutes)
            ->repeatForever();
    }

    /**
     * Create a SimpleScheduleBuilder set to repeat forever with an interval
     * of the given number of seconds.
     *
     * @param int $seconds
     *
     * @return SimpleScheduleBuilder
     */
    public static function repeatSecondlyForever($seconds = 1)
    {
        return static::simpleSchedule()
            ->withIntervalInSeconds($seconds)
            ->repeatForever();
    }

    /**
     * Create a SimpleScheduleBuilder set to repeat forever with an interval
     * of the given number of hours.
     *
     * @param int $hours
     *
     * @return SimpleScheduleBuilder
     */
    public static function repeatHourlyForever($hours = 1)
    {
        return static::simpleSchedule()
            ->withIntervalInHours($hours)
            ->repeatForever();
    }

    /**
     * Create a SimpleScheduleBuilder set to repeat the given number
     * of times - 1  with an interval of the given number of minutes.
     *
     * <p>Note: Total count = 1 (at start time) + repeat count</p>
     *
     * @param int $count
     * @param int $minutes
     *
     * @return SimpleScheduleBuilder
     */
    public static function repeatMinutelyForTotalCount($count, $minutes = 1)
    {
        if($count < 1) {
            throw new \InvalidArgumentException(sprintf('Total count of firings must be at least one! Given count: "%s"', $count));
        }

        return static::simpleSchedule()
            ->withIntervalInMinutes($minutes)
            ->withRepeatCount($count - 1);
    }

    /**
     * Create a SimpleScheduleBuilder set to repeat the given number
     * of times - 1  with an interval of the given number of seconds.
     *
     * <p>Note: Total count = 1 (at start time) + repeat count</p>
     *
     * @param int $count
     * @param int $seconds
     *
     * @return SimpleScheduleBuilder
     */
    public static function repeatSecondlyForTotalCount($count, $seconds = 1) {
        if($count < 1) {
            throw new \InvalidArgumentException(sprintf('Total count of firings must be at least one! Given count: "%s"', $count));
        }

        return static::simpleSchedule()
            ->withIntervalInSeconds($seconds)
            ->withRepeatCount($count - 1);
    }

    /**
     * Create a SimpleScheduleBuilder set to repeat the given number
     * of times - 1  with an interval of the given number of hours.
     *
     * <p>Note: Total count = 1 (at start time) + repeat count</p>
     *
     * @param int $count
     * @param int $hours
     *
     * @return SimpleScheduleBuilder
     */
    public static function repeatHourlyForTotalCount($count, $hours = 1) {
        if($count < 1) {
            throw new \InvalidArgumentException(sprintf('Total count of firings must be at least one! Given count: "%s"', $count));
        }

        return static::simpleSchedule()
            ->withIntervalInHours($hours)
            ->withRepeatCount($count - 1);
    }

    /**
     * Build the actual Trigger -- NOT intended to be invoked by end users,
     * but will rather be invoked by a TriggerBuilder which this
     * ScheduleBuilder is given to.
     *
     * @see TriggerBuilder#withSchedule(ScheduleBuilder)
     */
    public function build() {

        $trigger = new SimpleTrigger();
        $trigger->setRepeatInterval($this->interval);
        $trigger->setRepeatCount($this->repeatCount);
        $trigger->setMisfireInstruction($this->misfireInstruction);

        return $trigger;
    }

    /**
     * Specify a repeat interval in seconds
     *
     * @param int $intervalInSeconds the number of seconds at which the trigger should repeat.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#getRepeatInterval()
     * @see #withRepeatCount(int)
     */
    public function withIntervalInSeconds($intervalInSeconds)
    {
        $this->interval = $intervalInSeconds;

        return $this;
    }

    /**
     * Specify a repeat interval in minutes - which will then be multiplied
     * by 60 to produce milliseconds.
     *
     * @param int $intervalInMinutes the number of seconds at which the trigger should repeat.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#getRepeatInterval()
     * @see #withRepeatCount(int)
     */
    public function withIntervalInMinutes($intervalInMinutes)
    {
        $this->interval = $intervalInMinutes * 60;

        return $this;
    }

    /**
     * Specify a repeat interval in hours - which will then be multiplied
     * by 60 * 60 to produce seconds.
     *
     * @param int $intervalInHours the number of seconds at which the trigger should repeat.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#getRepeatInterval()
     * @see #withRepeatCount(int)
     */
    public function withIntervalInHours($intervalInHours)
    {
        $this->interval = $intervalInHours * 60 * 60 ;

        return $this;
    }

    /**
     * Specify a the number of time the trigger will repeat - total number of
     * firings will be this number + 1.
     *
     * @param int $triggerRepeatCount the number of seconds at which the trigger should repeat.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#getRepeatCount()
     * @see #repeatForever()
     */
    public function withRepeatCount($triggerRepeatCount)
    {
        $this->repeatCount = $triggerRepeatCount;

        return $this;
    }

    /**
     * Specify that the trigger will repeat indefinitely.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#getRepeatCount()
     * @see SimpleTrigger#REPEAT_INDEFINITELY
     * @see #withIntervalInMilliseconds(long)
     * @see #withIntervalInSeconds(int)
     * @see #withIntervalInMinutes(int)
     * @see #withIntervalInHours(int)
     */
    public function repeatForever()
    {
        $this->repeatCount = SimpleTrigger::REPEAT_INDEFINITELY;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link Trigger#MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY} instruction.
     *
     * @return SimpleScheduleBuilder
     *
     * @see Trigger#MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY
     */
    public function withMisfireHandlingInstructionIgnoreMisfires()
    {
        $this->misfireInstruction = Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link SimpleTrigger#MISFIRE_INSTRUCTION_FIRE_NOW} instruction.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#MISFIRE_INSTRUCTION_FIRE_NOW
     */

    public function withMisfireHandlingInstructionFireNow()
    {
        $this->misfireInstruction = SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT} instruction.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT
     */
    public function withMisfireHandlingInstructionNextWithExistingCount()
    {
        $this->misfireInstruction = SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT} instruction.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT
     */
    public function withMisfireHandlingInstructionNextWithRemainingCount()
    {
        $this->misfireInstruction = SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT} instruction.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT
     */
    public function withMisfireHandlingInstructionNowWithExistingCount()
    {
        $this->misfireInstruction = SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT;

        return $this;
    }

    /**
     * If the Trigger misfires, use the
     * {@link SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT} instruction.
     *
     * @return SimpleScheduleBuilder
     *
     * @see SimpleTrigger#MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT
     */
    public function withMisfireHandlingInstructionNowWithRemainingCount()
    {
        $this->misfireInstruction = SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT;

        return $this;
    }
}
