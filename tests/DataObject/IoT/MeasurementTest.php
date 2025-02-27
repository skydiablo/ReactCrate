<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DataObject\IoT;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;

class MeasurementTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $time = new \DateTime();
        $measurement = 'temperature';
        $tags = ['location' => 'office'];
        $fields = ['value' => 23.5];

        $obj = new Measurement($time, $measurement, $tags, $fields);

        $this->assertSame($time, $obj->getTime());
        $this->assertSame($measurement, $obj->getMeasurement());
        $this->assertSame($tags, $obj->getTags());
        $this->assertSame($fields, $obj->getFields());
    }

    public function testSetters(): void
    {
        $obj = new Measurement();
        $time = new \DateTime();

        $obj->setTime($time)
            ->setMeasurement('humidity')
            ->setTags(['room' => 'kitchen'])
            ->setFields(['value' => 45.2]);

        $this->assertSame($time, $obj->getTime());
        $this->assertSame('humidity', $obj->getMeasurement());
        $this->assertSame(['room' => 'kitchen'], $obj->getTags());
        $this->assertSame(['value' => 45.2], $obj->getFields());
    }
} 