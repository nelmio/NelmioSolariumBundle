# NelmioSolariumBundle

## About

The NelmioSolariumBundle provides integration with the [solarium](http://www.solarium-project.org)
solr client.

## Installation

Add NelmioSolariumBundle in your composer.json:

```js
{
    "require": {
        "nelmio/solarium-bundle": "2.*"
    }
}
```

Download bundle:

``` bash
$ php composer.phar update nelmio/solarium-bundle
```

Add the NelmioSolariumBundle to your AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Nelmio\SolariumBundle\NelmioSolariumBundle(),
            ...
        );
        ...
    }

## Basic configuration

Quick-start configuration:

```yaml
nelmio_solarium: ~
```

Gives you a Solarium_Client service with default options (`http://localhost:8983/solr`)

```php
    $client = $this->get('solarium.client');
```

Configure your endpoints:

```yaml
nelmio_solarium:
    endpoints:
        default:
            host: localhost
            port: 8983
            path: /solr
            core: active
            timeout: 5
    clients:
        default:
            endpoints: [default]
```

Or with dsn:

```yaml
nelmio_solarium:
    endpoints:
        default:
            dsn: http://localhost:8983/solr/active
            timeout: 5
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

For more information see the [Solarium documentation](http://www.solarium-project.org/documentation/).

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

You can also set wich is the default endpoint

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


## Overriding Classes

To change the adapter or client classes, you can set the client_class and adapter_class options:

```yaml
nelmio_solarium:
    clients:
        default:
            client_class: Solarium\Client
            adapter_class: Solarium\Client\Adapter\Http
```

## License

Released under the MIT License, see LICENSE.
