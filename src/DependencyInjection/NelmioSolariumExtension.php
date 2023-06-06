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
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Endpoint;
use Solarium\Plugin\Loadbalancer\Loadbalancer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class NelmioSolariumExtension extends Extension
{
    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('registry.xml');

        $isDebug = true === $container->getParameter('kernel.debug');
        if ($isDebug) {
            $loader->load('logger.xml');
        }

        $defaultClient = $config['default_client'];
        if (!count($config['clients'])) {
            $config['clients'][$defaultClient] = [];
        } elseif (count($config['clients']) === 1) {
            $defaultClient = key($config['clients']);
        }

        // Configure the Solarium endpoints
        $endpointReferences = $this->configureEndpoints($config['endpoints'], $container);

        $clients = [];
        foreach ($config['clients'] as $name => $clientOptions) {
            $clientName = sprintf('solarium.client.%s', $name);

            if (isset($clientOptions['client_class'])) {
                $clientClass = $clientOptions['client_class'];
                unset($clientOptions['client_class']);
            } else {
                $clientClass = Client::class;
            }
            $clientDefinition = new Definition($clientClass);
            $clientDefinition->setPublic(true);
            $clients[$name] = new Reference($clientName);

            $container->setDefinition($clientName, $clientDefinition);

            if ($name === $defaultClient) {
                $container->setAlias('solarium.client', new Alias($clientName, true));
                $container->setAlias($clientClass, new Alias($clientName, true));
            }

            $options = [];

            // If some specific endpoints are given
            if ($endpointReferences) {
                if (isset($clientOptions['endpoints']) && !empty($clientOptions['endpoints'])) {
                    $endpoints = [];
                    foreach ($clientOptions['endpoints'] as $endpointName) {
                        if (isset($endpointReferences[$endpointName])) {
                            $endpoints[] = $endpointReferences[$endpointName];
                        }
                    }
                } else {
                    $endpoints = $endpointReferences;
                }

                $options['endpoint'] = $endpoints;
            }

            if (isset($clientOptions['adapter_service'])) {
                $adapterName = $clientOptions['adapter_service'];
            } else {
                $adapterName = sprintf('solarium.adapter.%s', $name);
                $adapterDefinition = $container->register($adapterName, Curl::class);
                if (isset($clientOptions['adapter_timeout'])) {
                    $adapterDefinition->addMethodCall('setTimeout', [$clientOptions['adapter_timeout']]);
                }
            }

            $clientDefinition->setArguments([
                new Reference($adapterName),
                new Reference('event_dispatcher'),
                $options,
            ]);

            // Configure the Load-Balancer for the current client
            $this->configureLoadBalancerForClient($clientName, $clientOptions, $clientDefinition, $container);

            // Default endpoint
            if (isset($clientOptions['default_endpoint'], $endpointReferences[$clientOptions['default_endpoint']])) {
                $clientDefinition->addMethodCall('setDefaultEndpoint', [$clientOptions['default_endpoint']]);
            }

            // Add the optional adapter class
            if (isset($clientOptions['adapter_class'])) {
                $clientDefinition->addMethodCall('setAdapter', [$clientOptions['adapter_class']]);
            }

            // Configure the Plugins for the current client
            $this->configurePluginsForClient($clientOptions, $clientDefinition);

            if ($isDebug) {
                // If debug, associate the logger to this client
                $this->configureLoggerForClient($clientName, $container);
            }
        }

        // configure registry
        $registry = $container->getDefinition('solarium.client_registry');
        $registry->replaceArgument(0, $clients);
        if (array_key_exists($defaultClient, $clients)) {
            $registry->replaceArgument(1, $defaultClient);
        }
    }

    private function configureEndpoints(array $endpoints, ContainerBuilder $container): array
    {
        $endpointReferences = [];
        foreach ($endpoints as $name => $endpointOptions) {
            $endpointName = sprintf('solarium.client.endpoint.%s', $name);
            $endpointOptions['key'] = $name;

            $container
                ->setDefinition($endpointName, new Definition(Endpoint::class))
                ->setArguments([$endpointOptions]);
            $endpointReferences[$name] = new Reference($endpointName);
        }

        return $endpointReferences;
    }

    private function configureLoggerForClient(string $clientName, ContainerBuilder $container): void
    {
        $logger = new Reference('solarium.data_collector');
        $container->getDefinition($clientName)->addMethodCall('registerPlugin', [$clientName.'.logger', $logger]);
    }

    private function configureLoadBalancerForClient(
        string $clientName,
        array $clientOptions,
        Definition $clientDefinition,
        ContainerBuilder $container
    ): void {
        if (isset($clientOptions['load_balancer']) && $clientOptions['load_balancer']['enabled']) {
            $loadBalancerDefinition = new Definition(Loadbalancer::class);
            $loadBalancerDefinition
                ->addMethodCall('addEndpoints', [$clientOptions['load_balancer']['endpoints']])
            ;
            if (isset($clientOptions['load_balancer']['blocked_query_types'])) {
                $loadBalancerDefinition
                    ->addMethodCall('setBlockedQueryTypes', [$clientOptions['load_balancer']['blocked_query_types']])
                ;
            }

            $loadBalancerName = $clientName.'.load_balancer';
            $container->setDefinition($loadBalancerName, $loadBalancerDefinition);

            $clientDefinition
                ->addMethodCall('registerPlugin', ['loadbalancer', new Reference($loadBalancerName)])
            ;
        }
    }

    private function configurePluginsForClient(array $clientOptions, Definition $clientDefinition): void
    {
        if (isset($clientOptions['plugins'])) {
            foreach ($clientOptions['plugins'] as $pluginName => $pluginOptions) {
                if (isset($pluginOptions['plugin_class'])) {
                    $plugin = $pluginOptions['plugin_class'];
                } else {
                    $plugin = new Reference($pluginOptions['plugin_service']);
                }
                $clientDefinition->addMethodCall('registerPlugin', [$pluginName, $plugin]);
            }
        }
    }
}
