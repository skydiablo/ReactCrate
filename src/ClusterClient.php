<?php

namespace SkyDiablo\ReactCrate;

use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\ClientSelection\ClientSelectorInterface;
use SkyDiablo\ReactCrate\ClientSelection\RoundRobinClientSelector;

class ClusterClient implements ClientInterface
{
    /**
     * @var ClientInterface[]
     */
    private array $clients;
    private ClientSelectorInterface $selector;

    /**
     * @param ClientInterface[] $clients
     */
    public function __construct(array $clients, ?ClientSelectorInterface $selector = null)
    {
        if ($clients === []) {
            throw new \InvalidArgumentException('ClusterClient requires at least one client.');
        }

        foreach ($clients as $client) {
            if (!$client instanceof ClientInterface) {
                throw new \InvalidArgumentException('Every cluster entry must implement ClientInterface.');
            }
        }

        $this->clients = array_values($clients);
        $this->selector = $selector ?? new RoundRobinClientSelector();
    }

    protected function selectClient(string $statement, array $arguments = []) : ClientInterface {
        return $this->selector->selectClient($this->clients, $statement, $arguments);
    }

    public function getStatus(): PromiseInterface
    {
        return $this
            ->selectClient('GET_STATUS')
            ->getStatus();
    }

    public function query(string $statement, array $arguments = []): PromiseInterface
    {
        return $this
            ->selectClient($statement, $arguments)
            ->query($statement, $arguments);
    }

    public function refreshTable(string $tableName): PromiseInterface
    {
        return $this
            ->selectClient('REFRESH TABLE', ['table' => $tableName])
            ->refreshTable($tableName);
    }

}
