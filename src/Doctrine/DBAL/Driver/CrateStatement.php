<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use React\Async;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CrateResult;
use SkyDiablo\ReactCrate\Client;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Exceptions\WrapperException;
use SkyDiablo\ReactCrate\Exceptions\BaseException;

/**
 * Statement implementation for CrateDB in Doctrine DBAL.
 */
class CrateStatement implements Statement
{
    private Client $client;
    private string $sql;
    private array $params = [];
    private array $types = [];

    public function __construct(Client $client, string $sql)
    {
        $this->client = $client;
        $this->sql = $sql;
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->params[$param] = $value;
        $this->types[$param] = $type;
    }

    public function execute(): Result
    {
        try {
            $resultData = Async\await($this->client->query($this->sql, $this->params));

            return new CrateResult($resultData);
        } catch (\Throwable $e) {
            if ($e instanceof BaseException) {
                throw new DriverException(WrapperException::create($e), new Query($this->sql, $this->params, $this->types));
            }
            throw $e;
        }
    }
}
