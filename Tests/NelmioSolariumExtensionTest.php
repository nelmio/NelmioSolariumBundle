<?php

/*
 * This file is part of the Nelmio SolariumBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\SolariumBundle\Tests;

use Nelmio\SolariumBundle\DependencyInjection\NelmioSolariumExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class NelmioSolariumExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadEmptyConfiguration()
    {
        $config = array(
            'clients' => array(
                 'default' => array()
             )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium_Client', $container->get('solarium.client'));
        $this->assertInstanceOf('Solarium_Client_Adapter_Http', $container->get('solarium.client')->getAdapter());
    }

    public function testLoadCustomAdapter()
    {
        $adapter = $this->getMock('Solarium_Client_Adapter');
        $adapterClass = get_class($adapter);

        $config = array('adapter' => array('class' => $adapterClass));

        $config = array(
            'clients' => array(
                'default' => array(
                    'adapter' => $adapterClass
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium_Client', $container->get('solarium.client'));
        $this->assertInstanceOf($adapterClass, $container->get('solarium.client')->getAdapter());
    }

    /*
    public function testLoadCustomClient()
    {
        $config = array('client' => array('class' => 'Nelmio\SolariumBundle\Tests\StubClient'));

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Nelmio\SolariumBundle\Tests\StubClient', $container->get('solarium.client'));
        $this->assertInstanceOf('Solarium_Client_Adapter_Http', $container->get('solarium.client')->getAdapter());
    }

    public function testLoadWithCores()
    {
        $config = array('adapter' => array('cores' => array('a' => 'core_a', 'b' => 'core_b')));

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium_Client', $container->get('solarium.client.a'));
        $this->assertInstanceOf('Solarium_Client', $container->get('solarium.client.b'));

        $adapterOptions = array(
            'a' => $container->get('solarium.client.a')->getAdapter()->getOptions(),
            'b' => $container->get('solarium.client.b')->getAdapter()->getOptions(),
        );

        $this->assertEquals('core_a', $adapterOptions['a']['core']);
        $this->assertEquals('core_b', $adapterOptions['b']['core']);
    }
    */

    private function createCompiledContainerForConfig($config)
    {
        $container = $this->createContainer();
        $container->registerExtension(new NelmioSolariumExtension());
        $container->loadFromExtension('nelmio_solarium', $config);
        $this->compileContainer($container);

        return $container;
    }

    private function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__,
            'kernel.charset'   => 'UTF-8',
            'kernel.debug'     => false,
        )));

        return $container;
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();
    }
}

class StubClient extends \Solarium_Client
{
}
