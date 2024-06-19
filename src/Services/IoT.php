<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Services;

use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\DataObject\IoT\BulkMeasurement;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;

class IoT
{

    protected const string TABLE_NAME = 'iot';
    protected const string CREATE_TABLE_QUERY = 'CREATE TABLE IF NOT EXISTS "doc"."%s" (
           "ts" TIMESTAMP WITHOUT TIME ZONE,
           "measurement" TEXT,
           "tags" OBJECT(DYNAMIC),
           "fields" OBJECT(DYNAMIC)
        )';
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
        $query = sprintf(self::CREATE_TABLE_QUERY, $this->table);
        return $this->client->query($query);
    }

    public function add(Measurement $measurement): PromiseInterface
    {
        return $this->bulkAdd((new BulkMeasurement())->add($measurement));
    }

    public function bulkAdd(BulkMeasurement $bulkMeasurement): PromiseInterface
    {
        $query = sprintf(self::INSERT_QUERY, $this->table);
        $values = array_map(function (Measurement $measurement) {
            return $this->gatherInsertData($measurement, fn() => new \DateTime());
        }, (array)$bulkMeasurement);
        return $this->client->query($query, $values);
    }

    protected function gatherInsertData(Measurement $measurement, callable $timeFallback): array
    {
        return [
            ($measurement->getTime() ?? $timeFallback())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::W3C), // ts
            $measurement->getMeasurement(), // measurement
            json_encode($measurement->getTags(), JSON_PRESERVE_ZERO_FRACTION), // tags
            json_encode($measurement->getFields(), JSON_PRESERVE_ZERO_FRACTION) // fields
        ];
    }
}