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
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class NelmioSolariumExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('services.xml');

        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $configs);

        $container
            ->setDefinition('solarium.client', new Definition($config['client']['class']))
            ->setArguments(
                array(
                    $this->createOptions(
                        $config['adapter']['class'],
                        $config['adapter']['host'],
                        $config['adapter']['port'],
                        $config['adapter']['path'],
                        null,
                        $config['adapter']['timeout']
                    )
                )
            );

        if (isset($config['adapter']['cores'])) {
            foreach ($config['adapter']['cores'] as $name => $path) {
                $this->loadCore($name, $path, $container, $config);
            }
        }
    }

    protected function loadCore($name, $path, ContainerBuilder $container, array $config)
    {
        $container
            ->setDefinition(sprintf('solarium.client.%s', $name), new Definition($config['client']['class']))
            ->setArguments(
                array(
                    $this->createOptions(
                        $config['adapter']['class'],
                        $config['adapter']['host'],
                        $config['adapter']['port'],
                        $config['adapter']['path'],
                        $name,
                        $config['adapter']['timeout']
                    )
                )
            );
    }

    protected function createOptions($class = null, $host = null, $port = null, $path = null, $core = null,  $timeout = null)
    {
        return array(
            'adapter' => $class,
            'adapteroptions' => array(
                'host'    => $host,
                'port'    => $port,
                'path'    => $path,
                'core'    => $core,
                'timeout' => $timeout
                )
        );
    }
}
