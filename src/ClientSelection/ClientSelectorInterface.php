<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

interface ClientSelectorInterface
{
    /**
     * @param ClientInterface[] $clients
     */
    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface;
}
