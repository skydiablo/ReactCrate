<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Services;

use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\DataObject\IoT\BulkMeasurement;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;
use SkyDiablo\ReactCrate\DBAL\Functions\DateTrunc;
use SkyDiablo\ReactCrate\DBAL\Functions\Enums\DateTruncInterval;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\Table;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

class IoT
{

    protected const string TABLE_NAME = 'iot';
    protected const string INSERT_QUERY = 'INSERT INTO "doc"."%s" ("ts", "measurement", "tags", "fields") VALUES (?, ?, ?, ?)';

    /**
     * @param Client $client
     * @param string $table
     */
    public function __construct(protected Client $client, protected string $table = self::TABLE_NAME)
    {
    }

    public function initTable(): PromiseInterface
    {
        $table = new Table();
        $table
            ->name($this->table)
            ->ifNotExists(true)
            ->field($tsField = (new TableField())->name('ts')->type(DataType::TIMESTAMP_WITHOUT_TIME_ZONE))
            ->field((new TableField())->name('measurement')->type(DataType::TEXT))
            ->field((new TableField())->name('tags')->type(DataType::OBJECT))
            ->field((new TableField())->name('fields')->type(DataType::OBJECT))
            ->field($monthField = (new TableField())
                ->name('month')
                ->type(DataType::TIMESTAMP_WITHOUT_TIME_ZONE)
                ->generatedAlwaysAs(new DateTrunc(DateTruncInterval::month, $tsField))
            )
            ->partitionedBy($monthField);

        return $this->client->query((string)$table);
    }

    public function add(Measurement $measurement): PromiseInterface
    {
        return $this->bulkAdd((new BulkMeasurement())->add($measurement));
    }

    public function bulkAdd(BulkMeasurement $bulkMeasurement): PromiseInterface
    {
        $now = new \DateTime();
        $query = sprintf(self::INSERT_QUERY, $this->table);
        $values = array_map(function (Measurement $measurement) use ($now) {
            return $this->gatherInsertData($measurement, $now);
        }, (array)$bulkMeasurement);
        return $this->client->query($query, $values);
    }

    protected function gatherInsertData(Measurement $measurement, \DateTimeInterface $timeFallback): array
    {
        return [
            ($measurement->getTime() ?? $timeFallback)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::W3C), // ts
            $measurement->getMeasurement(), // measurement
            json_encode($measurement->getTags(), JSON_PRESERVE_ZERO_FRACTION), // tags
            json_encode($measurement->getFields(), JSON_PRESERVE_ZERO_FRACTION) // fields
        ];
    }
}