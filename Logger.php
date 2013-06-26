<?php

namespace Nelmio\SolariumBundle;

use Solarium\Core\Client\Request as SolariumRequest;
use Solarium\Core\Client\Response as SolariumResponse;
use Solarium\Core\Client\Endpoint as SolariumEndpoint;
use Solarium\Core\Plugin\Plugin as SolariumPlugin;
use Solarium\Core\Event\Events as SolariumEvents;
use Solarium\Core\Event\PreExecuteRequest as SolariumPreExecuteRequestEvent;
use Solarium\Core\Event\PostExecuteRequest as SolariumPostExecuteRequestEvent;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class Logger extends SolariumPlugin implements DataCollectorInterface, \Serializable
{
    private $data = array();
    private $queries = array();
    private $currentRequest;
    private $currentStartTime;
    private $currentEndpoint;

    private $logger;
    private $stopwatch;

    /**
     * Plugin init function
     *
     * Register event listeners
     */
    protected function initPluginType()
    {
        $dispatcher = $this->client->getEventDispatcher();
        $dispatcher->addListener(SolariumEvents::PRE_EXECUTE_REQUEST, array($this, 'preExecuteRequest'));
        $dispatcher->addListener(SolariumEvents::POST_EXECUTE_REQUEST, array($this, 'postExecuteRequest'));
    }

    /**
     * Set the Logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the Stopwatch
     *
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch($stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    public function log(SolariumRequest $request, $response, SolariumEndpoint $endpoint, $duration)
    {
        $this->queries[] = array(
            'request' => $request,
            'response' => $response,
            'duration' => $duration,
            'base_uri' => $endpoint->getBaseUri(),
        );
    }

    public function collect(HttpRequest $request, HttpResponse $response, \Exception $exception = null)
    {
        if (isset($this->currentRequest)) {
            $this->failCurrentRequest();
        }

        $time = 0;
        foreach ($this->queries as $queryStruct) {
            $time += $queryStruct['duration'];
        }
        $this->data = array(
            'queries'     => $this->queries,
            'total_time'  => $time,
        );
    }

    public function getName()
    {
        return 'solr';
    }

    public function getQueries()
    {
        return array_key_exists('queries', $this->data) ? $this->data['queries'] : array();
    }

    public function getQueryCount()
    {
        return count($this->getQueries());
    }

    public function getTotalTime()
    {
        return array_key_exists('total_time', $this->data) ? $this->data['total_time'] : 0;
    }

    public function preExecuteRequest(SolariumPreExecuteRequestEvent $event)
    {
        if (isset($this->currentRequest)) {
            $this->failCurrentRequest();
        }

        if (null !== $this->stopwatch) {
            $this->stopwatch->start('solr', 'solr');
        }

        $this->currentRequest = $event->getRequest();
        $this->currentEndpoint = $event->getEndpoint();

        if (null !== $this->logger) {
            $this->logger->debug($event->getEndpoint()->getBaseUri() . $this->currentRequest->getUri());
        }
        $this->currentStartTime = microtime(true);
    }

    public function postExecuteRequest(SolariumPostExecuteRequestEvent $event)
    {
        $endTime = microtime(true) - $this->currentStartTime;
        if (!isset($this->currentRequest)) {
            throw new \RuntimeException('Request not set');
        }
        if ($this->currentRequest !== $event->getRequest()) {
            throw new \RuntimeException('Requests differ');
        }

        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('solr');
        }

        $this->log($event->getRequest(), $event->getResponse(), $event->getEndpoint(), $endTime);

        $this->currentRequest = null;
        $this->currentStartTime = null;
        $this->currentEndpoint = null;
    }

    public function failCurrentRequest()
    {
        $endTime = microtime(true) - $this->currentStartTime;

        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('solr');
        }

        $this->log($this->currentRequest, null, $this->currentEndpoint, $endTime);

        $this->currentRequest = null;
        $this->currentStartTime = null;
        $this->currentEndpoint = null;
    }

    public function serialize()
    {
        return serialize($this->data);
    }

    public function unserialize($data)
    {
        $this->data = unserialize($data);
    }
}
