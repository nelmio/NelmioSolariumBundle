<?php

namespace Nelmio\SolariumBundle;

use Solarium\Client;

/**
 * Class ClientRegistry
 *
 * Service to access all the clients configured by the bundle
 *
 * @package Nelmio\SolariumBundle
 */
class ClientRegistry
{
    /** @var string */
    protected $defaultClientName;

    /** @var array */
    protected $clients;

    public function __construct(array $clients, $defaultClientName)
    {
        $this->defaultClientName = $defaultClientName;
        $this->clients = $clients;
    }

    /**
     * Gets the default client name.
     *
     * @return string The default client name.
     */
    public function getDefaultClientName()
    {
        return $this->defaultClientName;
    }

    /**
     * Gets the named client.
     *
     * @param string $name The client name (null for the default one).
     *
     * @throws \InvalidArgumentException
     * @return Client
     */
    public function getClient($name = null)
    {
        if (null === $name) {
            $name = $this->defaultClientName;
        }

        if (in_array($name, $this->getClientNames())) {
            return $this->clients[$name];
        }

        throw new \InvalidArgumentException(($name === null ? 'default client' : 'client ' . $name) . ' not configured');
    }

    /**
     * Gets an array of all registered clients.
     *
     * @return array An array of Client instances.
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Gets all client names.
     *
     * @return array An array of client names.
     */
    public function getClientNames()
    {
        return array_keys($this->clients);
    }

}