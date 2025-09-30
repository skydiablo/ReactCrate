<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Exception;

/**
 * Custom DateTime type for CrateDB that handles timestamp values.
 *
 * CrateDB stores TIMESTAMP values and returns them as milliseconds since epoch (integer),
 * but accepts datetime strings when writing.
 */
class CrateDateTimeType extends DateTimeType
{
    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTime
    {
        if ($value === null || $value instanceof DateTime) {
            return $value;
        }

        try {
            return DateTime::createFromFormat('U.u', (string)(((int)$value) / 1000), new DateTimeZone('UTC')) ?: null;
        } catch (Exception $e) {
            throw InvalidFormat::new(
                $value,
                static::class,
                'Unix timestamp in milliseconds',
                $e,
            );
        }
    }

}
