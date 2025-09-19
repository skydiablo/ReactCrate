<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Platforms\AbstractPostgreSQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;

/**
 * Platform for CrateDB, based on PostgreSQL platform due to similarities.
 */
class CratePlatform extends AbstractPostgreSQLPlatform
{
    public function getName(): string
    {
        return 'crate';
    }

    // Override methods as needed for CrateDB specifics
    // For example, custom types, functions, etc.

    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES | self::CREATE_FOREIGNKEYS): array
    {
        // Use existing Table builder if desired, or implement custom logic
        return parent::getCreateTableSQL($table, $createFlags);
    }

    // Add custom SQL functions from existing DBAL/Functions
    protected function initializeDoctrineTypeMappings(): void
    {
        parent::initializeDoctrineTypeMappings();
        // Add CrateDB specific types if needed
    }
}
