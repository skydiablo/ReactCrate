<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Functions;

use SkyDiablo\ReactCrate\DBAL\Functions\Enums\DateTruncInterval;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

class DateTrunc implements FunctionDefinition
{


    public function __construct(
        protected DateTruncInterval $interval,
        protected TableField        $timestampField,
        protected ?\DateTimeZone    $timezone = null
    )
    {
    }

    public function __toString(): string
    {
        $result = "DATE_TRUNC('{$this->interval->name}', ";
        if ($this->timezone !== null) {
            $result .= "'{$this->timezone->getName()}', ";
        }
        $result .= "\"{$this->timestampField->getName()}\")";
        return $result;
    }


}