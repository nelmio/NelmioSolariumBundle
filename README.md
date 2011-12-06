# NelmioSolariumBundle

## About

The NelmioSolariumBundle provides integration with the [solarium](www.solarium-project.org)
solr client.

## Features

...

## Configuration

...

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
