<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Functions;

class NullValue implements FunctionDefinition
{

    public function __toString(): string
    {
        return 'NULL';
    }
}