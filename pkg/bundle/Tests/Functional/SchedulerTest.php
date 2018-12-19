<?php

namespace Quartz\Bundle\Tests\Functional;

use Quartz\Scheduler\StdScheduler;

class SchedulerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $scheduler = static::$container->get('test_quartz.scheduler');

        $this->assertInstanceOf(StdScheduler::class, $scheduler);
    }
}