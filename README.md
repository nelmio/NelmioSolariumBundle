# NelmioSolarium Bundle

[![Latest Version](https://img.shields.io/github/release/nelmio/NelmioSolariumBundle.svg?style=flat-square)](https://github.com/nelmio/NelmioSolariumBundle/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/nelmio/solarium-bundle.svg?style=flat-square)](https://packagist.org/packages/nelmio/solarium-bundle)

## About

The NelmioSolariumBundle provides integration with the [solarium](http://www.solarium-project.org)
solr client.

## Installation

Require the `nelmio/solarium-bundle` package in your composer.json and update your dependencies.

```sh
$ composer require nelmio/solarium-bundle
```

Add the NelmioSolariumBundle to your AppKernel.php

```php
public function registerBundles()
{
    $bundles = array(
        ...
        new Nelmio\SolariumBundle\NelmioSolariumBundle(),
        ...
    );
    ...
}
```

## Basic configuration

Quick-start configuration:

```yaml
nelmio_solarium: ~
```

Gives you a Solarium_Client service with default options (`http://localhost:8983/solr`)

```php
    $client = $this->get('solarium.client');
```

Configure your endpoints in config.yml:

```yaml
nelmio_solarium:
    endpoints:
        default:
            scheme: http
            host: localhost
            port: 8983
            path: /solr
            core: active
    clients:
        default:
            endpoints: [default]
```

If you only have one endpoint, the ```client``` section is not necessary

## Usage

```php
$client = $this->get('solarium.client');
$select = $client->createSelect();
$select->setQuery('foo');
$results = $client->select($select);
```

For more information see the [Solarium documentation](http://solarium.readthedocs.io/en/stable/).

## Multiple clients and endpoints

```yaml
nelmio_solarium:
    endpoints:
        default:
            host: 192.168.1.2
        another:
            host: 192.168.1.3
    clients:
        default:
            endpoints: [default]
        another:
            endpoints: [another]
```

```php
    $defaultClient = $this->get('solarium.client');
    $anotherClient = $this->get('solarium.client.another');
```

You may also change `default` name with your own, but don't forget change `default_client` option if you want to get access to
`solarium.client` service

```yaml
nelmio_solarium:
    default_client: firstOne
    endpoints:
        firstOne:
            host: 192.168.1.2
        anotherOne:
            host: 192.168.1.3
    clients:
        firstOne:
            endpoints: [firstOne]
        anotherOne:
            endpoints: [anotherOne]
```

```php
    $firstOneClient = $this->get('solarium.client');
    //or
    $firstOneClient = $this->get('solarium.client.firstOne');

    $anotherOneClient = $this->get('solarium.client.anotherOne');
```

Starting from Solarium 3.x you can also have multiple endpoints within the same client

```yaml
nelmio_solarium:
    endpoints:
        default:
            host: 192.168.1.2
        another:
            host: 192.168.1.3
    # if you are using all the endpoints, the clients section is not necessary
    clients:
        default:
            endpoints: [default, another]
```

You can also set which is the default endpoint

```yaml
nelmio_solarium:
    endpoints:
        default:
            host: 192.168.1.2
        another:
            host: 192.168.1.3
    clients:
        default:
            endpoints: [default, another]
            default_endpoint: another
```

You can combine both multiple client and endpoints too

```yaml
nelmio_solarium:
    endpoints:
        one:
            host: 192.168.1.2
        two:
            host: 192.168.1.3
        three:
            host: 192.168.1.4
    clients:
        firstOne:
            endpoints: [one, two]
            default_endpoint: two
        secondOne:
            endpoints: [two, three]
            default_endpoint: three
```

## Client registry

You can also use the service ```solarium.client_registry``` to access the clients you have configured using the names you have used in the configuration (with the example above):

```php
$registry = $this->get('solarium.client_registry');
$firstOne = $registry->getClient('firstOne');
$secondOne = $registry->getClient('secondOne');
```

or if you have configured a default client

```php
$registry = $this->get('solarium.client_registry');
$default = $registry->getClient();
```
## Plugins

Solarium works with plugins. If you want to use your own plugins, you can register a plugin in the bundle configuration either with a service id or the plugin class:

```yaml
nelmio_solarium:
    clients:
        default:
            plugins:
                test_plugin_service:
                    plugin_service: plugin _service_id
                test_plugin_classname:
                    plugin_class: Some\Plugin\TestPlugin
```

## Overriding the Client class

To change the client class, you can set the client_class option:

```yaml
nelmio_solarium:
    clients:
        default:
            client_class: Solarium\Core\Client
```

## Customizing the HTTP Adapter used by the Client

If you need to customize the Adapter that is used by the Client to perform HTTP requests to Solr then you can use the `adapter_service` option to specify the ID of a symfony service to be used as an adapter:

```yaml
nelmio_solarium:
    clients:
        default:
            adapter_service: 'my.custom.adapter.service'
```

## HTTP Request timeout

If you are using the default adapter (`Curl`) and did not customize the `adapter_service` then you can use the `adapter_timeout` option to customize the timeout.
Solarium uses a timeout of 5 seconds by default.

```yaml
nelmio_solarium:
    clients:
        default:
            adapter_timeout: 10
```

## Loadbalancer Plugin

Solarium ships with a loadbalancer plugin which can be configured via the `load_balancer` option on the client level.

Passing a list of endpoints will assign equal weights of 1 and randomly pick an endpoint for each request.

```yaml
nelmio_solarium:
    endpoints:
        one:
            host: 192.168.1.2
        two:
            host: 192.168.1.3
        three:
            host: 192.168.1.4
    clients:
        default:
            load_balancer:
                enabled: true
                endpoints: [ one, two, three ] # will assign equal weights of 1
```

You can also assign different weights (integers >= 1) to the endpoints to have a more fine-grained control over the loadbalancing. 
There are also options to customize the blocked query types and the default endpoint to use for those queries.

```yaml
nelmio_solarium:
    endpoints:
        one:
            host: 192.168.1.2
        two:
            host: 192.168.1.3
        three:
            host: 192.168.1.4
    clients:
        default:
            default_endpoint: two # the default endpoint to use for blocked query types 
            load_balancer:
                enabled: true
                blocked_query_types: [ 'select', 'update' ] # default is [ 'update' ]
                endpoints: 
                    one: 1
                    two: 2 # this endpoint will be used twice as often as the other two       
                    three: 1
```

Also see the Solarium documentation for the loadbalancer plugin: https://github.com/solariumphp/solarium/blob/master/docs/plugins.md#loadbalancer-plugin

## License

Released under the MIT License, see LICENSE.
