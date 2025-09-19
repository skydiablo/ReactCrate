<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

/**
 * Schema manager for CrateDB.
 */
class CrateSchemaManager extends PostgreSQLSchemaManager
{
    public function __construct(
        \Doctrine\DBAL\Connection $connection,
        AbstractPlatform $platform
    ) {
        parent::__construct($connection, $platform);
    }

    // Override methods as needed for CrateDB specifics
}
