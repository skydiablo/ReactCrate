<?php

namespace SkyDiablo\ReactCrate\DataObject;

/**
 * @todo: add all missing features: https://cratedb.com/docs/crate/reference/en/latest/sql/statements/create-table.html#sql-create-table
 */
class Table
{

    protected const string OPTION_TABLE_NAME = 'table_name';
    protected const string OPTION_FIELDS = 'fields';
    protected const string OPTION_IF_NOT_EXISTS = 'if_not_exists';


    protected Client $client;
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

    public function __toString(): string
    {
        $implodedFields = implode(', ', $this->options[self::OPTION_FIELDS] ?? []);
        $query = sprintf('CREATE TABLE %s%s (%s)',
            ($this->options[self::OPTION_IF_NOT_EXISTS] ?? false) ? 'IF NOT EXISTS ' : '',
            $this->options[self::OPTION_TABLE_NAME],
            $implodedFields
        );
        return $query;
    }


}