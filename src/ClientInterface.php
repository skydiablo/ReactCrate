<?php

namespace SkyDiablo\ReactCrate;

use React\Promise\PromiseInterface;

interface ClientInterface
{
    public function getStatus(): PromiseInterface;

    /**
     * @param string $statement
     * @param array $arguments
     *
     * @return PromiseInterface<array>
     */
    public function query(string $statement, array $arguments = []): PromiseInterface;

    /**
     * Refresh a table (CrateDB-specific operation)
     *
     * @param string $tableName Name of the table to refresh
     *
     * @return PromiseInterface<void>
     */
    public function refreshTable(string $tableName): PromiseInterface;
}