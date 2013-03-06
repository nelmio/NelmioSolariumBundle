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

        $this->assertInstanceOf('Solarium\Client', $container->get('solarium.client'));

        $adapter = $container->get('solarium.client')->getAdapter();

        $this->assertInstanceOf('Solarium\Core\Client\Adapter\Http', $adapter);

        $this->assertEquals('127.0.0.1', $adapter->getOption('host'));
        $this->assertEquals('/solr', $adapter->getOption('path'));
        $this->assertEquals('8983', $adapter->getOption('port'));
        $this->assertEquals(5, $adapter->getOption('timeout'));
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

        $this->assertEquals('somehost', $adapter->getOption('host'));
        $this->assertEquals('/solr2', $adapter->getOption('path'));
        $this->assertEquals('80', $adapter->getOption('port'));

        $config = array(
            'clients' => array(
                'default' => array(
                    'dsn' => 'http://somehost:8080/solr/core_path/'
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $adapter = $container->get('solarium.client')->getAdapter();

        $this->assertEquals('somehost', $adapter->getOption('host'));
        $this->assertEquals('/solr/core_path/', $adapter->getOption('path'));
        $this->assertEquals('8080', $adapter->getOption('port'));
    }

    public function testLoadCustomAdapter()
    {
        $adapter = $this->getMock('Solarium\Core\Client\Adapter\Curl');
        $adapterClass = get_class($adapter);

        $config = array(
            'clients' => array(
                'default' => array(
                    'adapter_class' => $adapterClass
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium\Client', $container->get('solarium.client'));
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
        $this->assertInstanceOf('Solarium\Core\Client\Adapter\Http', $container->get('solarium.client')->getAdapter());
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

        $this->assertInstanceOf('Solarium\Client', $container->get('solarium.client.client1'));
        $this->assertInstanceOf('Nelmio\SolariumBundle\Tests\StubClient', $container->get('solarium.client'));
        $this->assertInstanceOf('Nelmio\SolariumBundle\Tests\StubClient', $container->get('solarium.client.client2'));
    }

    public function testDsnAndOtherParamsTogether()
    {
        $config = array(
            'default_client' => 'client2',
            'clients' => array(
                'client1' => array(
                    'dsn' => 'http://localhostBlahBlah/path',
                    'host' => 'localhost',
                    'port' => 123
                ),
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $adapter = $container->get('solarium.client')->getAdapter();

        $this->assertEquals('localhostBlahBlah', $adapter->getOption('host'));
        $this->assertEquals('/path', $adapter->getOption('path'));
        $this->assertEquals('80', $adapter->getOption('port'));
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

class StubClient extends \Solarium\Client
{
}
