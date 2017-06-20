<?php
namespace Quartz\Triggers;

use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;
use Quartz\Core\SchedulerException;

/**
 * A <code>{@link Trigger}</code> that is used to fire a <code>Job</code>
 * at a given moment in time, and optionally repeated at a specified interval.
 *
 * @see TriggerBuilder
 * @see SimpleScheduleBuilder
 */
class SimpleTrigger extends AbstractTrigger
{
    const INSTANCE = 'simple';

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link SimpleTrigger}</code> wants to be fired
     * now by <code>Scheduler</code>.
     * </p>
     *
     * <p>
     * <i>NOTE:</i> This instruction should typically only be used for
     * 'one-shot' (non-repeating) Triggers. If it is used on a trigger with a
     * repeat count > 0 then it is equivalent to the instruction <code>{@link #MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT}
     * </code>.
     * </p>
     */
    const MISFIRE_INSTRUCTION_FIRE_NOW = 1;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link SimpleTrigger}</code> wants to be
     * re-scheduled to 'now' (even if the associated <code>{@link Calendar}</code>
     * excludes 'now') with the repeat count left as-is.  This does obey the
     * <code>Trigger</code> end-time however, so if 'now' is after the
     * end-time the <code>Trigger</code> will not fire again.
     * </p>
     *
     * <p>
     * <i>NOTE:</i> Use of this instruction causes the trigger to 'forget'
     * the start-time and repeat-count that it was originally setup with (this
     * is only an issue if you for some reason wanted to be able to tell what
     * the original values were at some later time).
     * </p>
     */
    const MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT = 2;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link SimpleTrigger}</code> wants to be
     * re-scheduled to 'now' (even if the associated <code>{@link Calendar}</code>
     * excludes 'now') with the repeat count set to what it would be, if it had
     * not missed any firings.  This does obey the <code>Trigger</code> end-time
     * however, so if 'now' is after the end-time the <code>Trigger</code> will
     * not fire again.
     * </p>
     *
     * <p>
     * <i>NOTE:</i> Use of this instruction causes the trigger to 'forget'
     * the start-time and repeat-count that it was originally setup with.
     * Instead, the repeat count on the trigger will be changed to whatever
     * the remaining repeat count is (this is only an issue if you for some
     * reason wanted to be able to tell what the original values were at some
     * later time).
     * </p>
     *
     * <p>
     * <i>NOTE:</i> This instruction could cause the <code>Trigger</code>
     * to go to the 'COMPLETE' state after firing 'now', if all the
     * repeat-fire-times where missed.
     * </p>
     */
    const MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT = 3;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link SimpleTrigger}</code> wants to be
     * re-scheduled to the next scheduled time after 'now' - taking into
     * account any associated <code>{@link Calendar}</code>, and with the
     * repeat count set to what it would be, if it had not missed any firings.
     * </p>
     *
     * <p>
     * <i>NOTE/WARNING:</i> This instruction could cause the <code>Trigger</code>
     * to go directly to the 'COMPLETE' state if all fire-times where missed.
     * </p>
     */
    const MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT = 4;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link SimpleTrigger}</code> wants to be
     * re-scheduled to the next scheduled time after 'now' - taking into
     * account any associated <code>{@link Calendar}</code>, and with the
     * repeat count left unchanged.
     * </p>
     *
     * <p>
     * <i>NOTE/WARNING:</i> This instruction could cause the <code>Trigger</code>
     * to go directly to the 'COMPLETE' state if the end-time of the trigger
     * has arrived.
     * </p>
     */
    const MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT = 5;

    /**
     * <p>
     * Used to indicate the 'repeat count' of the trigger is indefinite. Or in
     * other words, the trigger should repeat continually until the trigger's
     * ending timestamp.
     * </p>
     */
    const REPEAT_INDEFINITELY = -1;

    public function __construct()
    {
        parent::__construct(self::INSTANCE);
    }

    /**
     * @return int
     */
    public function getRepeatCount()
    {
        return $this->getValue('repeatCount', 0);
    }

    /**
     * @param int $repeatCount
     */
    public function setRepeatCount($repeatCount)
    {
        $this->setValue('repeatCount', $repeatCount);
    }

    /**
     * @return int
     */
    public function getRepeatInterval()
    {
        return $this->getValue('repeatInterval');
    }

    /**
     * @param int $repeatInterval
     */
    public function setRepeatInterval($repeatInterval)
    {
        $this->setValue('repeatInterval', $repeatInterval);
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        parent::validate();

        if ($this->getRepeatCount() != 0 && $this->getRepeatInterval() < 1) {
            throw new SchedulerException('Repeat Interval cannot be zero.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateMisfireInstruction($misfireInstruction)
    {
        if ($misfireInstruction < self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY) {
            return false;
        }

        if ($misfireInstruction > self::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFireTimeAfter(\DateTime $afterTime = null)
    {
        if (($this->getTimesTriggered() > $this->getRepeatCount())
            && ($this->getRepeatCount() != self::REPEAT_INDEFINITELY)) {
            return null;
        }

        if (null == $afterTime) {
            $afterTime = new \DateTime();
        }

        if ($this->getRepeatCount() == 0 && $afterTime > $this->getStartTime()) {
            return null;
        }

        $endTime = $this->getEndTime() ?: new \DateTime('@'.PHP_INT_MAX);

        if ($endTime <= $afterTime) {
            return null;
        }

        if ($afterTime < ($startTime = $this->getStartTime())) {
            return clone $startTime;
        }

        $numberOfTimesExecuted = (int) ((((int)$afterTime->format('U')) - ((int)$this->getStartTime()->format('U'))) / $this->getRepeatInterval()) + 1;

        if (($numberOfTimesExecuted > $this->getRepeatCount()) && ($this->getRepeatCount() != self::REPEAT_INDEFINITELY)) {
            return null;
        }

        $time = new \DateTime('@'.(((int) $this->getStartTime()->format('U')) + ($numberOfTimesExecuted * $this->getRepeatInterval())));

        if ($endTime <= $time) {
            return null;
        }

        return $time;
    }

    /**
     * @return \DateTime|null
     */
    public function getFinalFireTime()
    {
        if ($this->getRepeatCount() == 0) {
            return clone $this->getStartTime();
        }

        if ($this->getRepeatCount() == self::REPEAT_INDEFINITELY) {
            return ($this->getEndTime() == null) ? null : $this->getFireTimeBefore($this->getEndTime());
        }

        $lastTrigger = new \DateTime('@'.(((int) $this->getStartTime()->format('U')) + ($this->getRepeatCount() * $this->getRepeatInterval())));

        if ($this->getEndTime() == null || $lastTrigger < $this->getEndTime()) {
            return $lastTrigger;
        } else {
            return $this->getFireTimeBefore($this->getEndTime());
        }
    }

    /**
     * @param \DateTime $end
     *
     * @return \DateTime|null
     */
    public function getFireTimeBefore(\DateTime $end)
    {
        if ($end < $this->getStartTime()) {
            return null;
        }

        $numFires = $this->computeNumTimesFiredBetween($this->getStartTime(), $end);

        return new \DateTime('@'.(((int) $this->getStartTime()->format('U')) + ($numFires * $this->getRepeatInterval())));
    }


    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return int
     */
    public function computeNumTimesFiredBetween(\DateTime $start, \DateTime $end)
    {
        if ($this->getRepeatInterval() < 1) {
            return 0;
        }

        $time = ((int) $end->format('U')) - ((int) $start->format('U'));

        return (int) ($time / $this->getRepeatInterval());
    }

    /**
     * <p>
     * Updates the <code>SimpleTrigger</code>'s state based on the
     * MISFIRE_INSTRUCTION_XXX that was selected when the <code>SimpleTrigger</code>
     * was created.
     * </p>
     *
     * <p>
     * If the misfire instruction is set to MISFIRE_INSTRUCTION_SMART_POLICY,
     * then the following scheme will be used: <br>
     * <ul>
     * <li>If the Repeat Count is <code>0</code>, then the instruction will
     * be interpreted as <code>MISFIRE_INSTRUCTION_FIRE_NOW</code>.</li>
     * <li>If the Repeat Count is <code>REPEAT_INDEFINITELY</code>, then
     * the instruction will be interpreted as <code>MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT</code>.
     * <b>WARNING:</b> using MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT
     * with a trigger that has a non-null end-time may cause the trigger to
     * never fire again if the end-time arrived during the misfire time span.
     * </li>
     * <li>If the Repeat Count is <code>&gt; 0</code>, then the instruction
     * will be interpreted as <code>MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT</code>.
     * </li>
     * </ul>
     * </p>
     *
     * @param Calendar $cal
     */
    public function updateAfterMisfire(Calendar $cal = null)
    {
        $instr = $this->getMisfireInstruction();

        if($instr == self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY) {
            return;
        }

        if ($instr == self::MISFIRE_INSTRUCTION_SMART_POLICY) {
            if ($this->getRepeatCount() == 0) {
                $instr = self::MISFIRE_INSTRUCTION_FIRE_NOW;
            } elseif ($this->getRepeatCount() == self::REPEAT_INDEFINITELY) {
                $instr = self::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT;
            } else {
                $instr = self::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT;
            }
        } elseif ($instr == self::MISFIRE_INSTRUCTION_FIRE_NOW && $this->getRepeatCount() != 0) {
            $instr = self::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT;
        }

        if ($instr == self::MISFIRE_INSTRUCTION_FIRE_NOW) {
            $this->setNextFireTime(new \DateTime());
        } elseif ($instr == self::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_EXISTING_COUNT) {
            $newFireTime = $this->getFireTimeAfter(new \DateTime());
            $yearToGiveUpSchedulingAt = DateBuilder::MAX_YEAR();
            while ($newFireTime && $cal && false == $cal->isTimeIncluded(((int) $newFireTime->format('U')))) {
                $newFireTime = $this->getFireTimeAfter($newFireTime);

                if (null == $newFireTime) {
                    break;
                }

                //avoid infinite loop
                if (((int) $newFireTime->format('Y')) > $yearToGiveUpSchedulingAt) {
                    $newFireTime = null;
                }
            }
            $this->setNextFireTime($newFireTime);
        } elseif ($instr == self::MISFIRE_INSTRUCTION_RESCHEDULE_NEXT_WITH_REMAINING_COUNT) {
            $newFireTime = $this->getFireTimeAfter(new \DateTime());
            $yearToGiveUpSchedulingAt = DateBuilder::MAX_YEAR();
            while ($newFireTime && $cal && false == $cal->isTimeIncluded(((int) $newFireTime->format('U')))) {
                $newFireTime = $this->getFireTimeAfter($newFireTime);

                if (null == $newFireTime) {
                    break;
                }

                //avoid infinite loop
                if (((int) $newFireTime->format('Y')) > $yearToGiveUpSchedulingAt) {
                    $newFireTime = null;
                }
            }

            if ($newFireTime) {
                $timesMissed = $this->computeNumTimesFiredBetween($this->getNextFireTime(), $newFireTime);
                $this->setTimesTriggered($this->getTimesTriggered() + $timesMissed);
            }
        } elseif ($instr == self::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT) {
            $newFireTime = new \DateTime();
            if ($this->getRepeatCount() != 0 && $this->getRepeatCount() != self::REPEAT_INDEFINITELY) {
                $this->setRepeatCount($this->getRepeatCount() - $this->getTimesTriggered());
                $this->setTimesTriggered(0);
            }

            if ($this->getEndTime() && $this->getEndTime() < $newFireTime) {
                $this->setNextFireTime(null); // We are past the end time
            } else {
                $this->setStartTime($newFireTime);
                $this->setNextFireTime($newFireTime);
            }
        } elseif ($instr == self::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_REMAINING_REPEAT_COUNT) {
            $newFireTime = new \DateTime();
            $timesMissed = $this->computeNumTimesFiredBetween($this->getNextFireTime(), $newFireTime);

            if ($this->getRepeatCount() != 0 && $this->getRepeatCount() != self::REPEAT_INDEFINITELY) {
                $remainingCount = $this->getRepeatCount() - ($this->getTimesTriggered() + $timesMissed);

                if ($remainingCount <= 0) {
                    $remainingCount = 0;
                }

                $this->setRepeatCount($remainingCount);
                $this->setTimesTriggered(0);
            }

            if ($this->getEndTime() && $this->getEndTime() < $newFireTime) {
                $this->setNextFireTime(null); // We are past the end time
            } else {
                $this->setStartTime($newFireTime);
                $this->setNextFireTime($newFireTime);
            }
        }
    }
}
