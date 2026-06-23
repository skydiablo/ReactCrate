<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use SkyDiablo\ReactCrate\Client;
use SkyDiablo\ReactCrate\Services\Retention\Retention;
use SkyDiablo\ReactCrate\Services\Retention\Strategy;

// Initialize client (you'll need to configure connection details)
$client = new Client('http://10.50.0.2:4200');

// Create retention service instance
$retention = new Retention($client);

echo "=== Retention Service Test ===\n\n";

// Test 1: Initialize retention policies table
echo "1. Initializing retention policies table...\n";
try {
    $result = \React\Async\await($retention->initTable());
    echo "✓ Table initialization successful\n";
} catch (Exception $e) {
    echo "✗ Table initialization failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Add a retention policy
echo "2. Adding retention policy...\n";
try {
    $result = \React\Async\await($retention->setPolicy(
        table: 'opnsense_metrics',
        column: 'ts',
        period: 90,
        strategy: Strategy::DELETE,
        schema: 'doc'
    ));
    echo "✓ Policy added successfully\n";
} catch (Exception $e) {
    echo "✗ Policy addition failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Apply retention policies
echo "3. Applying retention policies...\n";
try {
    $result = \React\Async\await($retention->applyPolicies(Strategy::DELETE));
    echo "✓ Policy application initiated\n";
} catch (Exception $e) {
    echo "✗ Policy application failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";

