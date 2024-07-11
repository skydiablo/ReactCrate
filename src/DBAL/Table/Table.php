<?php

namespace SkyDiablo\ReactCrate\DBAL\Table;

/**
 * @todo: add all missing features: https://cratedb.com/docs/crate/reference/en/latest/sql/statements/create-table.html#sql-create-table
 */
class Table
{

    protected const string OPTION_TABLE_NAME = 'table_name';
    protected const string OPTION_FIELDS = 'fields';
    protected const string OPTION_IF_NOT_EXISTS = 'if_not_exists';
    protected const string OPTION_PARTITIONED_BY = 'partitioned_by';
    protected const string OPTION_SHARDS = 'shards';
    protected const string OPTION_OPTIONS = 'with_options';


    protected array $options = [];

    public static function create(string $name): self
    {
        return (new self())->name($name);
    }

    public function name(string $name): self
    {
        $this->options[self::OPTION_TABLE_NAME] = $name;
        return $this;
    }

    public function field(TableField $field): self
    {
        $this->options[self::OPTION_FIELDS][$field->getName()] = $field;
        return $this;
    }

    public function fields(array $fields): self
    {
        $that = $this;
        array_map(function (TableField $field) use ($that) {
            $that->field($field);
        }, $fields);
        return $this;
    }

    public function ifNotExists(bool $value): self
    {
        $this->options[self::OPTION_IF_NOT_EXISTS] = $value;
        return $this;
    }

    public function partitionedBy(TableField $field): static
    {
        $this->options[self::OPTION_PARTITIONED_BY] = $field;
        return $this;
    }

    public function shards(int $shards): static
    {
        $this->options[self::OPTION_SHARDS] = $shards;
        return $this;
    }

    public function setOption(string $key, string $value): static
    {
        $this->options[self::OPTION_OPTIONS][$key] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $implodedFields = implode(', ', $this->options[self::OPTION_FIELDS] ?? []);
        $query = sprintf('CREATE TABLE %s"%s" (%s) %s',
            ($this->options[self::OPTION_IF_NOT_EXISTS] ?? false) ? 'IF NOT EXISTS ' : '',
            $this->options[self::OPTION_TABLE_NAME],
            $implodedFields,
            (($shards = $this->options[self::OPTION_SHARDS] ?? null) ? sprintf('CLUSTERED INTO %d SHARDS ', $shards) : '') .
            (($field = $this->options[self::OPTION_PARTITIONED_BY] ?? null) ? sprintf('PARTITIONED BY ("%s") ', $field->getName()) : '') .
            (($options = $this->options[self::OPTION_OPTIONS] ?? null) ? sprintf('WITH (%s)', $this->renderOptions($options)) : '')
        );
        return $query;
    }

    protected function renderOptions(array $options): string
    {
        $result = '';
        foreach ($options as $key => $value) {
            $result .= sprintf(',"%s" = ', addslashes($key));
            switch (true) {
                case is_string($value):
                    $result .= sprintf("'%s'", addslashes($value));
                    break;
                case is_numeric($value):
                    $result .= preg_replace('![^\d\.\,]!', '', $value);
                    break;
                case is_bool($value):
                    $result .= $value ? 'true' : 'false';
                    break;
            }
        }
        return ltrim($result, ',');
    }


}