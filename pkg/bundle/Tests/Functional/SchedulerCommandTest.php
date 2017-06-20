<?php

namespace Quartz\Bundle\Tests\Functional;

use Quartz\Bundle\Command\SchedulerCommand;

class SchedulerCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $scheduler = $this->container->get('quartz.cli.scheduler');

        $this->assertInstanceOf(SchedulerCommand::class, $scheduler);
    }
}