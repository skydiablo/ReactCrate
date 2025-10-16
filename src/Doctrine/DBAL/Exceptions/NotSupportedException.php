<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Exceptions;

use Doctrine\DBAL\Exception as DBALExceptionInterface;
use JetBrains\PhpStorm\Pure;

/**
 * Custom exception for features not supported by CrateDB.
 */
class NotSupportedException extends \Exception implements DBALExceptionInterface
{

    private const int ERROR_CODE = 4000;

    #[Pure]
    public function __construct(string $message = "")
    {
        parent::__construct($message, self::ERROR_CODE);
    }

}
