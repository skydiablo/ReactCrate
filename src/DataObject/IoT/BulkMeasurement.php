<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DataObject\IoT;

use SkyDiablo\ReactCrate\Exceptions\MeasurementException;

class BulkMeasurement extends \ArrayObject
{
    /**
     * @throws MeasurementException
     */
    public function __construct(object|array $array = [], int $flags = 0, string $iteratorClass = "ArrayIterator")
    {
        /** @var Measurement $measurement */
        foreach($array as $measurement) {
            if(!($measurement instanceof Measurement)) {
                throw MeasurementException::notAMeasurement($measurement);
            }
        }
        parent::__construct($array, $flags, $iteratorClass);
    }


    public function add(Measurement $measurement): static
    {
        $this[] = $measurement;
        return $this;
    }

}