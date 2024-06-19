<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DataObject\IoT;

class BulkMeasurement extends \ArrayObject
{

    public function add(Measurement $measurement): static
    {
        $this[] = $measurement;
        return $this;
    }

}