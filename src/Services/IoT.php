<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Services;

use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\DataObject\IoT\BulkMeasurement;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;
use SkyDiablo\ReactCrate\DBAL\Functions\CurrentTimestamp;
use SkyDiablo\ReactCrate\DBAL\Functions\DateTrunc;
use SkyDiablo\ReactCrate\DBAL\Functions\Enums\DateTruncInterval;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\Table;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

class IoT
{

    protected const string TABLE_NAME = 'iot';
    protected const string FIELD_TIMESTAMP = 'ts';
    protected const string FIELD_MEASUREMENT = 'measurement';
    protected const string FIELD_TAGS = 'tags';
    protected const string FIELD_FIELDS = 'fields';
    protected const string FIELD_PARTITION_FIELD = 'partition_field';

    protected string $insertQuery;

    /**
     * @param Client $client
     * @param string $table
     */
    public function __construct(
        protected Client $client,
        protected string $table = self::TABLE_NAME,
        protected ?int $shards = null,
        protected array $options = [],
    ) {
        $this->insertQuery = sprintf(
            'INSERT INTO "doc"."%s" ("%s", "%s", "%s", "%s") VALUES (?, ?, ?, ?)',
            $this->table,
            self::FIELD_TIMESTAMP,
            self::FIELD_MEASUREMENT,
            self::FIELD_TAGS,
            self::FIELD_FIELDS,
        );
    }

    public function initTable(?int $shards = null, ?array $options = null): PromiseInterface
    {
        $table = new Table();
        $table
            ->name($this->table)
            ->ifNotExists(true)
            ->field(
                $tsField = (new TableField())
                    ->name(self::FIELD_TIMESTAMP)
                    ->type(DataType::TIMESTAMP_WITHOUT_TIME_ZONE)
                    ->nullable(false)
                    ->default(new CurrentTimestamp()),
            )
            ->field(
                (new TableField())
                    ->name(self::FIELD_MEASUREMENT)
                    ->type(DataType::TEXT)
                    ->nullable(false),
            )
            ->field(
                (new TableField())
                    ->name(self::FIELD_TAGS)
                    ->type(DataType::OBJECT),
            )
            ->field(
                (new TableField())
                    ->name(self::FIELD_FIELDS)
                    ->type(DataType::OBJECT),
            )
            ->field(
                $partitionField = (new TableField())
                    ->name(self::FIELD_PARTITION_FIELD)
                    ->type(DataType::TIMESTAMP_WITHOUT_TIME_ZONE)
                    ->generatedAlwaysAs(new DateTrunc(DateTruncInterval::month, $tsField)),
            )
            ->shards($shards ?? $this->shards)
            ->partitionedBy($partitionField);
        foreach (($options ?? $this->options) as $key => $value) {
            $table->setOption($key, $value);
        }

        return $this->client->query((string)$table);
    }

    public function add(Measurement $measurement): PromiseInterface
    {
        return $this->bulkAdd((new BulkMeasurement())->add($measurement));
    }

    public function bulkAdd(BulkMeasurement $bulkMeasurement): PromiseInterface
    {
        $now = \DateTimeImmutable::createFromFormat('U.u', sprintf('%F', microtime(true)));
        $values = array_map(function (Measurement $measurement) use ($now) {
            return $this->gatherInsertData($measurement, $now);
        }, (array)$bulkMeasurement);

        return $this->client->query($this->insertQuery, $values);
    }

    protected function gatherInsertData(Measurement $measurement, \DateTimeInterface $timeFallback): array
    {
        return [
            ($measurement->getTime() ?? $timeFallback)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::RFC3339_EXTENDED), // ts
            $measurement->getMeasurement(), // measurement
            $measurement->getTags(), // tags
            $measurement->getFields(), // fields
        ];
    }
}