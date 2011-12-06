# NelmioSolariumBundle

## About

The NelmioSolariumBundle provides integration with the [solarium](http://www.solarium-project.org)
solr client.

## Features

Provides you with a `solarium.client` service in the Symfony2 DIC.

## Configuration

Here is the default configuration:

    nelmio_solarium:
        adapter:
            class: Solarium_Client_Adapter_Http
            host: 127.0.0.1
            port: 8983
            path: /solr

At the very least you need to add this to your config:

    nelmio_solarium:
        adapter: ~

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

## License

Released under the MIT License, see LICENSE.
