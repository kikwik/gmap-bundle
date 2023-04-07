<?php

namespace Kikwik\GmapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('kikwik_gmap');
        $rootNode = $treeBuilder->getRootNode();

//        $rootNode
//            ->children()
//                ->scalarNode('some_param')->defaultValue('some value')->cannotBeEmpty()->end()
//            ->end()
//        ;

        return $treeBuilder;
    }

}