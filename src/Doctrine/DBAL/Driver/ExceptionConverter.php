<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Query;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\ReadOnlyException;
use Doctrine\DBAL\Exception\ServerException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;

/**
 * CrateDB-specific exception converter.
 */
final class ExceptionConverter implements ExceptionConverterInterface
{
    public function convert(DriverExceptionInterface $exception, ?Query $query): DriverException
    {
        return match ($exception->getCode()) {
            0,
            4000,
            4007,
            4008,
            4009,
            4010,
            4011,
            4012,
            4013,
            5000,
            5001,
            5003,
            5004,
            5030 => new ServerException($exception, $query),
            4001,
            4002 => new SyntaxErrorException($exception, $query),
            4003,
            4004 => new InvalidFieldNameException($exception, $query),
            4005 => new NonUniqueFieldNameException($exception, $query),
            4006,
            4031 => new ReadOnlyException($exception, $query),
            4041,
            4042,
            4043,
            4045,
            4046 => new DatabaseObjectNotFoundException($exception, $query),
            4091 => new UniqueConstraintViolationException($exception, $query),
            4092,
            4093,
            4095,
            4096,
            4097,
            4098,
            4099,
            4100 => new TableExistsException($exception, $query),
            4094 => new ConstraintViolationException($exception, $query),
            5002 => new ConnectionException($exception, $query),
            default => new DriverException($exception, $query),
        };
    }
}
