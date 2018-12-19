<?php
namespace Quartz\Bundle\Tests\DependencyInjection;

use Quartz\Bundle\DependencyInjection\QuartzExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class QuartzExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldImplementConfigurationInterface()
    {
        $this->assertInstanceOf(Extension::class, new QuartzExtension());
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new QuartzExtension();
    }

    public function testShouldNotLoadSchedulerServicesIfDisabled()
    {
        $container = new ContainerBuilder();

        $ext = new QuartzExtension();
        $ext->load([[
            'scheduler' => false,
        ]], $container);

        $this->assertNotContains('quartz.scheduler', $container->getServiceIds());
    }

    public function testShouldLoadSchedulerServicesIfEnabled()
    {
        $container = new ContainerBuilder();

        $ext = new QuartzExtension();
        $ext->load([[
            'scheduler' => null,
        ]], $container);

        $this->assertContains('quartz.scheduler', $container->getServiceIds());
    }

    public function testShouldNotLoadRemoteSchedulerServicesIfDisabled()
    {
        $container = new ContainerBuilder();

        $ext = new QuartzExtension();
        $ext->load([[
            'remote_scheduler' => false,
        ]], $container);

        $this->assertNotContains('test_quartz.remote.scheduler', $container->getServiceIds());
    }

    public function testShouldLoadRemoteSchedulerServicesIfEnabled()
    {
        $container = new ContainerBuilder();

        $ext = new QuartzExtension();
        $ext->load([[
            'remote_scheduler' => null,
        ]], $container);

        $this->assertContains('quartz.remote.scheduler', $container->getServiceIds());
    }
}
