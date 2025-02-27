<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DBAL\Functions;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DBAL\Functions\CurrentTimestamp;

class CurrentTimestampTest extends TestCase
{
    public function testDefaultPrecision(): void
    {
        $timestamp = new CurrentTimestamp();
        $this->assertEquals('CURRENT_TIMESTAMP(3)', (string)$timestamp);
    }

    public function testCustomPrecision(): void
    {
        $timestamp = new CurrentTimestamp(1);
        $this->assertEquals('CURRENT_TIMESTAMP(1)', (string)$timestamp);
    }

    public function testInvalidPrecisionTooHigh(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CurrentTimestamp(4);
    }

    public function testInvalidPrecisionNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CurrentTimestamp(-1);
    }
} 