<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DBAL\Functions;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DBAL\Functions\DateTrunc;
use SkyDiablo\ReactCrate\DBAL\Functions\Enums\DateTruncInterval;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

class DateTruncTest extends TestCase
{
    public function testDateTruncWithoutTimezone(): void
    {
        $field = TableField::create(DataType::TIMESTAMP_WITH_TIME_ZONE, 'created_at');
        $dateTrunc = new DateTrunc(DateTruncInterval::hour, $field);
        
        $expected = "DATE_TRUNC('hour', \"created_at\")";
        $this->assertEquals($expected, (string)$dateTrunc);
    }

    public function testDateTruncWithTimezone(): void
    {
        $field = TableField::create(DataType::TIMESTAMP_WITH_TIME_ZONE, 'created_at');
        $timezone = new \DateTimeZone('Europe/Berlin');
        $dateTrunc = new DateTrunc(DateTruncInterval::day, $field, $timezone);
        
        $expected = "DATE_TRUNC('day', 'Europe/Berlin', \"created_at\")";
        $this->assertEquals($expected, (string)$dateTrunc);
    }

    public function testDifferentIntervals(): void
    {
        $field = TableField::create(DataType::TIMESTAMP_WITH_TIME_ZONE, 'created_at');

        foreach (DateTruncInterval::cases() as $interval) {
            $dateTrunc = new DateTrunc($interval, $field);
            $expectedString = "DATE_TRUNC('{$interval->name}', \"created_at\")";
            $this->assertStringContainsString($expectedString, (string)$dateTrunc);
        }
    }
} 