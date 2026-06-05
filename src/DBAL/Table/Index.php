<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DBAL\Table;

class Index
{
    protected const string OPTION_NAME = 'name';
    protected const string OPTION_TABLE = 'table';
    protected const string OPTION_COLUMNS = 'columns';
    protected const string OPTION_IF_NOT_EXISTS = 'if_not_exists';

    protected array $options = [];

    public static function create(string $name): self
    {
        return (new self())->name($name);
    }

    public function name(string $name): self
    {
        $this->options[self::OPTION_NAME] = $name;
        return $this;
    }

    public function on(string $table): self
    {
        $this->options[self::OPTION_TABLE] = $table;
        return $this;
    }

    /**
     * @param string[] $columns
     */
    public function columns(array $columns): self
    {
        $this->options[self::OPTION_COLUMNS] = $columns;
        return $this;
    }

    public function ifNotExists(bool $value = true): self
    {
        $this->options[self::OPTION_IF_NOT_EXISTS] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $name = $this->options[self::OPTION_NAME] ?? throw new \InvalidArgumentException('Missing index name');
        $table = $this->options[self::OPTION_TABLE] ?? throw new \InvalidArgumentException('Missing table name');
        $columns = $this->options[self::OPTION_COLUMNS] ?? throw new \InvalidArgumentException('Missing index columns');

        $quotedColumns = array_map(static fn(string $column) => '"'.$column.'"', $columns);

        return sprintf(
            'CREATE INDEX %s"%s" ON "%s" (%s)',
            ($this->options[self::OPTION_IF_NOT_EXISTS] ?? false) ? 'IF NOT EXISTS ' : '',
            $name,
            $table,
            implode(', ', $quotedColumns),
        );
    }
}
