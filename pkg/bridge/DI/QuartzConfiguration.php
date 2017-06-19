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
                ->scalarNode('db_name')->defaultValue('quartz')->end()
            ->end()->end()
            ->integerNode('misfire_threshold')->min(10)->defaultValue(60)->end()
        ;

        return $tb;
    }
}
