<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Types;

use Doctrine\DBAL\Types\Type;

/**
 * Registry for CrateDB custom types.
 * 
 * Call TypeRegistry::registerTypes() once during application bootstrap
 * before using any CrateDB connections.
 */
class TypeRegistry
{
    private static bool $registered = false;

    /**
     * Register all custom CrateDB types with Doctrine.
     * 
     * This should be called once during application bootstrap.
     * 
     * @throws \Doctrine\DBAL\Exception
     */
    public static function registerTypes(): void
    {
        if (self::$registered) {
            return;
        }

        if (!Type::hasType('crate_datetime')) {
            Type::addType('crate_datetime', CrateDateTimeType::class);
        }

        self::$registered = true;
    }
}
