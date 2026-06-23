<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

interface ClientSelectorInterface
{
    public const string EXCEPT_CLIENTS = 'except_clients';

    /**
     * @param ClientInterface[] $clients
     */
    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface;
}
