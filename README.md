# React Crate

`React Crate` is a PHP library that provides an asynchronous interface to [CrateDB](https://cratedb.com/) via the HTTP API. Built on [ReactPHP](https://reactphp.org/), it is designed for non-blocking I/O — ideal for IoT workloads, high-throughput inserts, and cluster deployments.

## Features

- **Async HTTP client** — SQL queries, cluster status, and table refresh via ReactPHP promises
- **HTTP Basic Auth** — `BasicAuthClient` for password-protected CrateDB instances
- **Cluster support** — `ClusterClient` with pluggable load-balancing strategies
- **IoT service** — time-series measurements with tags, fields, and monthly partitioning
- **Retention policies** — declarative data lifecycle management
- **Schema builder (DBAL)** — fluent `CREATE TABLE` and `CREATE INDEX` statement generation
- **Bulk inserts** — efficient multi-row inserts through CrateDB's bulk arguments API

## Installation

```bash
composer require skydiablo/react-crate
```

### Optional dependencies

```bash
# Doctrine DBAL driver integration (suggested)
composer require doctrine/dbal

# Synchronous execution helpers for the DBAL driver (suggested)
composer require react/async
```

## Requirements

- PHP 8.3 or higher
- CrateDB (HTTP endpoint, default port `4200`)
- `react/http` ^3 (installed automatically)

## Quick start

```php
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;
use SkyDiablo\ReactCrate\Services\IoT;

$client = new Client('http://localhost:4200');
$iot = new IoT($client);

$iot->initTable()->then(function () use ($iot) {
    $measurement = new Measurement(
        new \DateTime(),
        'temperature',
        ['location' => 'office'],
        ['value' => 23.5],
    );

    return $iot->add($measurement);
})->then(function () {
    echo "Measurement stored.\n";
});
```

All client and service methods return `React\Promise\PromiseInterface`. Use `->then()` / `->catch()` or [`react/async`](https://github.com/reactphp/async) for synchronous-style execution.

---

## Client

`Client` is the core HTTP client. It communicates with CrateDB's `_sql` endpoint and sets the default schema to `doc`.

```php
use SkyDiablo\ReactCrate\Client;

$client = new Client('http://localhost:4200');
```

### Connector context

Pass ReactPHP connector options (TLS, timeouts, DNS, etc.) as the second constructor argument:

```php
$client = new Client('https://crate.example.com:4200', [
    'timeout' => 10.0,
    'tls' => [
        'verify_peer' => true,
    ],
]);
```

### `query()`

Execute parameterized SQL statements. Single-row and bulk inserts are detected automatically.

```php
// Single statement
$client->query('SELECT * FROM doc.iot WHERE measurement = ?', ['temperature'])
    ->then(function (array $result) {
        foreach ($result['rows'] as $row) {
            // Rows are associative arrays keyed by column name
            echo $row['measurement'], ': ', $row['fields']['value'], "\n";
        }
    });

// Bulk insert (nested argument arrays)
$client->query(
    'INSERT INTO doc.iot (ts, measurement, tags, fields) VALUES (?, ?, ?, ?)',
    [
        ['2024-01-01T00:00:00Z', 'temp', ['room' => 'a'], ['value' => 21.0]],
        ['2024-01-01T00:01:00Z', 'temp', ['room' => 'b'], ['value' => 22.0]],
    ],
);
```

Enum values in arguments are converted automatically (`BackedEnum` → value, `UnitEnum` → name).

### `getStatus()`

Query the cluster/node status endpoint.

```php
$client->getStatus()->then(function (array $status) {
    print_r($status);
});
```

### `refreshTable()`

Trigger a CrateDB `REFRESH TABLE` for a given table. Table identifiers are validated and quoted.

```php
$client->refreshTable('iot');
$client->refreshTable('doc.iot'); // schema-qualified
```

### Response format

Successful responses are decoded JSON arrays. When `cols` and `rows` are present, each row is transformed into an associative array keyed by column name.

Errors throw `SkyDiablo\ReactCrate\Exceptions\CrateResponseException` with the CrateDB error code.

---

## Authentication

By default, `Client` assumes host-based (trust) authentication. When CrateDB requires username and password for HTTP clients, use `BasicAuthClient`:

```php
use SkyDiablo\ReactCrate\BasicAuthClient;

$client = new BasicAuthClient(
    'https://crate.example.com:4200',
    'myuser',
    'mypassword',
);
```

`BasicAuthClient` extends `Client` and injects an `Authorization: Basic …` header on every request. Connector context is supported as the fourth argument.

For clusters, wrap each node client before passing them to `ClusterClient` (see below).

---

## Cluster client

`ClusterClient` implements `ClientInterface` and distributes requests across multiple node clients.

```php
use SkyDiablo\ReactCrate\BasicAuthClient;
use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\ClusterClient;

$cluster = new ClusterClient([
    new Client('http://node1:4200'),
    new BasicAuthClient('http://node2:4200', 'user', 'pass'),
    new Client('http://node3:4200'),
]);
```

All `ClientInterface` methods (`query`, `getStatus`, `refreshTable`) are delegated to a selected node.

### Client selection strategies

| Selector | Description |
|---|---|
| `RoundRobinClientSelector` | Default — cycles through nodes in order |
| `RandomClientSelector` | Picks a random node per request |
| `LoadClientSelector` | Routes to the node with the fewest in-flight queries |
| `CustomClientSelector` | User-defined callable returning a `ClientInterface` |

```php
use SkyDiablo\ReactCrate\ClientSelection\LoadClientSelector;
use SkyDiablo\ReactCrate\ClientSelection\CustomClientSelector;

// Load-based balancing
$cluster = new ClusterClient($clients, new LoadClientSelector());

// Custom selection logic
$cluster = new ClusterClient($clients, new CustomClientSelector(
    fn(array $clients, string $statement, array $args) => $clients[0],
));
```

Implement `ClientSelectorInterface` for fully custom strategies. The selector receives the statement and arguments on every call, enabling statement-aware routing.

---

## IoT service

The `IoT` service stores time-series measurements in a CrateDB table with a schema inspired by InfluxDB line protocol concepts: timestamp, measurement name, tags (object), and fields (object).

### Table schema

The default table `doc.iot` contains:

| Column | Type | Description |
|---|---|---|
| `ts` | `TIMESTAMP` | Measurement timestamp (defaults to `CURRENT_TIMESTAMP`) |
| `measurement` | `TEXT` | Measurement name |
| `tags` | `OBJECT` | Key-value metadata (indexed for filtering) |
| `fields` | `OBJECT` | Key-value payload |
| `partition_field` | `TIMESTAMP` | Generated column (`DATE_TRUNC('month', ts)`) for partitioning |

The table is partitioned by month and supports configurable shard count and CrateDB `WITH` options.

### Initialization

```php
use SkyDiablo\ReactCrate\Services\IoT;

// Default table name "iot", default shards
$iot = new IoT($client);

// Custom table name, shard count, and CrateDB options
$iot = new IoT(
    client: $client,
    table: 'sensors',
    shards: 4,
    options: ['number_of_replicas' => '1'],
);

$iot->initTable()->then(function () {
    echo "Table ready.\n";
});

// Override shards/options per call
$iot->initTable(shards: 6, options: ['refresh_interval' => '1000']);
```

### Storing measurements

```php
use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;
use SkyDiablo\ReactCrate\DataObject\IoT\BulkMeasurement;

// Single measurement
$measurement = new Measurement(
    time: new \DateTime(),
    measurement: 'temperature',
    tags: ['location' => 'office', 'sensor' => 'ds18b20'],
    fields: ['value' => 23.5, 'unit' => 'celsius'],
);

$iot->add($measurement);

// Without timestamp — current time (UTC) is used as fallback
$iot->add(new Measurement(measurement: 'humidity', fields: ['value' => 65]));

// Bulk insert
$bulk = (new BulkMeasurement())
    ->add(new Measurement(new \DateTime(), 'temp', ['room' => 'a'], ['value' => 21.0]))
    ->add(new Measurement(new \DateTime(), 'temp', ['room' => 'b'], ['value' => 22.0]));

$iot->bulkAdd($bulk);
```

`Measurement` supports fluent setters (`setTime`, `setMeasurement`, `setTags`, `setFields`). `BulkMeasurement` extends `ArrayObject` and validates that every entry is a `Measurement`.

### Refresh

```php
$iot->refreshTable(); // delegates to $client->refreshTable($table)
```

---

## Retention service

`Retention` manages data lifecycle policies stored in `doc.retention_policies`. Policies define how long partitioned data should be kept and which strategy to apply.

### Setup

```php
use SkyDiablo\ReactCrate\Services\Retention\Retention;
use SkyDiablo\ReactCrate\Services\Retention\Strategy;

$retention = new Retention($client);

$retention->initTable()->then(function () use ($retention) {
    // Keep IoT data for 90 days, delete expired partitions
    return $retention->setPolicy(
        table: 'iot',
        column: 'partition_field',
        period: 90,
        strategy: Strategy::DELETE,
    );
});
```

### Applying policies

```php
$retention->applyPolicies(Strategy::DELETE)->then(function (array $results) {
    foreach ($results as $result) {
        echo $result['action'], ' on ', $result['table'],
             ': ', $result['affected_rows'] ?? 0, " rows\n";
    }
});
```

`setPolicy` uses `ON CONFLICT … DO UPDATE` so existing policies are updated rather than duplicated. Currently `Strategy::DELETE` is implemented; additional strategies (archive, compress, etc.) are reserved for future releases.

---

## Schema builder (DBAL)

The DBAL layer generates CrateDB DDL statements through a fluent builder API. Use it directly via `$client->query((string) $table)` or through the built-in services.

### Tables

```php
use SkyDiablo\ReactCrate\DBAL\Functions\CurrentTimestamp;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;
use SkyDiablo\ReactCrate\DBAL\Table\Table;
use SkyDiablo\ReactCrate\DBAL\Table\TableField;

$table = Table::create('events')
    ->ifNotExists(true)
    ->field(
        TableField::create(DataType::INTEGER, 'id')->primaryKey(true),
    )
    ->field(
        TableField::create(DataType::TEXT, 'event_type')->nullable(false),
    )
    ->field(
        (new TableField())
            ->name('created_at')
            ->type(DataType::TIMESTAMP_WITHOUT_TIME_ZONE)
            ->nullable(false)
            ->default(new CurrentTimestamp()),
    )
    ->shards(3)
    ->setOption('number_of_replicas', '1');

$client->query((string) $table);
```

#### `TableField` options

- `type(DataType)` — column data type
- `nullable(bool)` — `NOT NULL` constraint
- `length(int)` — required for `VARCHAR`, `CHAR`, `BIT`, etc.
- `primaryKey(bool)` / `constraint(string)` — primary key definitions
- `default(FunctionDefinition)` — default value expression
- `generatedAlwaysAs(FunctionDefinition)` — generated column expression
- `arrayElementType(DataType)` — element type for `ARRAY` columns

#### `Table` options

- `ifNotExists(bool)` — `CREATE TABLE IF NOT EXISTS`
- `shards(?int)` — `CLUSTERED INTO n SHARDS`
- `partitionedBy(TableField)` — `PARTITIONED BY ("column")`
- `setOption(string, mixed)` — CrateDB `WITH` clause options

### Indexes

```php
use SkyDiablo\ReactCrate\DBAL\Table\Index;

$index = Index::create('iot_measurement_idx')
    ->on('iot')
    ->columns(['measurement'])
    ->ifNotExists();

$client->query((string) $index);
```

### SQL function helpers

Used as defaults or generated column expressions in `TableField`:

| Class | Renders as |
|---|---|
| `CurrentTimestamp` | `CURRENT_TIMESTAMP(n)` (precision 0–3) |
| `DateTrunc` | `DATE_TRUNC('interval', "column")` |
| `StaticString` | Quoted string literal |
| `BooleanValue` | `TRUE` / `FALSE` |
| `NullValue` | `NULL` |

```php
use SkyDiablo\ReactCrate\DBAL\Functions\DateTrunc;
use SkyDiablo\ReactCrate\DBAL\Functions\Enums\DateTruncInterval;

$tsField = TableField::create(DataType::TIMESTAMP_WITHOUT_TIME_ZONE, 'ts');
$partitionField = (new TableField())
    ->name('month')
    ->type(DataType::TIMESTAMP_WITHOUT_TIME_ZONE)
    ->generatedAlwaysAs(new DateTrunc(DateTruncInterval::month, $tsField));
```

### Supported data types

`DataType` covers CrateDB's standard types: `BOOLEAN`, `VARCHAR`, `TEXT`, `INTEGER`, `BIGINT`, `REAL`, `DOUBLE PRECISION`, `TIMESTAMP WITH/WITHOUT TIME ZONE`, `DATE`, `OBJECT`, `ARRAY`, `GEO_POINT`, `GEO_SHAPE`, `IP`, and more. See `SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType` for the full list.

---

## Error handling

| Exception | When |
|---|---|
| `CrateResponseException` | CrateDB returns an error in the HTTP response |
| `InvalidIdentifierException` | Invalid table/column identifier in `refreshTable()` |
| `MeasurementException` | Non-`Measurement` object passed to `BulkMeasurement` |
| `BaseException` | Base class for all library exceptions |

```php
use SkyDiablo\ReactCrate\Exceptions\CrateResponseException;

$client->query('SELECT * FROM nonexistent')->catch(function (\Throwable $e) {
    if ($e instanceof CrateResponseException) {
        echo 'CrateDB error ', $e->getCode(), ': ', $e->getMessage();
    }
});
```

---

## Testing

```bash
composer install
./vendor/bin/phpunit
```

## License

MIT
