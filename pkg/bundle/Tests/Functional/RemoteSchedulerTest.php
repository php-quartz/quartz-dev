<?php

namespace Quartz\Bundle\Tests\Functional;

use Quartz\Bridge\Scheduler\RemoteScheduler;

class RemoteSchedulerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $scheduler = $this->container->get('quartz.remote.scheduler');

        $this->assertInstanceOf(RemoteScheduler::class, $scheduler);
    }
}