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
                ->scalarNode('default_client')->cannotBeEmpty()->defaultValue('default')->end()
                ->arrayNode('clients')
                    ->canBeUnset()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('client_class')->cannotBeEmpty()->defaultValue('Solarium_Client')->end()
                            ->scalarNode('adapter_class')->cannotBeEmpty()->defaultValue('Solarium_Client_Adapter_Http')->end()
                            ->scalarNode('dsn')
                                ->defaultValue('http://127.0.0.1:8983/solr')
                                ->validate()
                                    ->ifTrue(function($dsn) {
                                        return parse_url($dsn) === false;
                                    })
                                    ->thenInvalid('You need specify valid "dsn"')
                                ->end()
                            ->end()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('path')->end()
                            ->scalarNode('core')->end()
                            ->scalarNode('timeout')->defaultValue(5)->end()
                        ->end()
                        ->validate()
                            ->ifTrue(function($v) {
                                return (isset($v['dsn']) && (isset($v['host']) || isset($v['port']) || isset($v['path'])));
                            })
                            ->thenInvalid('You need specify only "dsn" or "host", "port", "path" parameters')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
