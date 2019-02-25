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

use Solarium\Core\Client\Endpoint;
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
     * @param array $configs
     * @param ContainerBuilder $container
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
            $config['clients'][$defaultClient] = array();
        } elseif (count($config['clients']) === 1) {
            $defaultClient = key($config['clients']);
        }

        // Configure the Solarium endpoints
        $endpointReferences = $this->configureEndpoints($config['endpoints'], $container);

        $clients = array();
        foreach ($config['clients'] as $name => $clientOptions) {
            $clientName = sprintf('solarium.client.%s', $name);

            if (isset($clientOptions['client_class'])) {
                $clientClass = $clientOptions['client_class'];
                unset($clientOptions['client_class']);
            } else {
                $clientClass = 'Solarium\Client';
            }
            $clientDefinition = new Definition($clientClass);
            $clients[$name] = new Reference($clientName);

            $container->setDefinition($clientName, $clientDefinition);
            $clientDefinition->addMethodCall('setEventDispatcher', array(new Reference('event_dispatcher')));

            if ($name === $defaultClient) {
                $container->setAlias('solarium.client', new Alias($clientName, true));
                $container->setAlias($clientClass, new Alias($clientName, true));
            }

            //If some specific endpoints are given
            if ($endpointReferences) {
                if (isset($clientOptions['endpoints']) && !empty($clientOptions['endpoints'])) {
                    $endpoints = array();
                    foreach ($clientOptions['endpoints'] as $endpointName) {
                        if (isset($endpointReferences[$endpointName])) {
                            $endpoints[] = $endpointReferences[$endpointName];
                        }
                    }
                } else {
                    $endpoints = $endpointReferences;
                }
                $clientDefinition->setArguments(array(array(
                    'endpoint' => $endpoints,
                )));
            }

            // Configure the Load-Balancer for the current client
            $this->configureLoadBalancerForClient($clientName, $clientOptions, $clientDefinition, $container);

            //Default endpoint
            if (isset($clientOptions['default_endpoint']) && isset($endpointReferences[$clientOptions['default_endpoint']])) {
                $clientDefinition->addMethodCall('setDefaultEndpoint', array($clientOptions['default_endpoint']));
            }

            //Add the optional adapter class
            if (isset($clientOptions['adapter_class'])) {
                $clientDefinition->addMethodCall('setAdapter', array($clientOptions['adapter_class']));
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
        if (in_array($defaultClient, array_keys($clients))) {
            $registry->replaceArgument(1, $defaultClient);
        }
    }

    private function configureEndpoints(array $endpoints, ContainerBuilder $container)
    {
        $endpointReferences = array();
        foreach ($endpoints as $name => $endpointOptions) {
            $endpointName = sprintf('solarium.client.endpoint.%s', $name);
            $endpointOptions['key'] = $name;

            $container
                ->setDefinition($endpointName, new Definition(Endpoint::class))
                ->setArguments(array($endpointOptions));
            $endpointReferences[$name] = new Reference($endpointName);
        }

        return $endpointReferences;
    }

    /**
     * @param string           $clientName
     * @param ContainerBuilder $container
     */
    private function configureLoggerForClient($clientName, ContainerBuilder $container)
    {
        $logger = new Reference('solarium.data_collector');
        $container->getDefinition($clientName)->addMethodCall('registerPlugin', array($clientName . '.logger', $logger));
    }

    /**
     * @param string           $clientName
     * @param array            $clientOptions
     * @param Definition       $clientDefinition
     * @param ContainerBuilder $container
     */
    private function configureLoadBalancerForClient($clientName, array $clientOptions, Definition $clientDefinition, ContainerBuilder $container)
    {
        if (isset($clientOptions['load_balancer']) && $clientOptions['load_balancer']['enabled']) {
            $loadBalancerDefinition = new Definition('Solarium\Plugin\Loadbalancer\Loadbalancer');
            $loadBalancerDefinition
                ->addMethodCall('addEndpoints', array($clientOptions['load_balancer']['endpoints']))
            ;
            if (isset($clientOptions['load_balancer']['blocked_query_types'])) {
                $loadBalancerDefinition
                    ->addMethodCall('setBlockedQueryTypes', array($clientOptions['load_balancer']['blocked_query_types']))
                ;
            }

            $loadBalancerName = $clientName . '.load_balancer';
            $container->setDefinition($loadBalancerName, $loadBalancerDefinition);

            $clientDefinition
                ->addMethodCall('registerPlugin', array('loadbalancer', new Reference($loadBalancerName)))
            ;
        }
    }

    /**
     * @param array      $clientOptions
     * @param Definition $clientDefinition
     */
    private function configurePluginsForClient(array $clientOptions, Definition $clientDefinition)
    {
        if (isset($clientOptions['plugins'])) {
            foreach ($clientOptions['plugins'] as $pluginName => $pluginOptions) {
                if (isset($pluginOptions['plugin_class'])) {
                    $plugin = $pluginOptions['plugin_class'];
                } else {
                    $plugin = new Reference($pluginOptions['plugin_service']);
                }
                $clientDefinition->addMethodCall('registerPlugin', array($pluginName, $plugin));
            }
        }
    }
}
