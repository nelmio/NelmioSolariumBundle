<?php

declare(strict_types=1);

namespace Nelmio\SolariumBundle\Tests;

use Nelmio\SolariumBundle\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch\Stopwatch;

class LoggerTest extends TestCase
{
    public function testLog(): void
    {
        $logger = new Logger();
        $logger->setLogger(new NullLogger());
        $logger->setStopwatch(new Stopwatch());

        $logger->log(new Request(), null, $this->createMock(Endpoint::class), 1);
        $logger->log(new Request(), null, $this->createMock(Endpoint::class), 1.5);
        $logger->collect(new SymfonyRequest(), new Response());

        $this->assertSame(2, $logger->getQueryCount());
        $this->assertCount(2, $logger->getQueries());
        $this->assertSame(2.5, $logger->getTotalTime());
    }
}
