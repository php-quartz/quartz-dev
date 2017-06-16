<?php
namespace Quartz\Core;


abstract class ScheduleBuilder
{
    /**
     * @return Trigger
     */
    public abstract function build();
}
