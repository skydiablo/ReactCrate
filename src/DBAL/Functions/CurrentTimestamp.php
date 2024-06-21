<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Functions;

use SkyDiablo\ReactCrate\DBAL\Functions\FunctionDefinition;

class CurrentTimestamp implements FunctionDefinition
{

    public function __construct(protected int $precision = 3)
    {
        if ($precision < 0 || $precision > 3) {
            throw new \InvalidArgumentException('Precision must be between 0 and 3');
        }
    }

    public function __toString(): string
    {
        return 'CURRENT_TIMESTAMP' . ($this->precision < 3 ? '(' . $this->precision . ')' : '');
    }

}