<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

class ExceptClientsClientSelector implements ClientSelectorInterface
{
    public function __construct(
        private readonly ClientSelectorInterface $selector,
    ) {
    }

    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface
    {
        if ($except = $arguments[ClientSelectorInterface::EXCEPT_CLIENTS] ?? []) {
            $clients = array_values(
                array_filter(
                    $clients,
                    fn(ClientInterface $client) => !in_array($client, $except, true),
                ),
            );
        }

        return $this->selector->selectClient($clients, $statement, $arguments);
    }
}
