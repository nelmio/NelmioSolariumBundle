<?php
namespace Nelmio\SolariumBundle;

use Solarium_Client_Request;
use Solarium_Client_Response;

interface LoggerInterface
{
    public function log(Solarium_Client_Request $request, Solarium_Client_Response $response, $duration);
}
