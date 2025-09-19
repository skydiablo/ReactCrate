<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\Exception as DriverException;
use React\Async;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CrateResult;
use SkyDiablo\ReactCrate\Client;

/**
 * Statement implementation for CrateDB in Doctrine DBAL.
 */
class CrateStatement implements Statement
{
    private Client $client;
    private string $sql;
    private array $params = [];

    public function __construct(Client $client, string $sql)
    {
        $this->client = $client;
        $this->sql = $sql;
    }

    public function bindValue($param, $value, $type = null): true
    {
        $this->params[$param] = $value;
        return true;
    }

    public function bindParam($param, &$variable, $type = null, $length = null): bool
    {
        $this->params[$param] = &$variable;
        return true;
    }

    public function execute(?array $params = null): Result
    {
        if ($params !== null) {
            $this->params = $params;
        }
        try {
            $resultData = Async\await($this->client->query($this->sql, $this->params));
            return new CrateResult($resultData);
        } catch (\Throwable $e) {
            throw DriverException::convertExceptionDuringQuery($e, $this->sql);
        }
    }
}
