<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Functions\Enums;

enum DateTruncInterval
{
    case second;
    case minute;
    case hour;
    case day;
    case week;
    case month;
    case quarter;
    case year;
}
