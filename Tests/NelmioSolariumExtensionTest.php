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
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
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
        $this->assertInstanceOf('Solarium\Core\Client\Adapter\Curl', $adapter);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('http', $endpoint->getOption('scheme'));
        $this->assertEquals('127.0.0.1', $endpoint->getOption('host'));
        $this->assertEquals('/solr', $endpoint->getOption('path'));
        $this->assertEquals('8983', $endpoint->getOption('port'));
        $this->assertEquals(5, $endpoint->getOption('timeout'));
    }

    public function testNoClients()
    {
        $config = array(
            'endpoints' => array(
                 'default' => array()
             )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf('Solarium\Client', $container->get('solarium.client'));

        $adapter = $container->get('solarium.client')->getAdapter();
        $this->assertInstanceOf('Solarium\Core\Client\Adapter\Curl', $adapter);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('http', $endpoint->getOption('scheme'));
        $this->assertEquals('127.0.0.1', $endpoint->getOption('host'));
        $this->assertEquals('/solr', $endpoint->getOption('path'));
        $this->assertEquals('8983', $endpoint->getOption('port'));
        $this->assertEquals(5, $endpoint->getOption('timeout'));
    }

    public function testLoadDsnConfiguration()
    {
        $config = array(
            'endpoints' => array(
                'default' => array(
                    'dsn' => 'http://somehost/solr2'
                )
            ),
            'clients' => array(
                'default' => array()
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('somehost', $endpoint->getOption('host'));
        $this->assertEquals('/solr2', $endpoint->getOption('path'));
        $this->assertEquals('80', $endpoint->getOption('port'));

        $config = array(
            'endpoints' => array(
                'default' => array(
                    'dsn' => 'http://somehost:8080/solr/core_path/'
                )
            ),
            'clients' => array(
                'default' => array()
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('http', $endpoint->getOption('scheme'));
        $this->assertEquals('somehost', $endpoint->getOption('host'));
        $this->assertEquals('/solr/core_path', $endpoint->getOption('path'));
        $this->assertEquals('8080', $endpoint->getOption('port'));
    }

    public function testLoadCustomAdapter()
    {
        $adapter = $this->getMock('Solarium\Core\Client\Adapter\Http');
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
        $this->assertInstanceOf('Solarium\Core\Client\Adapter\Curl', $container->get('solarium.client')->getAdapter());
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
            'endpoints' => array(
                'endpoint1' => array(
                    'dsn' => 'http://localhostBlahBlah/path',
                    'host' => 'localhost',
                    'port' => 123
                )
            ),
            'clients' => array(
                'client1' => array()
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('http', $endpoint->getOption('scheme'));
        $this->assertEquals('localhostBlahBlah', $endpoint->getOption('host'));
        $this->assertEquals('/path', $endpoint->getOption('path'));
        $this->assertEquals('80', $endpoint->getOption('port'));
    }

    public function testDsnAndOtherParamsWithHttpsTogether()
    {
        $config = array(
            'default_client' => 'client2',
            'endpoints' => array(
                'endpoint1' => array(
                    'dsn' => 'https://localhostBlahBlah/path',
                    'host' => 'localhost',
                    'port' => 123
                )
            ),
            'clients' => array(
                'client1' => array()
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('https', $endpoint->getOption('scheme'));
        $this->assertEquals('localhostBlahBlah', $endpoint->getOption('host'));
        $this->assertEquals('/path', $endpoint->getOption('path'));
        $this->assertEquals('80', $endpoint->getOption('port'));
    }

    public function testEndpoints()
    {
        $config = array(
            'endpoints' => array(
                'endpoint1' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ),
                'endpoint2' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                ),
                'endpoint3' => array(
                    'scheme' => 'https',
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core3',
                )
            ),
            'clients' => array(
                'client1' => array()
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('endpoint1', $endpoint->getOption('key'));
        $this->assertEquals('localhost', $endpoint->getOption('host'));
        $this->assertEquals('123', $endpoint->getOption('port'));
        $this->assertEquals('core1', $endpoint->getOption('core'));

        $endpoints = $container->get('solarium.client')->getEndpoints();

        $this->assertEquals(3, count($container->get('solarium.client')->getEndpoints()));

        $this->assertTrue(isset($endpoints['endpoint1']));
        $this->assertEquals('endpoint1', $endpoints['endpoint1']->getOption('key'));
        $this->assertEquals('http', $endpoints['endpoint1']->getOption('scheme'));
        $this->assertEquals('localhost', $endpoints['endpoint1']->getOption('host'));
        $this->assertEquals('123', $endpoints['endpoint1']->getOption('port'));
        $this->assertEquals('core1', $endpoints['endpoint1']->getOption('core'));

        $this->assertTrue(isset($endpoints['endpoint2']));
        $this->assertEquals('endpoint2', $endpoints['endpoint2']->getOption('key'));
        $this->assertEquals('http', $endpoints['endpoint2']->getOption('scheme'));
        $this->assertEquals('localhost', $endpoints['endpoint2']->getOption('host'));
        $this->assertEquals('123', $endpoints['endpoint2']->getOption('port'));
        $this->assertEquals('core2', $endpoints['endpoint2']->getOption('core'));

        $this->assertTrue(isset($endpoints['endpoint3']));
        $this->assertEquals('endpoint3', $endpoints['endpoint3']->getOption('key'));
        $this->assertEquals('https', $endpoints['endpoint3']->getOption('scheme'));
        $this->assertEquals('localhost', $endpoints['endpoint3']->getOption('host'));
        $this->assertEquals('123', $endpoints['endpoint3']->getOption('port'));
        $this->assertEquals('core3', $endpoints['endpoint3']->getOption('core'));

    }

    public function testSpecificEndpoints()
    {
        $config = array(
            'endpoints' => array(
                'endpoint1' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ),
                'endpoint2' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                )
            ),
            'clients' => array(
                'client1' => array(
                    'endpoints' => array('endpoint2'),
                )
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals(1, count($container->get('solarium.client')->getEndpoints()));

        $this->assertEquals('endpoint2', $endpoint->getOption('key'));
        $this->assertEquals('http', $endpoint->getOption('scheme'));
        $this->assertEquals('localhost', $endpoint->getOption('host'));
        $this->assertEquals('123', $endpoint->getOption('port'));
        $this->assertEquals('core2', $endpoint->getOption('core'));
    }

    public function testDefaultEndpoint()
    {
        $config = array(
            'endpoints' => array(
                'endpoint1' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ),
                'endpoint2' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                )
            ),
            'clients' => array(
                'client1' => array(
                    'default_endpoint' => 'endpoint2',
                )
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $endpoint = $container->get('solarium.client')->getEndpoint();

        $this->assertEquals('endpoint2', $endpoint->getOption('key'));
        $this->assertEquals('localhost', $endpoint->getOption('host'));
        $this->assertEquals('123', $endpoint->getOption('port'));
        $this->assertEquals('core2', $endpoint->getOption('core'));

        $endpoints = $container->get('solarium.client')->getEndpoints();

        $this->assertEquals(2, count($container->get('solarium.client')->getEndpoints()));

        $this->assertTrue(isset($endpoints['endpoint1']));
        $this->assertEquals('endpoint1', $endpoints['endpoint1']->getOption('key'));
        $this->assertEquals('http', $endpoints['endpoint1']->getOption('scheme'));
        $this->assertEquals('localhost', $endpoints['endpoint1']->getOption('host'));
        $this->assertEquals('123', $endpoints['endpoint1']->getOption('port'));
        $this->assertEquals('core1', $endpoints['endpoint1']->getOption('core'));

        $this->assertTrue(isset($endpoints['endpoint2']));
        $this->assertEquals('endpoint2', $endpoints['endpoint2']->getOption('key'));
        $this->assertEquals('http', $endpoints['endpoint2']->getOption('scheme'));
        $this->assertEquals('localhost', $endpoints['endpoint2']->getOption('host'));
        $this->assertEquals('123', $endpoints['endpoint2']->getOption('port'));
        $this->assertEquals('core2', $endpoints['endpoint2']->getOption('core'));
    }

    public function testClientRegistry()
    {
        $config = array(
            'endpoints' => array(
                'endpoint1' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ),
                'endpoint2' => array(
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                )
            ),
            'clients' => array(
                'client1' => array(
                    'endpoints' => array('endpoint1'),
                ),
                'client2' => array(
                    'endpoints' => array('endpoint2'),
                )
            ),
        );
        $container = $this->createCompiledContainerForConfig($config);
        $clientRegistry = $container->get('solarium.client_registry');
        $this->assertInstanceOf('Nelmio\SolariumBundle\ClientRegistry', $clientRegistry);
        $this->assertInstanceOf('Solarium\Client', $clientRegistry->getClient('client1'));
        $this->assertEquals(array('client1', 'client2'), $clientRegistry->getClientNames());

        $this->setExpectedException('InvalidArgumentException');
        $this->assertNotNull($clientRegistry->getClient());
    }

    public function testLogger()
    {
        $config = array();

        $container = $this->createCompiledContainerForConfig($config, true);

        $this->assertInstanceOf('Nelmio\SolariumBundle\Logger', $container->get('solarium.data_collector'));

        $eventDispatcher = $container->get('solarium.client')->getEventDispatcher();
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventDispatcherInterface', $eventDispatcher);
        $preExecuteListeners = $eventDispatcher->getListeners(\Solarium\Core\Event\Events::PRE_EXECUTE_REQUEST);
        $this->assertEquals(1, count($preExecuteListeners));
        $this->assertInstanceOf('Nelmio\SolariumBundle\Logger', $preExecuteListeners[0][0]);
        $this->assertEquals('preExecuteRequest', $preExecuteListeners[0][1]);
        $postExecuteListeners = $eventDispatcher->getListeners(\Solarium\Core\Event\Events::POST_EXECUTE_REQUEST);
        $this->assertEquals(1, count($postExecuteListeners));
        $this->assertInstanceOf('Nelmio\SolariumBundle\Logger', $postExecuteListeners[0][0]);
        $this->assertEquals('postExecuteRequest', $postExecuteListeners[0][1]);
    }

    public function testLoadBalancer()
    {
        $config = array(
            'endpoints' => array(
                'master' => array(
                    'host' => 'localhost',
                    'port' => 123,
                ),
                'slave1' => array(
                    'host' => 'localhost',
                    'port' => 124,
                ),
                'slave2' => array(
                    'host' => 'localhost',
                    'port' => 125,
                )
            ),
            'clients' => array(
                'client1' => array(
                    'endpoints' => ['master'],
                    'load_balancer' => array(
                        'endpoints' => array('slave1', 'slave2' => 5),
                        'blocked_query_types' => array('ping'),
                    ),
                )
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $client = $container->get('solarium.client');

        $endpoints = $client->getEndpoints();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('localhost', $endpoints['master']->getHost());
        $this->assertEquals(123, $endpoints['master']->getPort());

        $clientPlugins = $client->getPlugins();
        $this->assertArrayHasKey('loadbalancer', $clientPlugins);

        $loadBalancerPlugin = $client->getPlugin('loadbalancer');
        $this->assertEquals($loadBalancerPlugin, $container->get('solarium.client.client1.load_balancer'));

        $loadBalancedEndpoints = $loadBalancerPlugin->getEndpoints();
        $this->assertCount(2, $loadBalancedEndpoints);
        $this->assertEquals(
            array(
                'slave1' => 1,
                'slave2' => 5,
            ),
            $loadBalancedEndpoints
        );

    }

    private function createCompiledContainerForConfig($config, $debug = false)
    {
        $container = $this->createContainer($debug);
        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new NelmioSolariumExtension());
        $container->loadFromExtension('framework', array());
        $container->loadFromExtension('nelmio_solarium', $config);
        $this->compileContainer($container);

        return $container;
    }

    private function createContainer($debug = false)
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir'       => __DIR__,
            'kernel.charset'         => 'UTF-8',
            'kernel.debug'           => $debug,
            'kernel.container_class' => 'dummy',
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
