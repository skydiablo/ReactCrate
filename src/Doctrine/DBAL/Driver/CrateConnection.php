<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use React\Async;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Exceptions\NotSupportedException;

/**
 * Connection wrapper for CrateDB in Doctrine DBAL.
 */
class CrateConnection implements DriverConnection
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function prepare(string $sql): Statement
    {
        return new CrateStatement($this->client, $sql);
    }

    public function query(string $sql): Result
    {
        return $this->prepare($sql)->execute();
    }

    public function quote($value, $type = ParameterType::STRING): string
    {
        // Simple quoting for strings
        if ($type === ParameterType::INTEGER || $type === ParameterType::BOOLEAN) {
            return (string) $value;
        }
        return "'" . addslashes((string) $value) . "'";
    }

    public function exec(string $sql): int
    {
        $result = Async\await($this->client->query($sql));
        return $result['rowcount'] ?? 0;
    }

    public function lastInsertId(): int|string
    {
        // CrateDB does not support auto-increment IDs in the same way
        throw new NotSupportedException(__METHOD__ . ' is not supported by CrateDB.');
    }

    public function beginTransaction(): void
    {
        return; // silent ignored
//        throw new NotSupportedException(__METHOD__ . ' is not supported by CrateDB.');
    }

    public function commit(): void
    {
        return; // silent ignored
//        throw new NotSupportedException(__METHOD__ . ' is not supported by CrateDB.');
    }

    public function rollBack(): void
    {
        return; // silent ignored
//        throw new NotSupportedException(__METHOD__ . ' is not supported by CrateDB.');
    }

    public function getServerVersion(): string
    {
        $result = Async\await($this->client->getStatus());
        return $result['version']['number'] ?? 'unknown';
    }

    public function getNativeConnection(): Client
    {
        return $this->client;
    }
}
