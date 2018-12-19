<?php

namespace Quartz\Bundle\Tests\Functional;

use Quartz\Bundle\Command\SchedulerCommand;

class SchedulerCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $scheduler = static::$container->get('test_quartz.cli.scheduler');

        $this->assertInstanceOf(SchedulerCommand::class, $scheduler);
    }
}