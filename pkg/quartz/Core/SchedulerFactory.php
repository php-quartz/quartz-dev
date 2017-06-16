<?php
namespace Quartz\Core;

interface SchedulerFactory
{
    /**
     * <p>
     * Returns a client-usable handle to a <code>Scheduler</code>.
     * </p>
     *
     * @return Scheduler
     */
    public function getScheduler();
}
