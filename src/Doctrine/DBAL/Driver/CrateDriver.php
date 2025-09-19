<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CrateConnection;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CratePlatform;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Driver\CrateSchemaManager;

/**
 * Doctrine DBAL driver for CrateDB using the ReactCrate Client.
 */
class CrateDriver extends AbstractPostgreSQLDriver
{
    public function connect(array $params): DriverConnection
    {
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? 4200;
        $url = "http://{$host}:{$port}";
        $client = new \SkyDiablo\ReactCrate\Client($url);
        return new CrateConnection($client);
    }

    public function getDatabasePlatform(): AbstractPlatform
    {
        return new CratePlatform();
    }

    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): \Doctrine\DBAL\Schema\AbstractSchemaManager
    {
        return new CrateSchemaManager($conn, $platform);
    }

    public function getName(): string
    {
        return 'crate';
    }

    public function getDatabase(Connection $conn): ?string
    {
        return null; // CrateDB does not have traditional databases
    }
}
