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
        $configuration = new QuartzConfiguration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'yadm_simple_store' => [],
        ]]);

        $expectedConfig = [
            'yadm_simple_store' => [
                'uri' => 'mongodb://localhost:27017',
                'uriOptions' => [],
                'driverOptions' => [],
                'sessionId' => 'quartz',
                'dbName' => null,
                'managementLockCol' => 'quartz_management_lock',
                'calendarCol' => 'quartz_calendar',
                'triggerCol' => 'quartz_trigger',
                'firedTriggerCol' => 'quartz_fired_trigger',
                'jobCol' => 'quartz_job',
                'pausedTriggerCol' => 'quartz_paused_trigger',
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
            'yadm_simple_store' => [
                'uri' => 'the-uri',
            ],
            'misfireThreshold' => 120,
        ]]);

        $expectedConfig = [
            'yadm_simple_store' => [
                'uri' => 'the-uri',
                'uriOptions' => [],
                'driverOptions' => [],
                'sessionId' => 'quartz',
                'dbName' => null,
                'managementLockCol' => 'quartz_management_lock',
                'calendarCol' => 'quartz_calendar',
                'triggerCol' => 'quartz_trigger',
                'firedTriggerCol' => 'quartz_fired_trigger',
                'jobCol' => 'quartz_job',
                'pausedTriggerCol' => 'quartz_paused_trigger',
            ],
            'misfireThreshold' => 120,
        ];

        $this->assertSame($expectedConfig, $config);
    }
}
