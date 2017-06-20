<?php

namespace Quartz\Bundle\Tests\Functional;

use Quartz\Bundle\Command\ManagementCommand;

class ManagementCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $scheduler = $this->container->get('quartz.cli.management');

        $this->assertInstanceOf(ManagementCommand::class, $scheduler);
    }
}