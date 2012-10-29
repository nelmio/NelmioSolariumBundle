<?php

namespace Nelmio\SolariumBundle;

use Solarium_Client_Request as SolariumRequest;
use Solarium_Client_Response as SolariumResponse;
use Solarium_Plugin_Abstract;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class Logger extends Solarium_Plugin_Abstract implements DataCollectorInterface
{
    private $queries = array();
    private $currentRequest = null;
    private $currentStartTime = null;

    public function log(SolariumRequest $request, SolariumResponse $response, $duration)
    {
        $this->queries[] = array(
            'request' => $request,
            'response' => $response,
            'duration' => $duration,
            'base_uri' => $this->_client->getAdapter()->getBaseUri()
        );
    }

    public function collect(HttpRequest $request, HttpResponse $response, \Exception $exception = null)
    {
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

    public function preExecuteRequest($request)
    {
        if (isset($this->currentRequest)) {
            // mop: hmmm not sure what happens when an exception is thrown :S lets be restrictive for the moment
            throw new \RuntimeException('Request already set');
        }
        $this->currentRequest = $request;
        $this->currentStartTime = microtime(true);
    }

    public function postExecuteRequest($request, $response)
    {
        if (!isset($this->currentRequest)) {
            throw new \RuntimeException('Request not set');
        }
        if ($this->currentRequest !== $request) {
            throw new \RuntimeException('Requests differ');
        }

        $this->log($request, $response, microtime(true) - $this->currentStartTime);

        $this->currentRequest = null;
        $this->currentStartTime = null;
    }
}
