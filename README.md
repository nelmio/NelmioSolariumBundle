# NelmioSolariumBundle

## About

The NelmioSolariumBundle provides integration with the [solarium](http://www.solarium-project.org)
solr client.

## Installation

Add NelmioSolariumBundle in your composer.json:

```js
{
    "require": {
        "nelmio/solarium-bundle": "1.*"
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

Configure your client:

```yaml
nelmio_solarium:
    clients:
        default:
            host: localhost
            port: 8983
            path: /solr
            core: active
            timeout: 5
```

Or with dsn:

```yaml
nelmio_solarium:
    clients:
        default:
            dsn: http://localhost:8983/solr/active
            timeout: 5
```

## Usage

```php
        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('foo');
        $results = $client->select($select);
```

For more information see the [Solarium documentation](http://www.solarium-project.org/documentation/).

## Multiple clients

```yaml
nelmio_solarium:
    clients:
        default:
            host: 192.168.1.2

        another:
            host: 192.168.1.3
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
    clients:
        firstOne:
            host: 192.168.1.2

        anotherOne:
            host: 192.168.1.3
```

```php
    $firstOneClient = $this->get('solarium.client');
    //or
    $firstOneClient = $this->get('solarium.client.firstOne');

    $anotherOneClient = $this->get('solarium.client.anotherOne');
```

## Overriding Classes

To change the adapter or client classes, you can set the client_class and adapter_class options:

```yaml
nelmio_solarium:
    default_client: firstOne
    clients:
        default:
            client_class: Solarium_Client
            adapter_class: Solarium_Client_Adapter_Http
```

## License

Released under the MIT License, see LICENSE.
