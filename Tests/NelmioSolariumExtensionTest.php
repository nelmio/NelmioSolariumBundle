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

use Nelmio\SolariumBundle\ClientRegistry;
use Nelmio\SolariumBundle\DependencyInjection\NelmioSolariumExtension;
use Nelmio\SolariumBundle\Logger;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Http;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Event\Events;
use Solarium\Core\Plugin\AbstractPlugin;
use Solarium\Plugin\Loadbalancer\Loadbalancer;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NelmioSolariumExtensionTest extends TestCase
{
    public function testLoadEmptyConfiguration()
    {
        $config = array(
            'clients' => array(
                 'default' => array()
             )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(Client::class, $container->get('solarium.client'));

        $adapter = $container->get('solarium.client')->getAdapter();
        $this->assertInstanceOf(Curl::class, $adapter);

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

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

        $this->assertInstanceOf(Client::class, $container->get('solarium.client'));

        $adapter = $container->get('solarium.client')->getAdapter();
        $this->assertInstanceOf(Curl::class, $adapter);

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

        $this->assertEquals('http', $endpoint->getOption('scheme'));
        $this->assertEquals('127.0.0.1', $endpoint->getOption('host'));
        $this->assertEquals('/solr', $endpoint->getOption('path'));
        $this->assertEquals('8983', $endpoint->getOption('port'));
        $this->assertEquals(5, $endpoint->getOption('timeout'));
    }

    public function testLoadCustomAdapter()
    {
        $adapter = $this->createMock(Http::class);
        $adapterClass = get_class($adapter);

        $config = array(
            'clients' => array(
                'default' => array(
                    'adapter_class' => $adapterClass
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(Client::class, $container->get('solarium.client'));
        $this->assertInstanceOf(Http::class, $container->get('solarium.client')->getAdapter());
    }

    public function testLoadCustomClient()
    {
        $config = array(
            'clients' => array(
                'default' => array(
                    'client_class' => StubClient::class
                )
            )
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(StubClient::class, $container->get('solarium.client'));
        $this->assertInstanceOf(Curl::class, $container->get('solarium.client')->getAdapter());
    }

    public function testDefaultClient()
    {
        $config = array(
            'default_client' => 'client2',
            'clients' => array(
                'client1' => array(),
                'client2' => array(
                    'client_class' => StubClient::class
                )
            ),
        );

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(Client::class, $container->get('solarium.client.client1'));
        $this->assertInstanceOf(StubClient::class, $container->get('solarium.client'));
        $this->assertInstanceOf(StubClient::class, $container->get('solarium.client.client2'));
    }

    public function testPlugins()
    {
        $config = array(
          'clients' => array(
            'client' => array(
              'plugins' => array('plugin1' => array('plugin_service' => 'my_plugin'), 'plugin2' => array('plugin_class' => MyPluginClass::class))
            )
          ),
        );

        $container = $this->createCompiledContainerForConfig($config, true, array('my_plugin' => new Definition(MyPluginClass::class)));

        $client = $container->get('solarium.client');
        $plugin1 = $client->getPlugin('plugin1');
        $plugin2 = $client->getPlugin('plugin2');

        $this->assertInstanceOf(MyPluginClass::class, $plugin1);
        $this->assertInstanceOf(MyPluginClass::class, $plugin2);
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

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

        $this->assertEquals('endpoint1', $endpoint->getOption('key'));
        $this->assertEquals('localhost', $endpoint->getOption('host'));
        $this->assertEquals('123', $endpoint->getOption('port'));
        $this->assertEquals('core1', $endpoint->getOption('core'));

        /** @var Endpoint[] $endpoints */
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

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

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

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

        $this->assertEquals('endpoint2', $endpoint->getOption('key'));
        $this->assertEquals('localhost', $endpoint->getOption('host'));
        $this->assertEquals('123', $endpoint->getOption('port'));
        $this->assertEquals('core2', $endpoint->getOption('core'));

        /** @var Endpoint[] $endpoints */
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
        $this->assertInstanceOf(ClientRegistry::class, $clientRegistry);
        $this->assertInstanceOf(Client::class, $clientRegistry->getClient('client1'));
        $this->assertEquals(array('client1', 'client2'), $clientRegistry->getClientNames());

        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotNull($clientRegistry->getClient());
    }

    public function testLogger()
    {
        $config = array();

        $container = $this->createCompiledContainerForConfig($config, true);

        $this->assertInstanceOf(Logger::class, $container->get('solarium.data_collector'));

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $container->get('solarium.client')->getEventDispatcher();
        $this->assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        $preExecuteListeners = $eventDispatcher->getListeners(Events::PRE_EXECUTE_REQUEST);
        $this->assertEquals(1, count($preExecuteListeners));
        $this->assertInstanceOf(Logger::class, $preExecuteListeners[0][0]);
        $this->assertEquals('preExecuteRequest', $preExecuteListeners[0][1]);
        $postExecuteListeners = $eventDispatcher->getListeners(Events::POST_EXECUTE_REQUEST);
        $this->assertEquals(1, count($postExecuteListeners));
        $this->assertInstanceOf(Logger::class, $postExecuteListeners[0][0]);
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

        /** @var Endpoint[] $endpoints */
        $endpoints = $client->getEndpoints();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('localhost', $endpoints['master']->getHost());
        $this->assertEquals(123, $endpoints['master']->getPort());

        $clientPlugins = $client->getPlugins();
        $this->assertArrayHasKey('loadbalancer', $clientPlugins);

        /** @var Loadbalancer $loadBalancerPlugin */
        $loadBalancerPlugin = $client->getPlugin('loadbalancer');
        $this->assertInstanceOf(Loadbalancer::class, $loadBalancerPlugin);
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

    private function createCompiledContainerForConfig($config, $debug = false, $extraServices = array())
    {
        $container = $this->createContainer($debug);
        $container->registerExtension(new FrameworkExtension());
        $container->addDefinitions($extraServices);
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

class StubClient extends Client
{
}

class MyPluginClass extends AbstractPlugin {

}
