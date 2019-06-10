<?php

namespace Quartz\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder('quartz');
        $rootNode = $tb->getRootNode();

        $rootNode->children()
            ->variableNode('remote_scheduler')
                ->defaultValue([])
                ->treatNullLike([])
                ->treatTrueLike([])
                ->info('Remote scheduler configuration')
            ->end()
            ->variableNode('scheduler')
                ->defaultValue(false)
                ->treatNullLike([])
                ->treatTrueLike([])
                ->info('Scheduler configuration')
            ->end()
        ->end()
        ;

        return $tb;
    }
}
