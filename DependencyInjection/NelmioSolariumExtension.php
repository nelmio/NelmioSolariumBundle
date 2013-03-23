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
            $is_debug = true;
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('logger.xml');
        } else {
            $is_debug = false;
        }

        $default_client = $config['default_client'];
        if (!count($config['clients'])) {
            $config['clients'][$default_client] = array();
        } elseif (count($config['clients']) === 1) {
            $default_client = key($config['clients']);
        }

        foreach ($config['clients'] as $name => $client_options) {
            $client_name = sprintf('solarium.client.%s', $name);
            $adapter_name = sprintf('solarium.client.adapter.%s', $name);
            $endpoint_name = sprintf('solarium.client.endpoint.%s', $name);

            if (isset($client_options['client_class'])) {
                $client_class = $client_options['client_class'];
                unset($client_options['client_class']);
            } else {
                $client_class = 'Solarium\Client';
            }

            if (isset($client_options['adapter_class'])) {
                $adapter_class = $client_options['adapter_class'];
                unset($client_options['adapter_class']);
            } else {
                $adapter_class = 'Solarium\Core\Client\Adapter\Http';
            }



            $clientDefinition = new Definition($client_class);
            $container->setDefinition($client_name, $clientDefinition);

            if ($name == $default_client) {
                $container->setAlias('solarium.client', $client_name);
            }

            $container
                ->setDefinition($endpoint_name, new Definition('Solarium\Core\Client\Endpoint'))
                ->setArguments(array($client_options))
                ->addMethodCall('setKey', array($endpoint_name));

            $container
                ->getDefinition($client_name)
                ->addMethodCall('clearEndpoints')
                ->addMethodCall('addEndpoint', array(new Reference($endpoint_name)));

            $container->setDefinition($adapter_name, new Definition($adapter_class));
            $adapter = new Reference($adapter_name);
            $container->getDefinition($client_name)->addMethodCall('setAdapter', array($adapter));

            if ($is_debug) {
                $logger = new Reference('solarium.data_collector');
                $container->getDefinition($client_name)->addMethodCall('registerPlugin', array($client_name . '.logger', $logger));
            }
        }
    }
}
