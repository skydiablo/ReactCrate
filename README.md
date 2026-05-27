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

### Password authentication (HTTP Basic Auth)

By default, the client assumes host-based (trust) authentication. When CrateDB requires username and password for HTTP clients, use `BasicAuthClient`:

```php
use SkyDiablo\ReactCrate\BasicAuthClient;

$client = new BasicAuthClient(
    'https://crate.example.com:4200',
    'myuser',
    'mypassword',
);
```

For clusters, wrap each node client before passing them to `ClusterClient`.

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
