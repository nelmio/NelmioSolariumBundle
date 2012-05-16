# NelmioSolariumBundle

## About

The NelmioSolariumBundle provides integration with the [solarium](http://www.solarium-project.org)
solr client.

## Features

Provides you with a `solarium.client` service in the Symfony2 DIC.

## Configuration

Here is the default configuration that will be used if you do not configure
anything:

    nelmio_solarium:
        client:
            class: Solarium_Client
        adapter:
            class: Solarium_Client_Adapter_Http
            host: 127.0.0.1
            port: 8983
            path: /solr
            timeout: 5
            cores: ~

You can define cores like this :
            ...
            cores:
                cms: cms_core_path

## Installation

Put the NelmioSolariumBundle into the ``vendor/bundles/Nelmio`` directory:

    $ git clone git://github.com/nelmio/NelmioSolariumBundle.git vendor/bundles/Nelmio/SolariumBundle

Register the `Nelmio` namespace in your project's autoload script (app/autoload.php):

    $loader->registerNamespaces(array(
        'Nelmio'                        => __DIR__.'/../vendor/bundles',
    ));

Add the NelmioSolariumBundle to your application's kernel:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Nelmio\SolariumBundle\NelmioSolariumBundle(),
            ...
        );
        ...
    }

You will also need the [solarium library](https://github.com/basdenooijer/solarium):

    $ git clone git://github.com/basdenooijer/solarium.git vendor/solarium

And the autoloader:

    $loader->registerPrefixes(array(
        'Solarium_'        => __DIR__.'/../vendor/solarium/library',
    ));

## Usage

In your Controllers you can access the Solarium instance using the `solarium.client` service, e.g.:

```php
        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('foo');
        $results = $client->select($select);
```

If you have define a core, you can access by config name like this :

```php
        $client = $this->get('solarium.client.cms');
```

Then you can use `$results` in a `foreach` or twig `for` to display the results.

For more information see the [Solarium documentation](http://www.solarium-project.org/documentation/).

## License

Released under the MIT License, see LICENSE.
