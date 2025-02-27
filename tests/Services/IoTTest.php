<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\Services;

use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\DataObject\IoT\BulkMeasurement;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;
use SkyDiablo\ReactCrate\Services\IoT;

class IoTTest extends TestCase
{
    private Client $clientMock;
    private IoT $iotService;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->iotService = new IoT($this->clientMock);
    }

    public function testInitTable(): void
    {
        $this->clientMock->expects($this->once())
            ->method('query')
            ->willReturn($this->createMock(PromiseInterface::class));

        $result = $this->iotService->initTable(3);
        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testAdd(): void
    {
        $measurement = new Measurement(
            new \DateTime(),
            'temperature',
            ['location' => 'room1'],
            ['value' => 23.5]
        );

        $this->clientMock->expects($this->once())
            ->method('query')
            ->willReturn($this->createMock(PromiseInterface::class));

        $result = $this->iotService->add($measurement);
        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testBulkAdd(): void
    {
        $bulk = new BulkMeasurement([
            new Measurement(new \DateTime(), 'temp1'),
            new Measurement(new \DateTime(), 'temp2')
        ]);

        $this->clientMock->expects($this->once())
            ->method('query')
            ->willReturn($this->createMock(PromiseInterface::class));

        $result = $this->iotService->bulkAdd($bulk);
        $this->assertInstanceOf(PromiseInterface::class, $result);
    }
} 