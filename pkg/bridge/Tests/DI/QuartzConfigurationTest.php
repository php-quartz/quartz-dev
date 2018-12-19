<?php
namespace Quartz\Bridge\Tests\DI;

use PHPUnit\Framework\TestCase;
use Quartz\Bridge\DI\QuartzConfiguration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class QuartzConfigurationTest extends TestCase
{
    public function testShouldImplementConfigurationInterface()
    {
        $this->assertInstanceOf(ConfigurationInterface::class, new QuartzConfiguration());
    }

    public function testShouldReturnDefaultConfig()
    {
        $configuration = new QuartzConfiguration([]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[]]);

        $expectedConfig = [
            'store' => [
                'uri' => 'mongodb://localhost:27017',
                'uriOptions' => [],
                'driverOptions' => [],
                'sessionId' => 'quartz',
                'dbName' => null,
                'managementLockCol' => 'managementLock',
                'calendarCol' => 'calendar',
                'triggerCol' => 'trigger',
                'firedTriggerCol' => 'firedTrigger',
                'jobCol' => 'job',
                'pausedTriggerCol' => 'pausedTrigger',
            ],
            'misfireThreshold' => 60,
        ];

        $this->assertSame($expectedConfig, $config);
    }

    public function testCouldSetConfigurationOptions()
    {
        $configuration = new QuartzConfiguration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'store' => [
                'uri' => 'the-uri',
            ],
            'misfireThreshold' => 120,
        ]]);

        $expectedConfig = [
            'store' => [
                'uri' => 'the-uri',
                'uriOptions' => [],
                'driverOptions' => [],
                'sessionId' => 'quartz',
                'dbName' => null,
                'managementLockCol' => 'managementLock',
                'calendarCol' => 'calendar',
                'triggerCol' => 'trigger',
                'firedTriggerCol' => 'firedTrigger',
                'jobCol' => 'job',
                'pausedTriggerCol' => 'pausedTrigger',
            ],
            'misfireThreshold' => 120,
        ];

        $this->assertSame($expectedConfig, $config);
    }
}
