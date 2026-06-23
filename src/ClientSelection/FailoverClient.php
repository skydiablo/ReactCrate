<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\ClientInterface;
use SkyDiablo\ReactCrate\Exceptions\CrateResponseException;

use function React\Promise\reject;

final readonly class FailoverClient implements ClientInterface
{

    protected ?int $maxTries;

    /**
     * @param ClientInterface[] $clients
     */
    public function __construct(
        private ClientInterface $client,
        private array $clients,
        private ClientSelectorInterface $selector,
        private string $selectStatement,
        private array $selectArguments,
        ?int $maxTries = null,
        private int $attempt = 1,
    ) {
        $this->maxTries = $maxTries ?? count($this->clients);
    }

    public function getStatus(): PromiseInterface
    {
        return $this->withFailover(fn(ClientInterface $client) => $client->getStatus());
    }

    public function query(string $statement, array $arguments = []): PromiseInterface
    {
        return $this->withFailover(fn(ClientInterface $client) => $client->query($statement, $arguments));
    }

    public function refreshTable(string $tableName): PromiseInterface
    {
        return $this->withFailover(fn(ClientInterface $client) => $client->refreshTable($tableName));
    }

    /**
     * @param callable(ClientInterface): PromiseInterface $operation
     */
    private function withFailover(callable $operation): PromiseInterface
    {
        return $operation($this->client)->catch(function (\Throwable $throwable) use ($operation) {
            if (!$this->isConnectionFailure($throwable)) {
                return reject($throwable);
            }

            return $this->retryWithNextClient($operation, $throwable);
        });
    }

    /**
     * @param callable(ClientInterface): PromiseInterface $operation
     */
    private function retryWithNextClient(callable $operation, \Throwable $previous): PromiseInterface
    {
        if ($this->attempt >= $this->maxTries) {
            return reject($previous);
        }

        $except = $this->selectArguments[ClientSelectorInterface::EXCEPT_CLIENTS] ?? [];
        $except[] = $this->clusterClient($this->client);

        $remainingClients = array_values(array_filter(
            $this->clients,
            fn(ClientInterface $client) => !in_array($client, $except, true),
        ));

        if ($remainingClients === []) {
            return reject($previous);
        }

        $selectArguments = $this->selectArguments + [ClientSelectorInterface::EXCEPT_CLIENTS => array_values($except)];
        $nextClient = $this->selector->selectClient($this->clients, $this->selectStatement, $selectArguments);

        return new self(
            $nextClient,
            $this->clients,
            $this->selector,
            $this->selectStatement,
            $selectArguments,
            $this->maxTries,
            $this->attempt + 1,
        )->withFailover($operation);
    }

    private function clusterClient(ClientInterface $client): ClientInterface
    {
        if ($client instanceof DelegatesToClientInterface) {
            return $client->getDelegatedClient();
        }

        return $client;
    }

    private function isConnectionFailure(\Throwable $throwable): bool
    {
        $current = $throwable;

        while (true) {
            if ($current instanceof CrateResponseException) {
                return false;
            }

            if ($current instanceof \RuntimeException) {
                $message = $current->getMessage();
                if (
                    (str_starts_with($message, 'Connection to ') && str_contains($message, ' failed'))
                    || str_starts_with($message, 'Request timed out after ')
                ) {
                    return true;
                }
            }

            $previous = $current->getPrevious();
            if (!$previous instanceof \Throwable) {
                return false;
            }

            $current = $previous;
        }
    }
}
