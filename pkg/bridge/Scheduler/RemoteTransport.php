<?php
namespace Quartz\Bridge\Scheduler;


interface RemoteTransport
{
    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function request(array $parameters);
}
