<?php

namespace Quartz\Bundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Quartz\Bundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testShouldImplementConfigurationInterface()
    {
        $this->assertInstanceOf(ConfigurationInterface::class, new Configuration());
    }

    public function testByDefaultShouldEnableRemoteSchedulerAndDisableScheduler()
    {
        $configuration = new Configuration([]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[]]);

        $expectedConfig = [
            'remote_scheduler' => [],
            'scheduler' => false,
        ];

        $this->assertSame($expectedConfig, $config);
    }

    public function testShouldThreadNullAsEmptyArray()
    {
        $configuration = new Configuration([]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'remote_scheduler' => null,
            'scheduler' => null,
        ]]);

        $expectedConfig = [
            'remote_scheduler' => [],
            'scheduler' => [],
        ];

        $this->assertSame($expectedConfig, $config);
    }

    public function testShouldPassAnyVariables()
    {
        $configuration = new Configuration([]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'remote_scheduler' => [
                'key1' => 'value1',
            ],
            'scheduler' => [
                'key2' => 'value2',
            ],
        ]]);

        $expectedConfig = [
            'remote_scheduler' => [
                'key1' => 'value1',
            ],
            'scheduler' => [
                'key2' => 'value2',
            ],
        ];

        $this->assertSame($expectedConfig, $config);
    }
}
