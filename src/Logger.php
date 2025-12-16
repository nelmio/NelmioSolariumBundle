<?php

declare(strict_types=1);

namespace Nelmio\SolariumBundle;

use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Endpoint as SolariumEndpoint;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Request as SolariumRequest;
use Solarium\Core\Client\Response;
use Solarium\Core\Event\Events as SolariumEvents;
use Solarium\Core\Event\PostExecuteRequest as SolariumPostExecuteRequestEvent;
use Solarium\Core\Event\PreExecuteRequest as SolariumPreExecuteRequestEvent;
use Solarium\Core\Plugin\AbstractPlugin as SolariumPlugin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @phpstan-type Query array{request: SolariumRequest, response: ?Response, duration: int|float, base_uri: string}
 */
final class Logger extends SolariumPlugin implements DataCollectorInterface, \Serializable
{
    /**
     * @var array{queries?: Query[], total_time?: int|float}
     */
    private array $data = [];
    /**
     * @var Query[]
     */
    private array $queries = [];
    private ?Request $currentRequest = null;
    private ?float $currentStartTime = null;
    private ?Endpoint $currentEndpoint = null;
    private ?LoggerInterface $logger = null;
    private ?Stopwatch $stopwatch = null;

    /**
     * @var PsrEventDispatcherInterface[]
     */
    private array $eventDispatchers = [];

    protected function initPluginType(): void
    {
        $dispatcher = $this->client->getEventDispatcher();
        if (!in_array($dispatcher, $this->eventDispatchers, true)) {
            if ($dispatcher instanceof EventDispatcherInterface) {
                $dispatcher->addListener(SolariumEvents::PRE_EXECUTE_REQUEST, [$this, 'preExecuteRequest'], 1000);
                $dispatcher->addListener(SolariumEvents::POST_EXECUTE_REQUEST, [$this, 'postExecuteRequest'], -1000);
            }
            $this->eventDispatchers[] = $dispatcher;
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setStopwatch(Stopwatch $stopwatch): void
    {
        $this->stopwatch = $stopwatch;
    }

    public function log(SolariumRequest $request, ?Response $response, SolariumEndpoint $endpoint, int|float $duration): void
    {
        $this->queries[] = [
            'request' => $request,
            'response' => $response,
            'duration' => $duration,
            'base_uri' => $endpoint->getCoreBaseUri(),
        ];
    }

    public function collect(HttpRequest $request, HttpResponse $response, ?\Throwable $exception = null): void
    {
        if (isset($this->currentRequest)) {
            $this->failCurrentRequest();
        }

        $time = 0;
        foreach ($this->queries as $queryStruct) {
            $time += $queryStruct['duration'];
        }
        $this->data = [
            'queries' => $this->queries,
            'total_time' => $time,
        ];
    }

    public function getName(): string
    {
        return 'solr';
    }

    /**
     * @return array<array{request: SolariumRequest, response: ?Response, duration: int|float, base_uri: string}>
     */
    public function getQueries(): array
    {
        return array_key_exists('queries', $this->data) ? $this->data['queries'] : [];
    }

    public function getQueryCount(): int
    {
        return count($this->getQueries());
    }

    public function getTotalTime(): int|float
    {
        return array_key_exists('total_time', $this->data) ? $this->data['total_time'] : 0;
    }

    public function preExecuteRequest(SolariumPreExecuteRequestEvent $event): void
    {
        if (isset($this->currentRequest)) {
            $this->failCurrentRequest();
        }

        $this->stopwatch?->start('solr', 'solr');

        $this->currentRequest = $event->getRequest();
        $this->currentEndpoint = $event->getEndpoint();

        $this->logger?->debug($this->currentEndpoint->getCoreBaseUri().$this->currentRequest->getUri());
        $this->currentStartTime = microtime(true);
    }

    public function postExecuteRequest(SolariumPostExecuteRequestEvent $event): void
    {
        $endTime = microtime(true) - $this->currentStartTime;
        if (!isset($this->currentRequest)) {
            throw new \RuntimeException('Request not set');
        }
        if ($this->currentRequest !== $event->getRequest()) {
            throw new \RuntimeException('Requests differ');
        }

        if (null !== $this->stopwatch && $this->stopwatch->isStarted('solr')) {
            $this->stopwatch->stop('solr');
        }

        $this->log($event->getRequest(), $event->getResponse(), $event->getEndpoint(), $endTime);

        $this->currentRequest = null;
        $this->currentStartTime = null;
        $this->currentEndpoint = null;
    }

    public function failCurrentRequest(): void
    {
        $endTime = microtime(true) - $this->currentStartTime;

        if (null !== $this->stopwatch && $this->stopwatch->isStarted('solr')) {
            $this->stopwatch->stop('solr');
        }

        $this->log($this->currentRequest, null, $this->currentEndpoint, $endTime);

        $this->currentRequest = null;
        $this->currentStartTime = null;
        $this->currentEndpoint = null;
    }

    public function serialize(): string
    {
        return serialize($this->data);
    }

    public function unserialize($data): void
    {
        $this->data = unserialize($data);
    }

    public function reset(): void
    {
        $this->data = [];
        $this->queries = [];
    }

    /**
     * @return array{queries?: Query[], total_time?: int|float}
     */
    public function __serialize(): array
    {
        return $this->data;
    }

    /**
     * @param array{queries?: Query[], total_time?: int|float} $data
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data;
    }
}
