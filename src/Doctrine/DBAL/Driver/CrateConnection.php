<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use React\Async;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CrateStatement;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CrateResult;
use SkyDiablo\ReactCrate\Client;

/**
 * Connection wrapper for CrateDB in Doctrine DBAL.
 */
class CrateConnection implements DriverConnection, ServerInfoAwareConnection
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

    public function lastInsertId($name = null): false|string
    {
        // CrateDB does not support auto-increment IDs in the same way
        throw new \RuntimeException('lastInsertId is not supported by CrateDB driver.');
    }

    public function beginTransaction(): bool
    {
        try {
            $this->exec('BEGIN');
            return true;
        } catch (\Throwable $e) {
            throw DriverException::convertExceptionDuringQuery($e, 'BEGIN');
        }
    }

    public function commit(): bool
    {
        $this->exec('COMMIT');
        return true;
    }

    public function rollBack(): bool
    {
        $this->exec('ROLLBACK');
        return true;
    }

    public function getServerVersion(): string
    {
        $result = Async\await($this->client->query('SELECT version[\'number\'] FROM sys.cluster'));
        return $result['rows'][0][0] ?? 'unknown';
    }

    public function getNativeConnection(): Client
    {
        return $this->client;
    }
}
