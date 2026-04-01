<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

class RandomClientSelector implements ClientSelectorInterface
{
    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface
    {
        if ($clients === []) {
            throw new \InvalidArgumentException('No clients available for selection.');
        }

        return $clients[array_rand($clients)];
    }
}
