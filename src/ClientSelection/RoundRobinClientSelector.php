<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

class RoundRobinClientSelector implements ClientSelectorInterface
{
    private int $index = 0;

    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface
    {
        if ($clients === []) {
            throw new \InvalidArgumentException('No clients available for selection.');
        }

        $client = $clients[$this->index % count($clients)];
        $this->index++;

        return $client;
    }
}
