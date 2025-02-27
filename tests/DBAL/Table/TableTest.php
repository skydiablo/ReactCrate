<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DBAL\Table;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\Table;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

class TableTest extends TestCase
{
    public function testCreateTable(): void
    {
        $table = Table::create('test_table')
            ->ifNotExists(true)
            ->field(TableField::create(DataType::INTEGER, 'id')->primaryKey(true))
            ->field(TableField::create(DataType::TEXT, 'name')->nullable(false));

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS', (string)$table);
        $this->assertStringContainsString('"test_table"', (string)$table);
        $this->assertStringContainsString('"id" INTEGER PRIMARY KEY', (string)$table);
        $this->assertStringContainsString('"name" TEXT NOT NULL', (string)$table);
    }

    public function testTableWithPartitioning(): void
    {
        $partitionField = TableField::create(DataType::DATE, 'created_date');
        $table = Table::create('logs')
            ->field(TableField::create(DataType::TEXT, 'message'))
            ->field($partitionField)
            ->partitionedBy($partitionField)
            ->shards(3);

        $tableString = (string)$table;
        $this->assertStringContainsString('PARTITIONED BY ("created_date")', $tableString);
        $this->assertStringContainsString('CLUSTERED INTO 3 SHARDS', $tableString);
    }

    public function testTableWithOptions(): void
    {
        $table = Table::create('config')
            ->field(TableField::create(DataType::TEXT, 'key'))
            ->field(TableField::create(DataType::TEXT, 'value'))
            ->setOption('number_of_replicas', '2')
            ->setOption('refresh_interval', '1000');

        $tableString = (string)$table;
        $this->assertStringContainsString('WITH (', $tableString);
        $this->assertStringContainsString('"number_of_replicas" = \'2\'', $tableString);
        $this->assertStringContainsString('"refresh_interval" = \'1000\'', $tableString);
    }
} 