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

        $adapter = $container->get('solarium.client')->getAdapter();
        $this->assertInstanceOf('Solarium_Client_Adapter_Http', $adapter);
        $this->assertEquals('127.0.0.1', $adapter->getHost());
        $this->assertEquals('/solr', $adapter->getPath());
        $this->assertEquals('8983', $adapter->getPort());
        $this->assertEquals(5, $adapter->getTimeout());
    }

    public function testLoadDsnConfiguration()
    {
        $config = array(
            'clients' => array(
                'default' => array(
                    'dsn' => 'http://somehost/solr2'
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $adapter = $container->get('solarium.client')->getAdapter();

        $this->assertEquals('somehost', $adapter->getHost());
        $this->assertEquals('/solr2', $adapter->getPath());
        $this->assertEquals('80', $adapter->getPort());

        $config = array(
            'clients' => array(
                'default' => array(
                    'dsn' => 'http://somehost:8080/solr/core_path/'
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $adapter = $container->get('solarium.client')->getAdapter();

        $this->assertEquals('somehost', $adapter->getHost());
        $this->assertEquals('/solr/core_path', $adapter->getPath());
        $this->assertEquals('8080', $adapter->getPort());
    }

    public function testLoadCustomAdapter()
    {
        $adapter = $this->getMock('Solarium_Client_Adapter');
        $adapterClass = get_class($adapter);

        $config = array(
            'clients' => array(
                'default' => array(
                    'adapter_class' => $adapterClass
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium_Client', $container->get('solarium.client'));
        $this->assertInstanceOf($adapterClass, $container->get('solarium.client')->getAdapter());
    }

    public function testLoadCustomClient()
    {
        $config = array(
            'clients' => array(
                'default' => array(
                    'client_class' => 'Nelmio\SolariumBundle\Tests\StubClient'
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Nelmio\SolariumBundle\Tests\StubClient', $container->get('solarium.client'));
        $this->assertInstanceOf('Solarium_Client_Adapter_Http', $container->get('solarium.client')->getAdapter());
    }

    public function testDefaultClient()
    {
        $config = array(
            'default_client' => 'client2',
            'clients' => array(
                'client1' => array(),
                'client2' => array(
                    'client_class' => 'Nelmio\SolariumBundle\Tests\StubClient'
                )
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium_Client', $container->get('solarium.client.client1'));
        $this->assertInstanceOf('Nelmio\SolariumBundle\Tests\StubClient', $container->get('solarium.client'));
        $this->assertInstanceOf('Nelmio\SolariumBundle\Tests\StubClient', $container->get('solarium.client.client2'));
    }

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
