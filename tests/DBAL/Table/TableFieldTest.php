<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DBAL\Table;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DBAL\Functions\CurrentTimestamp;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

class TableFieldTest extends TestCase
{
    public function testCreateAndToString(): void
    {
        $field = TableField::create(DataType::TEXT, 'test_field')
            ->nullable(false)
            ->default(new CurrentTimestamp());

        $expected = '"test_field" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP(3)';
        $this->assertEquals($expected, (string)$field);
    }

    public function testPrimaryKeyField(): void
    {
        $field = TableField::create(DataType::INTEGER, 'id')
            ->primaryKey(true);

        $expected = '"id" INTEGER PRIMARY KEY';
        $this->assertEquals($expected, (string)$field);
    }

    public function testVarcharWithLength(): void
    {
        $field = TableField::create(DataType::VARCHAR, 'name')
            ->length(255);

        $expected = '"name" VARCHAR(255)';
        $this->assertEquals($expected, (string)$field);
    }

    public function testInvalidVarcharWithoutLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $field = TableField::create(DataType::VARCHAR, 'name');
        (string)$field; // Should throw exception because length is required
    }
} 