<?php
namespace Quartz\Triggers;

use G4\Cron\CronExpression;
use Quartz\Core\Calendar;

/**
 * <p>
 * A concrete <code>{@link Trigger}</code> that is used to fire a <code>{@link org.quartz.JobDetail}</code>
 * at given moments in time, defined with Unix 'cron-like' definitions.
 * </p>
 */
class CronTrigger extends AbstractTrigger
{
    const INSTANCE = 'cron';

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link CronTrigger}</code> wants to be fired now
     * by <code>Scheduler</code>.
     * </p>
     */
    const MISFIRE_INSTRUCTION_FIRE_ONCE_NOW = 1;

    /**
     * <p>
     * Instructs the <code>{@link Scheduler}</code> that upon a mis-fire
     * situation, the <code>{@link CronTrigger}</code> wants to have it's
     * next-fire-time updated to the next time in the schedule after the
     * current time (taking into account any associated <code>{@link Calendar}</code>,
     * but it does not want to be fired now.
     * </p>
     */
    const MISFIRE_INSTRUCTION_DO_NOTHING = 2;

    /**
     * @var CronExpression
     */
    private $cronExpr;

    public function __construct()
    {
        parent::__construct(self::INSTANCE);
    }

    /**
     * @param string $cronExpression
     */
    public function setCronExpression($cronExpression)
    {
        $this->setValue('cronExpression', $cronExpression);

        // reinit cron expression, throws exception on invalid cron expression
        $this->cronExpr = null;
        $this->getCronExp();
    }

    /**
     * @param CronExpression $cronExpression
     */
    public function setCronExpressionInstance(CronExpression $cronExpression)
    {
        $this->setCronExpression($cronExpression->getExpression());
    }

    /**
     * @return string|null
     */
    public function getCronExpression()
    {
        return $this->getValue('cronExpression');
    }

    /**
     * <p>
     * Returns the next time at which the <code>CronTrigger</code> will fire,
     * after the given time. If the trigger will not fire after the given time,
     * <code>null</code> will be returned.
     * </p>
     *
     * <p>
     * Note that the date returned is NOT validated against the related
     * org.quartz.Calendar (if any)
     * </p>
     *
     * {@inheritdoc}
     */
    public function getFireTimeAfter(\DateTime $afterTime = null)
    {
        if (null == $afterTime) {
            $afterTime = new \DateTime();
        }

        if ($this->getStartTime() > $afterTime) {
            $afterTime = clone $this->getStartTime();
            $afterTime->sub(new \DateInterval('PT1S'));
        }

        if ($this->getEndTime() && $afterTime >= $this->getEndTime()) {
            return null;
        }

        $pot = $this->getTimeAfter($afterTime);
        if ($this->getEndTime() && $pot && $pot > $this->getEndTime()) {
            return null;
        }

        return $pot;
    }

    /**
     * <p>
     * NOT YET IMPLEMENTED: Returns the final time at which the
     * <code>CronTrigger</code> will fire.
     * </p>
     *
     * <p>
     * Note that the return time *may* be in the past. and the date returned is
     * not validated against org.quartz.calendar
     * </p>
     *
     * {@inheritdoc}
     */
    public function getFinalFireTime()
    {
        if ($this->getEndTime()) {
            $resultTime = clone $this->getEndTime();
            $resultTime->add(new \DateInterval('PT1S'));

            $resultTime = $this->getTimeBefore($resultTime);
        } else {
            $resultTime = null;
        }

        if ($resultTime && $this->getStartTime() && $resultTime < $this->getStartTime()) {
            return null;
        }

        return $resultTime;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateMisfireInstruction($misfireInstruction)
    {
        return $misfireInstruction >= self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY
            && $misfireInstruction <= self::MISFIRE_INSTRUCTION_DO_NOTHING;
    }

    /**
     * <p>
     * Updates the <code>CronTrigger</code>'s state based on the
     * MISFIRE_INSTRUCTION_XXX that was selected when the <code>CronTrigger</code>
     * was created.
     * </p>
     *
     * <p>
     * If the misfire instruction is set to MISFIRE_INSTRUCTION_SMART_POLICY,
     * then the following scheme will be used: <br>
     * <ul>
     * <li>The instruction will be interpreted as <code>MISFIRE_INSTRUCTION_FIRE_ONCE_NOW</code>
     * </ul>
     * </p>
     *
     * {@inheritdoc}
     */
    public function updateAfterMisfire(Calendar $cal = null)
    {
        $instr = $this->getMisfireInstruction();

        if($instr == self::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY) {
            return;
        }

        if ($instr == self::MISFIRE_INSTRUCTION_SMART_POLICY) {
            $instr = self::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW;
        }

        if ($instr == self::MISFIRE_INSTRUCTION_DO_NOTHING) {
            $newFireTime = $this->getFireTimeAfter(new \DateTime());
            while ($newFireTime && $cal && false == $cal->isTimeIncluded(((int) $newFireTime->format('U')))) {
                $newFireTime = $this->getFireTimeAfter($newFireTime);
            }

            $this->setNextFireTime($newFireTime);
        } elseif ($instr == self::MISFIRE_INSTRUCTION_FIRE_ONCE_NOW) {
            $this->setNextFireTime(new \DateTime());
        }
    }

    /**
     * @return CronExpression
     */
    protected function getCronExp()
    {
        if (null == $this->cronExpr && $cronExpression = $this->getCronExpression()) {
            $this->cronExpr = CronExpression::factory($cronExpression);
        }

        return $this->cronExpr;
    }

    ////////////////////////////////////////////////////////////////////////////
    //
    // Computation Functions
    //
    ////////////////////////////////////////////////////////////////////////////

    protected function getTimeAfter(\DateTime $afterTime)
    {
        if ($this->getCronExp()) {
            return $this->getCronExp()->getNextRunDate($afterTime);
        }
    }

    /**
     * Returns the time before the given time
     * that this <code>CronTrigger</code> will fire.
     */
    protected function getTimeBefore(\DateTime $eTime)
    {
        if ($this->getCronExp()) {
            return $this->getCronExp()->getPreviousRunDate($eTime);
        }
    }
}
