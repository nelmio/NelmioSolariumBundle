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
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class NelmioSolariumExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $container->setParameter('solarium.client.class', $config['client']['class']);

        if (!empty($config['adapter'])) {
            $options = array(
                'adapter'           => $config['adapter']['class'],
                'adapteroptions'    => array(
                    'host'      => $config['adapter']['host'],
                    'port'      => $config['adapter']['port'],
                    'path'      => $config['adapter']['path'],
                    'core'      => $config['adapter']['core'],
                    'timeout'   => $config['adapter']['timeout'],
                ),
            );
            $container->setParameter('solarium.client.options', $options);

            $loader->load('services.yml');
        }
    }
}
