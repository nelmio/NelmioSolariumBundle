<?php

declare(strict_types=1);

namespace Nelmio\SolariumBundle;

use Solarium\Client;

class ClientRegistry
{
    /**
     * @param array<string, Client> $clients
     */
    public function __construct(private array $clients, private ?string $defaultClientName)
    {
    }

    public function getDefaultClientName(): ?string
    {
        return $this->defaultClientName;
    }

    /**
     * @param string|null $name the client name (null for the default one)
     *
     * @throws \InvalidArgumentException
     */
    public function getClient(?string $name = null): Client
    {
        if (null === $name) {
            $name = $this->defaultClientName;
        }

        if ($name !== null && in_array($name, $this->getClientNames())) {
            return $this->clients[$name];
        }

        throw new \InvalidArgumentException('client '.$name.' not configured');
    }

    /**
     * @return array<string, Client>
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * @return string[]
     */
    public function getClientNames(): array
    {
        return array_keys($this->clients);
    }
}
