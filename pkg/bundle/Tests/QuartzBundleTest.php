<?php

namespace Quartz\Bundle\Tests;

use PHPUnit\Framework\TestCase;
use Quartz\Bridge\DI\QuartzJobCompilerPass;
use Quartz\Bundle\QuartzBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class QuartzBundleTest extends TestCase
{
    public function testShouldExtendBundleClass()
    {
        $this->assertInstanceOf(Bundle::class, new QuartzBundle());
    }

    public function testShouldRegisterExpectedCompilerPasses()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(QuartzJobCompilerPass::class))
        ;

        $bundle = new QuartzBundle();
        $bundle->build($container);
    }
}
