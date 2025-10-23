<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Functions;

class BooleanValue implements FunctionDefinition
{

    public function __construct(private readonly bool $bool) {}

    public function __toString(): string
    {
        return $this->bool ? 'TRUE' : 'FALSE';
    }
}