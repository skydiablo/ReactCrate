<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

class LoadClientSelector implements ClientSelectorInterface
{
    /**
     * @var array<string,int>
     */
    private array $inflightRequests = [];
    /**
     * @var array<string,ClientInterface>
     */
    private array $wrappedClients = [];

    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface
    {
        if ($clients === []) {
            throw new \InvalidArgumentException('No clients available for selection.');
        }

        $selected = $clients[0];
        $selectedLoad = $this->loadForClient($selected);

        foreach ($clients as $client) {
            $clientLoad = $this->loadForClient($client);

            if ($clientLoad < $selectedLoad) {
                $selected = $client;
                $selectedLoad = $clientLoad;
            }
        }

        return $this->wrapClient($selected);
    }

    private function loadForClient(ClientInterface $client): int
    {
        return $this->inflightRequests[$this->clientKey($client)] ?? 0;
    }

    private function clientKey(ClientInterface $client): string
    {
        return spl_object_hash($client);
    }

    private function wrapClient(ClientInterface $client): ClientInterface
    {
        $key = $this->clientKey($client);
        if (isset($this->wrappedClients[$key])) {
            return $this->wrappedClients[$key];
        }

        $onStart = function (ClientInterface $trackedClient): void {
            $this->markRequestStart($trackedClient);
        };
        $onEnd = function (ClientInterface $trackedClient): void {
            $this->markRequestEnd($trackedClient);
        };

        $wrappedClient = new class($client, $onStart, $onEnd) implements ClientInterface, DelegatesToClientInterface {
            public function __construct(
                private readonly ClientInterface $client,
                private readonly \Closure $onStart,
                private readonly \Closure $onEnd
            ) {
            }

            public function getDelegatedClient(): ClientInterface
            {
                return $this->client;
            }

            public function getStatus(): \React\Promise\PromiseInterface
            {
                return $this->client->getStatus();
            }

            public function query(string $statement, array $arguments = []): \React\Promise\PromiseInterface
            {
                ($this->onStart)($this->client);

                return $this->client
                    ->query($statement, $arguments)
                    ->finally(
                        function () {
                            ($this->onEnd)($this->client);
                        }
                    );
            }

            public function refreshTable(string $tableName): \React\Promise\PromiseInterface
            {
                return $this->client->refreshTable($tableName);
            }
        };

        $this->wrappedClients[$key] = $wrappedClient;
        return $wrappedClient;
    }

    private function markRequestStart(ClientInterface $client): void
    {
        $key = $this->clientKey($client);
        $this->inflightRequests[$key] = ($this->inflightRequests[$key] ?? 0) + 1;
    }

    private function markRequestEnd(ClientInterface $client): void
    {
        $key = $this->clientKey($client);
        $this->inflightRequests[$key] = max(0, ($this->inflightRequests[$key] ?? 1) - 1);
    }
}
