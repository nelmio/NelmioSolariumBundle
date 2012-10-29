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
use Symfony\Component\DependencyInjection\DefinitionDecorator;

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

        foreach ($config['clients'] as $name => $client_options) {
            $client_name = sprintf('solarium.client.%s', $name);
            $adapter_name = sprintf('solarium.client.adapter.%s', $name);

            if (isset($client_options['client_class'])) {
                $client_class = $client_options['client_class'];
                unset($client_options['client_class']);
            } else {
                $client_class = 'Solarium_Client';
            }

            if (isset($client_options['adapter_class'])) {
                $adapter_class = $client_options['adapter_class'];
                unset($client_options['adapter_class']);
            } else {
                $adapter_class = 'Solarium_Client_Adapter_Http';
            }

            $clientDefinition = new Definition($client_class);
            $container->setDefinition($client_name, $clientDefinition);

            if ($name == $default_client) {
                $container->setAlias('solarium.client', $client_name);
            }

            $container
                ->setDefinition($adapter_name, new Definition($adapter_class))
                ->setArguments(array($client_options));

            $adapter = new Reference($adapter_name);
            $container->getDefinition($client_name)->addMethodCall('setAdapter', array($adapter));

            if ($is_debug) {
                $logger = new Reference('solarium.data_collector');
                $container->getDefinition($client_name)->addMethodCall('registerPlugin', array($client_name . '.logger', $logger));
            }
        }
    }
}