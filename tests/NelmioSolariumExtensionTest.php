<?php

declare(strict_types=1);

/**
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
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Adapter\Http;
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
    public function testLoadEmptyConfiguration(): void
    {
        $config = [
            'clients' => [
                'default' => [],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(Client::class, $container->get('solarium.client'));

        $adapter = $container->get('solarium.client')->getAdapter();
        $this->assertInstanceOf(Curl::class, $adapter);

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

        $this->assertEquals('http', $endpoint->getScheme());
        $this->assertEquals('127.0.0.1', $endpoint->getHost());
        $this->assertEquals('', $endpoint->getPath());
        $this->assertEquals(8983, $endpoint->getPort());
    }

    public function testNoClients(): void
    {
        $config = [
            'endpoints' => [
                'default' => [],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(Client::class, $container->get('solarium.client'));

        $adapter = $container->get('solarium.client')->getAdapter();
        $this->assertInstanceOf(Curl::class, $adapter);

        /** @var Endpoint $endpoint */
        $endpoint = $container->get('solarium.client')->getEndpoint();
        $this->assertInstanceOf(Endpoint::class, $endpoint);

        $this->assertEquals('http', $endpoint->getScheme());
        $this->assertEquals('127.0.0.1', $endpoint->getHost());
        $this->assertEquals('', $endpoint->getPath());
        $this->assertEquals(8983, $endpoint->getPort());
    }

    public function testLoadCustomClient(): void
    {
        $config = [
            'clients' => [
                'default' => [
                    'client_class' => StubClient::class,
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(StubClient::class, $container->get('solarium.client'));
        $this->assertInstanceOf(Curl::class, $container->get('solarium.client')->getAdapter());
    }

    public function testDefaultClient(): void
    {
        $config = [
            'default_client' => 'client2',
            'clients' => [
                'client1' => [],
                'client2' => [
                    'client_class' => StubClient::class,
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        $this->assertInstanceOf(Client::class, $container->get('solarium.client.client1'));
        $this->assertInstanceOf(StubClient::class, $container->get('solarium.client'));
        $this->assertInstanceOf(StubClient::class, $container->get('solarium.client.client2'));
    }

    public function testPlugins(): void
    {
        $config = [
            'clients' => [
                'client' => [
                    'plugins' => ['plugin1' => ['plugin_service' => 'my_plugin'], 'plugin2' => ['plugin_class' => MyPluginClass::class]],
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config, true, ['my_plugin' => new Definition(MyPluginClass::class)]);

        /** @var Client $client */
        $client = $container->get('solarium.client');
        $plugin1 = $client->getPlugin('plugin1');
        $plugin2 = $client->getPlugin('plugin2');

        $this->assertInstanceOf(MyPluginClass::class, $plugin1);
        $this->assertInstanceOf(MyPluginClass::class, $plugin2);
    }

    public function testEndpoints(): void
    {
        $config = [
            'endpoints' => [
                'endpoint1' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ],
                'endpoint2' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                ],
                'endpoint3' => [
                    'scheme' => 'https',
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core3',
                ],
            ],
            'clients' => [
                'client1' => [],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        /** @var Client $client */
        $client = $container->get('solarium.client');
        $endpoint = $client->getEndpoint();

        $this->assertEquals('endpoint1', $endpoint->getKey());
        $this->assertEquals('localhost', $endpoint->getHost());
        $this->assertEquals(123, $endpoint->getPort());
        $this->assertEquals('core1', $endpoint->getCore());

        $endpoints = $client->getEndpoints();
        $this->assertCount(3, $endpoints);

        $this->assertTrue(isset($endpoints['endpoint1']));
        $this->assertEquals('endpoint1', $endpoints['endpoint1']->getKey());
        $this->assertEquals('http', $endpoints['endpoint1']->getScheme());
        $this->assertEquals('localhost', $endpoints['endpoint1']->getHost());
        $this->assertEquals(123, $endpoints['endpoint1']->getPort());
        $this->assertEquals('core1', $endpoints['endpoint1']->getCore());

        $this->assertTrue(isset($endpoints['endpoint2']));
        $this->assertEquals('endpoint2', $endpoints['endpoint2']->getKey());
        $this->assertEquals('http', $endpoints['endpoint2']->getScheme());
        $this->assertEquals('localhost', $endpoints['endpoint2']->getHost());
        $this->assertEquals(123, $endpoints['endpoint2']->getPort());
        $this->assertEquals('core2', $endpoints['endpoint2']->getCore());

        $this->assertTrue(isset($endpoints['endpoint3']));
        $this->assertEquals('endpoint3', $endpoints['endpoint3']->getKey());
        $this->assertEquals('https', $endpoints['endpoint3']->getScheme());
        $this->assertEquals('localhost', $endpoints['endpoint3']->getHost());
        $this->assertEquals(123, $endpoints['endpoint3']->getPort());
        $this->assertEquals('core3', $endpoints['endpoint3']->getCore());
    }

    public function testSpecificEndpoints(): void
    {
        $config = [
            'endpoints' => [
                'endpoint1' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ],
                'endpoint2' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                ],
            ],
            'clients' => [
                'client1' => [
                    'endpoints' => ['endpoint2'],
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        /** @var Client $client */
        $client = $container->get('solarium.client');
        $endpoint = $client->getEndpoint();

        $this->assertCount(1, $client->getEndpoints());

        $this->assertEquals('endpoint2', $endpoint->getKey());
        $this->assertEquals('http', $endpoint->getScheme());
        $this->assertEquals('localhost', $endpoint->getHost());
        $this->assertEquals(123, $endpoint->getPort());
        $this->assertEquals('core2', $endpoint->getCore());
    }

    public function testDefaultEndpoint(): void
    {
        $config = [
            'endpoints' => [
                'endpoint1' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ],
                'endpoint2' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                    'path' => '/custom_prefix',
                ],
            ],
            'clients' => [
                'client1' => [
                    'default_endpoint' => 'endpoint2',
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        /** @var Client $client */
        $client = $container->get('solarium.client');
        $endpoint = $client->getEndpoint();

        $this->assertEquals('endpoint2', $endpoint->getKey());
        $this->assertEquals('localhost', $endpoint->getHost());
        $this->assertEquals(123, $endpoint->getPort());
        $this->assertEquals('core2', $endpoint->getCore());

        $endpoints = $client->getEndpoints();

        $this->assertCount(2, $client->getEndpoints());

        $this->assertTrue(isset($endpoints['endpoint1']));
        $this->assertEquals('endpoint1', $endpoints['endpoint1']->getKey());
        $this->assertEquals('http', $endpoints['endpoint1']->getScheme());
        $this->assertEquals('localhost', $endpoints['endpoint1']->getHost());
        $this->assertEquals(123, $endpoints['endpoint1']->getPort());
        $this->assertEquals('core1', $endpoints['endpoint1']->getCore());
        $this->assertEquals('http://localhost:123/solr/core1/', $endpoints['endpoint1']->getCoreBaseUri());

        $this->assertTrue(isset($endpoints['endpoint2']));
        $this->assertEquals('endpoint2', $endpoints['endpoint2']->getKey());
        $this->assertEquals('http', $endpoints['endpoint2']->getScheme());
        $this->assertEquals('localhost', $endpoints['endpoint2']->getHost());
        $this->assertEquals(123, $endpoints['endpoint2']->getPort());
        $this->assertEquals('core2', $endpoints['endpoint2']->getCore());
        $this->assertEquals('/custom_prefix', $endpoints['endpoint2']->getPath());
        $this->assertEquals('http://localhost:123/custom_prefix/solr/core2/', $endpoints['endpoint2']->getCoreBaseUri());
    }

    public function testClientRegistry(): void
    {
        $config = [
            'endpoints' => [
                'endpoint1' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core1',
                ],
                'endpoint2' => [
                    'host' => 'localhost',
                    'port' => 123,
                    'core' => 'core2',
                ],
            ],
            'clients' => [
                'client1' => [
                    'endpoints' => ['endpoint1'],
                ],
                'client2' => [
                    'endpoints' => ['endpoint2'],
                ],
            ],
        ];
        $container = $this->createCompiledContainerForConfig($config);
        $clientRegistry = $container->get('solarium.client_registry');
        $this->assertInstanceOf(ClientRegistry::class, $clientRegistry);
        $this->assertInstanceOf(Client::class, $clientRegistry->getClient('client1'));
        $this->assertEquals(['client1', 'client2'], $clientRegistry->getClientNames());

        $this->expectException(\InvalidArgumentException::class);
        $clientRegistry->getClient();
    }

    public function testLogger(): void
    {
        $config = [];

        $container = $this->createCompiledContainerForConfig($config, true);

        $this->assertInstanceOf(Logger::class, $container->get('solarium.data_collector'));

        /** @var Client $client */
        $client = $container->get('solarium.client');

        $eventDispatcher = $client->getEventDispatcher();
        $this->assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        $preExecuteListeners = $eventDispatcher->getListeners(Events::PRE_EXECUTE_REQUEST);
        $this->assertCount(1, $preExecuteListeners);
        /* @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $this->assertInstanceOf(Logger::class, $preExecuteListeners[0][0]);
        $this->assertSame('preExecuteRequest', $preExecuteListeners[0][1]);
        $postExecuteListeners = $eventDispatcher->getListeners(Events::POST_EXECUTE_REQUEST);
        $this->assertCount(1, $postExecuteListeners);
        /* @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $this->assertInstanceOf(Logger::class, $postExecuteListeners[0][0]);
        $this->assertSame('postExecuteRequest', $postExecuteListeners[0][1]);
    }

    public function testLoadBalancer(): void
    {
        $config = [
            'endpoints' => [
                'master' => [
                    'host' => 'localhost',
                    'port' => 123,
                ],
                'slave1' => [
                    'host' => 'localhost',
                    'port' => 124,
                ],
                'slave2' => [
                    'host' => 'localhost',
                    'port' => 125,
                ],
            ],
            'clients' => [
                'client1' => [
                    'endpoints' => ['master'],
                    'load_balancer' => [
                        'endpoints' => ['slave1', 'slave2' => 5],
                        'blocked_query_types' => ['ping'],
                    ],
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        /** @var Client $client */
        $client = $container->get('solarium.client');

        $endpoints = $client->getEndpoints();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('localhost', $endpoints['master']->getHost());
        $this->assertEquals(123, $endpoints['master']->getPort());

        $clientPlugins = $client->getPlugins();
        $this->assertArrayHasKey('loadbalancer', $clientPlugins);

        /** @var Loadbalancer $loadBalancerPlugin */
        $loadBalancerPlugin = $client->getPlugin('loadbalancer');
        $this->assertInstanceOf(Loadbalancer::class, $loadBalancerPlugin);

        $loadBalancedEndpoints = $loadBalancerPlugin->getEndpoints();
        $this->assertCount(2, $loadBalancedEndpoints);
        $this->assertEquals(
            [
                'slave1' => 1,
                'slave2' => 5,
            ],
            $loadBalancedEndpoints
        );
    }

    public function testAdapterTimeout(): void
    {
        $config = [
            'clients' => [
                'default' => [
                    'adapter_timeout' => 10,
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config);

        /** @var Client $client */
        $client = $container->get('solarium.client');

        $this->assertInstanceOf(Curl::class, $client->getAdapter());
        $this->assertSame(10, $client->getAdapter()->getTimeout());
    }

    public function testAdapterService(): void
    {
        $config = [
            'clients' => [
                'default' => [
                    'adapter_service' => 'foo',
                ],
            ],
        ];

        $container = $this->createCompiledContainerForConfig($config, false, ['foo' => new Definition(Http::class)]);

        /** @var Client $client */
        $client = $container->get('solarium.client');

        $this->assertInstanceOf(Http::class, $client->getAdapter());
    }

    public function testAdapterTimeoutAndServiceNotSupported(): void
    {
        $config = [
            'clients' => [
                'default' => [
                    'adapter_timeout' => 10,
                    'adapter_service' => 'foo',
                ],
            ],
        ];

        $this->expectExceptionMessage('Setting "adapter_timeout" is only supported for the default adapter and not in combination with "adapter_service"');
        $this->createCompiledContainerForConfig($config);
    }

    /**
     * @param array<string, mixed>      $config
     * @param array<string, Definition> $extraServices
     */
    private function createCompiledContainerForConfig(array $config, bool $debug = false, array $extraServices = []): ContainerBuilder
    {
        $container = $this->createContainer($debug);
        $container->registerExtension(new FrameworkExtension());
        $container->addDefinitions($extraServices);
        $container->registerExtension(new NelmioSolariumExtension());
        $container->loadFromExtension('framework', [
            'http_method_override' => false,
        ]);
        $container->loadFromExtension('nelmio_solarium', $config);
        $this->compileContainer($container);

        return $container;
    }

    private function createContainer(bool $debug = false): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => __DIR__,
            'kernel.charset' => 'UTF-8',
            'kernel.debug' => $debug,
            'kernel.container_class' => 'dummy',
            'kernel.project_dir' => __DIR__,
            'kernel.build_dir' => __DIR__,
            'debug.file_link_format' => 'foo',
            'env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)' => '',
            'env(default::SYMFONY_TRUSTED_HOSTS)' => '',
            'env(default::SYMFONY_TRUSTED_PROXIES)' => '',
            'env(default::SYMFONY_TRUSTED_HEADERS)' => '',
        ]));
    }

    private function compileContainer(ContainerBuilder $container): void
    {
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();
    }
}

class StubClient extends Client
{
}

class MyPluginClass extends AbstractPlugin
{
}
