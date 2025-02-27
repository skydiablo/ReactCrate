<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DataObject\IoT;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DataObject\IoT\BulkMeasurement;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;
use SkyDiablo\ReactCrate\Exceptions\MeasurementException;

class BulkMeasurementTest extends TestCase
{
    public function testConstructorWithValidMeasurements(): void
    {
        $measurement1 = new Measurement(new \DateTime(), 'temp1');
        $measurement2 = new Measurement(new \DateTime(), 'temp2');
        
        $bulk = new BulkMeasurement([$measurement1, $measurement2]);
        
        $this->assertCount(2, $bulk);
        $this->assertSame($measurement1, $bulk[0]);
        $this->assertSame($measurement2, $bulk[1]);
    }

    public function testConstructorWithInvalidData(): void
    {
        $this->expectException(MeasurementException::class);
        
        new BulkMeasurement(['invalid']);
    }

    public function testAddMeasurement(): void
    {
        $bulk = new BulkMeasurement();
        $measurement = new Measurement(new \DateTime(), 'temp');
        
        $bulk->add($measurement);
        
        $this->assertCount(1, $bulk);
        $this->assertSame($measurement, $bulk[0]);
    }
} 