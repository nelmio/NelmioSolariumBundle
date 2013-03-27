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

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class NelmioSolariumExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $configs);

        if ($container->getParameter('kernel.debug') === true) {
            $isDebug = true;
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('logger.xml');
        } else {
            $isDebug = false;
        }

        $defaultClient = $config['default_client'];
        if (!count($config['clients'])) {
            $config['clients'][$defaultClient] = array();
        } elseif (count($config['clients']) === 1) {
            $defaultClient = key($config['clients']);
        }

        $endpointReferences = array();
        foreach ($config['endpoints'] as $name => $endpointOptions) {
            $endpointName = sprintf('solarium.client.endpoint.%s', $name);
            $endpointOptions['key'] = $name;
            $container
                ->setDefinition($endpointName, new Definition('Solarium\Core\Client\Endpoint'))
                ->setArguments(array($endpointOptions));
            $endpointReferences[$name] = new Reference($endpointName);
        }

        foreach ($config['clients'] as $name => $clientOptions) {
            $clientName = sprintf('solarium.client.%s', $name);

            if (isset($clientOptions['client_class'])) {
                $clientClass = $clientOptions['client_class'];
                unset($clientOptions['client_class']);
            } else {
                $clientClass = 'Solarium\Client';
            }
            $clientDefinition = new Definition($clientClass);
            $container->setDefinition($clientName, $clientDefinition);

            if ($name == $defaultClient) {
                $container->setAlias('solarium.client', $clientName);
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

            //Default endpoint
            if (isset($clientOptions['default_endpoint']) && isset($endpointReferences[$clientOptions['default_endpoint']])) {
                $clientDefinition->addMethodCall('setDefaultEndpoint', array($clientOptions['default_endpoint']));
            }

            //Add the optional adapter class
            if (isset($clientOptions['adapter_class'])) {
                $clientDefinition->addMethodCall('setAdapter', array($clientOptions['adapter_class']));
            }

            if ($isDebug) {
                $logger = new Reference('solarium.data_collector');
                $container->getDefinition($clientName)->addMethodCall('registerPlugin', array($clientName . '.logger', $logger));
            }
        }
    }
}
