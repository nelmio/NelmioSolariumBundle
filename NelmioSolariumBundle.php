<?php

/*
 * This file is part of the Nelmio SolariumBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\SolariumBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Nelmio\ApiDocBundle\DependencyInjection\Compiler\ConfigurationPass;
use Nelmio\ApiDocBundle\DependencyInjection\Compiler\TagDescribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class NelmioSolariumBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new TagDescribersPass());
    }
}
