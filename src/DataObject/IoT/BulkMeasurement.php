<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DataObject\IoT;

class BulkMeasurement
{

    protected array $bulk = [];

    public function add(Measurement $measurement): static
    {
        $this->bulk[] = $measurement;
        return $this;
    }

    public function getBulk(): array
    {
        return $this->bulk;
    }

}