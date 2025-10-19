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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Nelmio\SolariumBundle\ClientRegistry;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('solarium.client_registry.class', ClientRegistry::class);

    $container->services()
        ->set('solarium.client_registry', '%solarium.client_registry.class%')
        ->public()
        ->args([
            [],
            null,
        ]);
};
