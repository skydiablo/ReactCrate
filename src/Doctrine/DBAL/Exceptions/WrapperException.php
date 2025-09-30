<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Exceptions;

use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use JetBrains\PhpStorm\Pure;
use SkyDiablo\ReactCrate\Exceptions\BaseException;

class WrapperException extends BaseException implements DriverExceptionInterface
{
    #[Pure]
    protected function __construct(BaseException $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    public static function create(BaseException $exception): self
    {
        return new self($exception);
    }

    public function getSQLState(): ?string
    {
        return null;
    }


}