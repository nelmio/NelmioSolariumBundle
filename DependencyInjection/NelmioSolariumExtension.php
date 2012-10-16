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
        $loader        = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        print_r($config);

        foreach ($config['clients'] as $name => $client_data) {
            if ($name == 'default') {
                $clientName = 'solarium.client';
                $adapterName = 'solarium.client.adapter';
            } else {
                $clientName = sprintf('solarium.client.%s', $name);
                $adapterName = 'solarium.adapter.' . $name;
            }

            $clientDefinition = new Definition($client_data['class']);
            $container
                ->setDefinition($clientName, $clientDefinition)
                ->setArguments(array($client_data));

            if (isset($client_data['adapter'])) {
                //$arguments = array($this->createAdapterOptionsFromConfig($name, $config));
                $arguments = array();
                $container
                    ->setDefinition($adapterName, new Definition($client_data['adapter']))
                    ->setArguments($arguments)
                ;

                $adapter = new Reference($adapterName);
                $container->getDefinition($clientName)->addMethodCall('setAdapter', array($adapter));
            }
        }

        /*
        $this->createClient(null, $container, $config);
        if (isset($config['adapter']['cores'])) {
            foreach ($config['adapter']['cores'] as $name => $path) {
                $this->createClient($name, $container, $config);
            }
        }
        */
        if (true === $container->getParameter('kernel.debug')) {
            $loader->load('logger.xml');
        }
    }

    protected function createClient($name, ContainerBuilder $container, array $config)
    {
        if (null === $name) {
            $clientName = 'solarium.client';
            $adapterName = 'solarium.adapter.default';
        } else {
            $clientName = sprintf('solarium.client.%s', $name);
            $adapterName = 'solarium.adapter.core.' . $name;
        }
        $clientDefinition = new Definition($config['client']['class']);
        $container
            ->setDefinition($clientName, $clientDefinition)
            ->setArguments(array());

        $debug = $container->getParameter('kernel.debug');
        $arguments = array($this->createAdapterOptionsFromConfig($name, $config));
        $container
            ->setDefinition($adapterName, new Definition($config['adapter']['class']))
            ->setArguments($arguments);

        $adapter = new Reference($adapterName);
        $container->getDefinition($clientName)->addMethodCall('setAdapter', array($adapter));

        if ($debug) {
            $logger = new Reference('solarium.data_collector');
            $container->getDefinition($clientName)->addMethodCall('registerPlugin', array($clientName . '.logger', $logger));
        }
    }

    protected function createAdapterOptionsFromConfig($core, $config)
    {
        return array(
            'host'    => $config['adapter']['host'],
            'port'    => $config['adapter']['port'],
            'path'    => $config['adapter']['path'],
            'core'    => (null !== $core) ? $config['adapter']['cores'][$core] : null,
            'timeout' => $config['adapter']['timeout'],
        );
    }
}
