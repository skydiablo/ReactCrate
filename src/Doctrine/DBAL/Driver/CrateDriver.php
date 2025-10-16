<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\ServerVersionProvider;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Types\TypeRegistry;

/**
 * Doctrine DBAL driver for CrateDB using the ReactCrate Client.
 */
class CrateDriver implements Driver
{

    public function connect(array $params): DriverConnection
    {
        $host = (string) ($params['host'] ?? 'localhost');
        $port = (int) ($params['port'] ?? 4200);
        $transfer_protocol = $params['transfer_protocol'] ?? 'http';
        $transfer_protocol = in_array($transfer_protocol, ['http', 'https']) ? $transfer_protocol : 'http';
        $url = "{$transfer_protocol}://{$host}:{$port}";
        $connectorContext = (array) ($params['connector_context'] ?? []);
        $client = new \SkyDiablo\ReactCrate\Client($url, $connectorContext);
        return new CrateConnection($client);
    }

    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        return new CratePlatform();
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }

}
