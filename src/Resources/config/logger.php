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

use Nelmio\SolariumBundle\Logger;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('solarium.data_collector.class', Logger::class);

    $container->services()
        ->set('solarium.data_collector', '%solarium.data_collector.class%')
        ->public()
        ->tag('data_collector', ['template' => '@NelmioSolarium/DataCollector/solarium', 'id' => 'solr'])
        ->tag('monolog.logger', ['channel' => 'solr'])
        ->call('setLogger', [service('logger')->ignoreOnInvalid()])
        ->call('setStopWatch', [service('debug.stopwatch')->ignoreOnInvalid()]);
};
