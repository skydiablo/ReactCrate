<?php

namespace SkyDiablo\ReactCrate\Exceptions;

use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;

class MeasurementException extends BaseException
{
    public static function notAMeasurement(mixed $input): static
    {
        return new static(sprintf('Given input "%s" is not a instance of "%s"', gettype($input), Measurement::class));
    }
}