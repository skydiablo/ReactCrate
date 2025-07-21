<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Functions;

use Stringable;

class StaticString implements FunctionDefinition
{

    public function __construct(
        readonly protected string $value,
    ) {}

    public function __toString(): string
    {
        return escapeshellarg($this->value);
    }
}