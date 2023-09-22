<?php

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
    public function testLog()
    {
        $logger = new Logger();
        $logger->setLogger(new NullLogger());
        $logger->setStopwatch(new Stopwatch());

        $logger->log(new Request(), null, $this->createMock(Endpoint::class), 0);
        $logger->collect(new SymfonyRequest(), new Response());

        $this->assertSame(1, $logger->getQueryCount());
        $this->assertCount(1, $logger->getQueries());
    }
}
