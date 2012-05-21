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

        $container
            ->setDefinition('solarium.client', new Definition($config['client']['class']))
            ->setArguments(array($this->createOptionsFromConfig(null, $config)));

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
            ->setArguments(array($this->createOptionsFromConfig($name, $config)));
    }

    protected function createOptionsFromConfig($core, $config)
    {
        return array(
            'adapter' => $config['adapter']['class'],
            'adapteroptions' => array(
                'host'    => $config['adapter']['host'],
                'port'    => $config['adapter']['port'],
                'path'    => $config['adapter']['path'],
                'core'    => $core,
                'timeout' => $config['adapter']['timeout'],
            ),
        );
    }
}
