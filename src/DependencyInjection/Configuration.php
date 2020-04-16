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

use Solarium\Client;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nelmio_solarium');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config < 4.2
            $rootNode = $treeBuilder->root('nelmio_solarium');
        }

        $rootNode
            ->children()
                ->scalarNode('default_client')->cannotBeEmpty()->defaultValue('default')->end()
                ->arrayNode('endpoints')
                    ->canBeUnset()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('scheme')->defaultValue('http')->end()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('port')->defaultValue(8983)->end()
                            ->scalarNode('path')->defaultValue('/')->end()
                            ->scalarNode('core')->end()
                            ->scalarNode('timeout')
                                ->setDeprecated('Configuring a timeout per endpoint is deprecated. Configure the timeout on the client adapter instead.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('clients')
                    ->canBeUnset()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return !empty($v['adapter_timeout']) && !empty($v['adapter_service']);
                            })
                            ->thenInvalid('Setting "adapter_timeout" is only supported for the default adapter and not in combination with "adapter_service".')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return !empty($v['adapter_class']) && !empty($v['adapter_service']);
                            })
                            ->thenInvalid('Only either "adapter_timeout" or "adapter_class" can be set')
                        ->end()
                        ->children()
                            ->scalarNode('client_class')->cannotBeEmpty()->defaultValue(Client::class)->end()
                            ->scalarNode('adapter_class')
                                ->setDeprecated('Configuring an adapter class is deprecated. Configure an adapter service instead.')
                            ->end()
                            ->scalarNode('adapter_timeout')->end()
                            ->scalarNode('adapter_service')->end()
                            ->arrayNode('endpoints')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then($this->getNormalizeListToArrayClosure())
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('default_endpoint')->end()
                            ->arrayNode('load_balancer')
                                ->addDefaultsIfNotSet()
                                ->treatFalseLike(array('enabled' => false))
                                ->treatTrueLike(array('enabled' => true))
                                ->treatNullLike(array('enabled' => true))
                                ->beforeNormalization()
                                    ->ifArray()
                                    ->then(function($v) {
                                        $v['enabled'] = isset($v['enabled']) ? $v['enabled'] : true;

                                        return $v;
                                    })
                                ->end()
                                ->children()
                                    ->booleanNode('enabled')->defaultFalse()->end()
                                    ->arrayNode('endpoints')
                                        ->requiresAtLeastOneElement()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then($this->getNormalizeListToArrayClosure())
                                        ->end()
                                        ->beforeNormalization()
                                            ->always(function (array $endpoints) {
                                                // the values should be the weight and the keys the endpoints name
                                                // handle the case where people just list the endpoints like [endpoint1, endpoint2]
                                                $normalizedEndpoints = array();
                                                foreach ($endpoints as $name => $weight) {
                                                    if (!is_string($name)) {
                                                        $name = $weight;
                                                        $weight = 1;
                                                    }

                                                    $normalizedEndpoints[$name] = $weight;
                                                }

                                                return $normalizedEndpoints;
                                            })
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->arrayNode('blocked_query_types')
                                        ->defaultValue(array(Client::QUERY_UPDATE))
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then($this->getNormalizeListToArrayClosure())
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('plugins')
                                ->canBeUnset()
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->validate()
                                        ->ifTrue(function ($v) { return !empty($v['plugin_class']) && !empty($v['plugin_service']); })
                                        ->thenInvalid('Only either a plugin class or a plugin service can be set')
                                    ->end()
                                    ->children()
                                        ->scalarNode('plugin_class')->end()
                                        ->scalarNode('plugin_service')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    public function getNormalizeListToArrayClosure()
    {
        return function ($endpointList) {
            return preg_split('/\s*,\s*/', $endpointList);
        };
    }
}
