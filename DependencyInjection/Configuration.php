<?php

/*
 * This file is part of the Nelmio SolariumBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\SolariumBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nelmio_solarium');

        $rootNode
            ->children()
                ->arrayNode('clients')
                ->prototype('array')
                //->requiresAtLeastOneElement()
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')->cannotBeEmpty()->defaultValue('Solarium_Client')->end()
                        ->scalarNode('adapter')->end()
                        ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                        ->scalarNode('port')->defaultValue(8983)->end()
                        ->scalarNode('path')->defaultValue('/solr')->end()
                        ->scalarNode('timeout')->defaultValue(5)->end()
                    ->end()
                ->end()
            ->end()
        ;

        /*
        $rootNode
            ->fixXmlConfig('client')
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('class')->cannotBeEmpty()->defaultValue('Solarium_Client')->end()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('port')->defaultValue(8983)->end()
                            ->scalarNode('path')->defaultValue('/solr')->end()
                            ->scalarNode('timeout')->defaultValue(5)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        */

        /*
        $rootNode
            ->children()
                ->arrayNode('client')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')->cannotBeEmpty()->defaultValue('Solarium_Client')->end()
                    ->end()
                ->end()
                ->arrayNode('adapter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')->defaultValue('Solarium_Client_Adapter_Http')->end()
                        ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                        ->scalarNode('port')->defaultValue(8983)->end()
                        ->scalarNode('path')->defaultValue('/solr')->end()
                        ->scalarNode('timeout')->defaultValue(5)->end()
                        ->arrayNode('cores')
                            ->useAttributeAsKey('key')
                            ->canBeUnset()
                            ->prototype('scalar')
                            ->end()
                    ->end()
                ->end()
            ->end()
        ;
        */

        return $treeBuilder;
    }

    private function getClientsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('clients');

        /** @var $connectionNode ArrayNodeDefinition */
        $connectionNode = $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
        ;

        $this->configureDbalDriverNode($connectionNode);

        $connectionNode
        ->fixXmlConfig('option')
        ->fixXmlConfig('mapping_type')
        ->fixXmlConfig('slave')
        ->children()
        ->scalarNode('driver')->defaultValue('pdo_mysql')->end()
        ->scalarNode('platform_service')->end()
        ->scalarNode('schema_filter')->end()
        ->booleanNode('logging')->defaultValue($this->debug)->end()
        ->booleanNode('profiling')->defaultValue($this->debug)->end()
        ->scalarNode('driver_class')->end()
        ->scalarNode('wrapper_class')->end()
        ->booleanNode('keep_slave')->end()
        ->arrayNode('options')
        ->useAttributeAsKey('key')
        ->prototype('scalar')->end()
        ->end()
        ->arrayNode('mapping_types')
        ->useAttributeAsKey('name')
        ->prototype('scalar')->end()
        ->end()
        ->end()
        ;

        $slaveNode = $connectionNode
        ->children()
        ->arrayNode('slaves')
        ->useAttributeAsKey('name')
        ->prototype('array')
        ;
        $this->configureDbalDriverNode($slaveNode);

        return $node;
    }
}
