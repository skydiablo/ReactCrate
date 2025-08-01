<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Services\Retention;

use DateTimeImmutable;
use DateTimeInterface;
use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\DBAL\Functions\StaticString;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\Table;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

use function React\Promise\resolve;

class Retention
{

    protected const string TABLE_NAME = 'retention_policies';

    public function __construct(
        protected Client $client,
    ) {}

    public function initTable(): PromiseInterface
    {
        $table = new Table();
        $table
            ->name(self::TABLE_NAME)
            ->ifNotExists(true)
            ->field(
                (new TableField())
                    ->name('table_schema')
                    ->type(DataType::VARCHAR)
                    ->length(16)
                    ->nullable(false)
                    ->default(new StaticString('doc'))
                    ->primaryKey(true),
            )
            ->field(
                (new TableField())
                    ->name('table_name')
                    ->type(DataType::VARCHAR)
                    ->length(255)
                    ->nullable(false)
                    ->primaryKey(true),
            )
            ->field(
                (new TableField())
                    ->name('partition_column')
                    ->type(DataType::VARCHAR)
                    ->length(255)
                    ->nullable(false)
                    ->primaryKey(true),
            )
            ->field(
                (new TableField())
                    ->name('retention_period')
                    ->type(DataType::INTEGER)
                    ->nullable(false),
            )
            ->field(
                (new TableField())
                    ->name('strategy')
                    ->type(DataType::VARCHAR)
                    ->length(16)
                    ->nullable(false)
                    ->primaryKey(true),
            )
            ->shards(1);

        return $this->client->query((string)$table);
    }

    public function setPolicy(
        string $table,
        string $column,
        int $period,
        Strategy $strategy = Strategy::DELETE,
        string $schema = 'doc',
    ): PromiseInterface {
        $sql = 'INSERT INTO '.self::TABLE_NAME.' ("table_schema", "table_name", "partition_column", "retention_period", "strategy") 
                VALUES (?,?,?,?,?) 
                ON CONFLICT ("table_schema", "table_name", "partition_column", "strategy") 
                DO UPDATE SET "retention_period" = EXCLUDED."retention_period"';
        return $this->client->query($sql, [$schema, $table, $column, $period, $strategy]);
    }

    public function applyPolicies(Strategy ...$strategies): PromiseInterface
    {
        $now = new DateTimeImmutable();
        $cutoffDate = $now->format('Y-m-d H:i:s');

        // Get all policies for the specified strategies
        $strategyValues = array_map(fn(Strategy $strategy) => "'$strategy->value'", $strategies);
        $sql = "
            SELECT 
                table_schema,
                table_name,
                partition_column,
                retention_period,
                strategy
            FROM ".self::TABLE_NAME."
            WHERE strategy IN (".implode(',', $strategyValues).")";

        return $this->client
            ->query($sql)
            ->then(function ($policies) use ($cutoffDate) {
                $promises = [];
                foreach ($policies['rows'] as $policy) {
                    $schema = $policy['table_schema'];
                    $table = $policy['table_name'];
                    $column = $policy['partition_column'];
                    $period = $policy['retention_period'];
                    $strategy = $policy['strategy'];


                    switch (Strategy::tryFrom($strategy)) {
                        case Strategy::DELETE:
                            $deleteSql = "
                            DELETE FROM $schema.$table 
                            WHERE $column < ?::TIMESTAMP - INTERVAL '$period days'";

                            $promises[] = $this->client
                                ->query($deleteSql, [$cutoffDate])
                                ->then(function ($result) use ($schema, $table, $strategy) {
                                    return [
                                        'action'        => $strategy,
                                        'table'         => "$schema.$table",
                                        'affected_rows' => $result['rowcount'] ?? 0,
                                    ];
                                });
                            break;
                        default:
                            // For future strategies (like ARCHIVE, COMPRESS, etc.)
                            $promises[] = resolve([
                                'action' => 'skipped',
                                'table'  => "$schema.$table",
                                'reason' => "Strategy '$strategy' not implemented yet",
                            ]);
                            break;
                    }
                }

                return \React\Promise\all($promises);
            });
    }

}