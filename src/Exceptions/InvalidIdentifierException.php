<?php

namespace SkyDiablo\ReactCrate\Exceptions;

class InvalidIdentifierException extends BaseException
{
    public static function invalidSqlIdentifier(string $identifier): static
    {
        return new static(
            sprintf(
                'Invalid SQL identifier "%s". Allowed: letters, numbers, underscore; must not start with a number.',
                $identifier
            )
        );
    }
}
