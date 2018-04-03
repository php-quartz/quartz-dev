<?php

namespace Quartz\Bridge\DI;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class QuartzConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('quartz');

        $rootNode->children()
            ->arrayNode('store')->addDefaultsIfNotSet()->children()
                ->scalarNode('uri')->defaultValue('mongodb://localhost:27017')->end()
                ->variableNode('uriOptions')->defaultValue([])->end()
                ->variableNode('driverOptions')->defaultValue([])->end()
                ->scalarNode('sessionId')->defaultValue('quartz')->end()
                ->scalarNode('dbName')->defaultValue(null)->end()
                ->scalarNode('managementLockCol')->defaultValue('managementLock')->end()
                ->scalarNode('calendarCol')->defaultValue('calendar')->end()
                ->scalarNode('triggerCol')->defaultValue('trigger')->end()
                ->scalarNode('firedTriggerCol')->defaultValue('firedTrigger')->end()
                ->scalarNode('jobCol')->defaultValue('job')->end()
                ->scalarNode('pausedTriggerCol')->defaultValue('pausedTrigger')->end()
            ->end()->end()
            ->integerNode('misfireThreshold')->min(10)->defaultValue(60)->end()
        ;

        return $tb;
    }
}
