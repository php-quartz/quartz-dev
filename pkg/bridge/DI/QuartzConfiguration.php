<?php

namespace Quartz\Bridge\DI;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class QuartzConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('quartz');

        $rootNode->children()
            ->arrayNode('yadm_simple_store')->children()
                ->scalarNode('uri')->defaultValue('mongodb://localhost:27017')->end()
                ->variableNode('uriOptions')->defaultValue([])->end()
                ->variableNode('driverOptions')->defaultValue([])->end()
                ->scalarNode('sessionId')->defaultValue('quartz')->end()
                ->scalarNode('dbName')->defaultValue(null)->end()
                ->scalarNode('managementLockCol')->defaultValue('quartz_management_lock')->end()
                ->scalarNode('calendarCol')->defaultValue('quartz_calendar')->end()
                ->scalarNode('triggerCol')->defaultValue('quartz_trigger')->end()
                ->scalarNode('firedTriggerCol')->defaultValue('quartz_fired_trigger')->end()
                ->scalarNode('jobCol')->defaultValue('quartz_job')->end()
                ->scalarNode('pausedTriggerCol')->defaultValue('quartz_paused_trigger')->end()
            ->end()->end()
            ->arrayNode('yadm_bundle_store')->children()
                ->scalarNode('sessionId')->defaultValue('quartz')->end()
                ->scalarNode('managementLockCol')->defaultValue('quartz_management_lock')->end()
                ->scalarNode('calendarStorage')->defaultValue('quartz_calendar')->end()
                ->scalarNode('triggerStorage')->defaultValue('quartz_trigger')->end()
                ->scalarNode('firedTriggerStorage')->defaultValue('quartz_fired_trigger')->end()
                ->scalarNode('jobStorage')->defaultValue('quartz_job')->end()
                ->scalarNode('pausedTriggerStorage')->defaultValue('quartz_paused_trigger')->end()
            ->end()->end()
            ->integerNode('misfireThreshold')->min(10)->defaultValue(60)->end()
        ;

        return $tb;
    }
}
