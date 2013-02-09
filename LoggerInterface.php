<?php
namespace Nelmio\SolariumBundle;

use Solarium\Core\Client\Request as SolariumRequest;
use Solarium\Core\Client\Response as SolariumResponse;

interface LoggerInterface
{
    public function log(SolariumRequest $request, SolariumResponse $response, $duration);
}
