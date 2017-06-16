<?php
namespace Quartz\Calendar;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Makasim\Values\ObjectsTrait;
use Makasim\Values\ValuesTrait;
use Quartz\Core\Calendar;
use Quartz\Core\Model;
use Quartz\ModelClassFactory;

abstract class BaseCalendar implements Model, Calendar
{
    use ValuesTrait;
    use ObjectsTrait;

    public function __construct($instance, Calendar $baseCalendar = null)
    {
        $this->setInstance($instance);
        $this->setBaseCalendar($baseCalendar);
    }

    /**
     * @param string $instance
     */
    protected function setInstance($instance)
    {
        $this->setValue('instance', $instance);
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseCalendar(Calendar $baseCalendar = null)
    {
        $this->setObject('baseCalendar', $baseCalendar);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseCalendar()
    {
        return $this->getObject('baseCalendar', function ($values) {
            return ModelClassFactory::getClass($values);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->getValue('description');
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        $this->setValue('description', $description);
    }

    /**
     * {@inheritdoc}
     */
    public function isTimeIncluded($timeStamp)
    {
        if ($timeStamp <= 0) {
            throw new InvalidArgumentException('timeStamp must be greater 0');
        }

        if (null != $baseCalendar = $this->getBaseCalendar()) {
            return $baseCalendar->isTimeIncluded($timeStamp);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextIncludedTime($timeStamp)
    {
        if ($timeStamp <= 0) {
            throw new InvalidArgumentException('timeStamp must be greater 0');
        }

        if (null != $baseCalendar = $this->getBaseCalendar()) {
            return $baseCalendar->getNextIncludedTime($timeStamp);
        }

        return $timeStamp;
    }

    /**
     * Returns the time zone for which this <code>Calendar</code> will be
     * resolved.
     *
     * @return \DateTimeZone This Calendar's timezone, <code>null</code> if Calendar should use the default
     */
    public function getTimeZone()
    {
        if ($timezone = $this->getValue('timezone')) {
            return new \DateTimeZone($timezone);
        }
    }

    /**
     * Sets the time zone for which this <code>Calendar</code> will be resolved.
     *
     * @param \DateTimeZone $timeZone The time zone to use for this Calendar, null if default should be used
     */
    public function setTimeZone(\DateTimeZone $timeZone = null)
    {
        if ($timeZone) {
            $value = $timeZone->getName();
        } else {
            $value = null;
        }

        $this->setValue('timezone', $value);
    }
}
