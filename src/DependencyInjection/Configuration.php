<?php

namespace Kikwik\GmapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kikwik_gmap');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('gmapApiKeyJs')->defaultValue('%env(GMAP_API_KEY_JS)%')->cannotBeEmpty()->end()
            ->end()
        ;

        return $treeBuilder;
    }

}