<?php
namespace Quartz\Calendar;

use G4\Cron\CronExpression;
use Quartz\Core\Calendar;

/**
 * This implementation of the Calendar excludes the set of times expressed by a
 * given cron expression. For example, you
 * could use this calendar to exclude all but business hours (8AM - 5PM) every
 * day using the expression &quot;* * 0-7,18-23 ? * *&quot;.
 * <P>
 * It is important to remember that the cron expression here describes a set of
 * times to be <I>excluded</I> from firing. Whereas the cron expression in
 * {@link CronTrigger} describes a set of times that can
 * be <I>included</I> for firing. Thus, if a <CODE>CronTrigger</CODE> has a
 * given cron expression and is associated with a <CODE>CronCalendar</CODE> with
 * the <I>same</I> expression, the calendar will exclude all the times the
 * trigger includes, and they will cancel each other out.
 */
class CronCalendar extends BaseCalendar
{
    const INSTANCE = 'cron-cal';

    /**
     * @var CronExpression
     */
    private $cronExpr;

    /**
     * {@inheritdoc}
     */
    public function __construct(Calendar $baseCalendar = null, \DateTimeZone $timeZone = null)
    {
        parent::__construct(self::INSTANCE, $baseCalendar, $timeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function isTimeIncluded($timeStamp)
    {
        // Test the base calendar first. Only if the base calendar not already
        // excludes the time/date, continue evaluating this calendar instance.
        if (false == parent::isTimeIncluded($timeStamp)) {
            return false;
        }

        return false == $this->getCronExp()->isDue($this->createDateTime($timeStamp));
    }

    /**
     * {@inheritdoc}
     */
    public function getNextIncludedTime($timeStamp)
    {
        $nextIncludedTime = $timeStamp + 1;

        while (false == $this->isTimeIncluded($nextIncludedTime)) {
            //If the time is in a range excluded by this calendar, we can
            // move to the end of the excluded time range and continue testing
            // from there. Otherwise, if nextIncludedTime is excluded by the
            // baseCalendar, ask it the next time it includes and begin testing
            // from there. Failing this, add one millisecond and continue
            // testing.
            if ($this->getCronExp()->isDue($date = $this->createDateTime($nextIncludedTime))) {
                $nextIncludedTime = (int) $this->getNextInvalidTimeAfter($date)->format('U');
            } elseif ($this->getBaseCalendar() && false == $this->getBaseCalendar()->isTimeIncluded($nextIncludedTime)) {
                $nextIncludedTime = $this->getBaseCalendar()->getNextIncludedTime($nextIncludedTime);
            } else {
                $nextIncludedTime++;
            }
        }

        return $nextIncludedTime;
    }

    /**
     * Returns the next date/time <I>after</I> the given date/time which does
     * <I>not</I> satisfy the expression
     *
     * @param \DateTime $date the date/time at which to begin the search for the next invalid date/time
     *
     * @return \DateTime the next valid date/time
     */
    public function getNextInvalidTimeAfter(\DateTime $date)
    {
        $difference = 1;

        $lastDate = clone $date;

        // IMPROVE THIS! The following is a BAD solution to this problem. Performance will be very bad here, depending on the cron expression. It is, however A solution.

        //keep getting the next included time until it's farther than one second
        // apart. At that point, lastDate is the last valid fire time. We return
        // the second immediately following it.
        while ($difference == 1) {
            try {
                $newDate = $this->getCronExp()->getNextRunDate($lastDate);
            } catch (\RuntimeException $e) {
                break;
            }

            $difference = ((int) $newDate->format('U')) - ((int) $lastDate->format('U'));

            if ($difference == 1) {
                $lastDate = $newDate;
            }
        }

        $lastDate->add(new \DateInterval('PT1S'));

        return $lastDate;
    }

    /**
     * @param string|CronExpression $cronExpression
     */
    public function setCronExpression($cronExpression)
    {
        if ($cronExpression instanceof CronExpression) {
            $cronExpression = $cronExpression->getExpression();
        } elseif (false == is_string($cronExpression)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected string but got: "%s"', is_object($cronExpression) ? get_class($cronExpression) : gettype($cronExpression)));
        }

        $this->setValue('cronExpression', $cronExpression);

        // reinit cron expression, throws exception on invalid cron expression
        $this->cronExpr = null;
        $this->getCronExp();
    }

    /**
     * @return string|null
     */
    public function getCronExpression()
    {
        return $this->getValue('cronExpression');
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
}
