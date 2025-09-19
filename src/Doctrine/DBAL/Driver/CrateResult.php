<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver\Result;

/**
 * Result implementation for CrateDB in Doctrine DBAL.
 */
class CrateResult implements Result
{
    private array $result;
    private int $position = 0;

    public function __construct(array $result)
    {
        $this->result = $result;
    }

    public function fetchNumeric(): array|false
    {
        if (!isset($this->result['rows'][$this->position])) {
            return false;
        }
        return $this->result['rows'][$this->position++];
    }

    public function fetchAssociative(): array|false
    {
        if (!isset($this->result['rows'][$this->position])) {
            return false;
        }
        return $this->result['rows'][$this->position++];
    }

    public function fetchOne(): mixed
    {
        $row = $this->fetchNumeric();
        return $row ? $row[0] : false;
    }

    public function fetchAllNumeric(): array
    {
        $rows = $this->result['rows'] ?? [];
        $this->position = count($rows);
        return $rows;
    }

    public function fetchAllAssociative(): array
    {
        $rows = $this->result['rows'] ?? [];
        $this->position = count($rows);
        return $rows;
    }

    public function fetchFirstColumn(): array
    {
        return array_map(function ($row) {
            return $row[0] ?? null;
        }, $this->result['rows'] ?? []);
    }

    public function rowCount(): int
    {
        return $this->result['rowcount'] ?? count($this->result['rows'] ?? []);
    }

    public function columnCount(): int
    {
        return count($this->result['cols'] ?? []);
    }

    public function free(): void
    {
        $this->result = [];
    }
}
