# React Crate

`React Crate` ist eine PHP-Bibliothek, die eine Schnittstelle zu CrateDB bietet, um IoT-Daten effizient zu speichern und abzurufen. Diese Bibliothek nutzt die ReactPHP-Architektur, um asynchrone Operationen zu unterstützen.

## Installation

Um die Bibliothek zu installieren, verwenden Sie Composer:

```

composer require skydiablo/react-crate
```

## Anforderungen

- PHP 8.3 oder höher
- CrateDB
- ReactPHP

## Verwendung

### Initialisierung

Um die Bibliothek zu verwenden, müssen Sie zuerst einen `Client` erstellen und die `IoT`-Serviceklasse initialisieren:

```

use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\Services\IoT;

$client = new Client('http://localhost:4200');
$iotService = new IoT($client);
```

 ### Tabelle initialisieren

 Bevor Sie Messungen hinzufügen, sollten Sie die Tabelle mit der Funktion `initTable` initialisieren. Diese Funktion erstellt die notwendige Tabelle in CrateDB, falls sie noch nicht existiert:

 ```

 $iotService->initTable()->then(function() {
     echo "Tabelle erfolgreich initialisiert.";
 });
 ```

### Messungen hinzufügen

Sie können Messungen hinzufügen, indem Sie die `Measurement`-Klasse verwenden:

```

use SkyDiablo\ReactCrate\DataObject\IoT\Measurement;

$measurement = new Measurement(new \DateTime(), 'temperature', ['location' => 'office'], ['value' => 23.5]);
$iotService->addMeasurement($measurement);
```

## Using the Doctrine DBAL Driver

This library provides a custom Doctrine DBAL driver for CrateDB. To use it:

### Requirements
- Doctrine DBAL (^4.0 or compatible version)

Install it via Composer if not already present:
```
composer require doctrine/dbal
```

### Configuration
Use the following configuration to create a Doctrine DBAL connection:

```php
use Doctrine\DBAL\DriverManager;
use SkyDiablo\ReactCrate\Doctrine\DBAL\Types\TypeRegistry;

// Register custom CrateDB types (call this once during bootstrap)
TypeRegistry::registerTypes();

$connectionParams = [
    'url' => 'crate://localhost:4200/doc', // or specify host, port, etc.
    'driverClass' => 'SkyDiablo\\ReactCrate\\Doctrine\\DBAL\\Driver\\CrateDriver',
    // Additional params if needed, e.g., 'host' => 'localhost', 'port' => 4200
];

$connection = DriverManager::getConnection($connectionParams);

// Now you can use $connection as usual with Doctrine DBAL
$connection->executeQuery('SELECT * FROM your_table');
```

**Important:** CrateDB returns TIMESTAMP values as millisecond timestamps (integers), but accepts datetime strings when writing. 
The custom `crate_datetime` type handles this conversion automatically.

Note: CrateDB uses HTTP for connections, and authentication might not be required. Adjust parameters accordingly.

For more details on Doctrine DBAL configuration, refer to the [Doctrine DBAL documentation](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html).