<?php

namespace SkyDiablo\ReactCrate\DataObject;

use SkyDiablo\ReactCrate\DataObject\Enums\DataType;

class TableField
{

    protected const string OPTION_TYPE = 'type'; // bool
    protected const string OPTION_NAME = 'name'; // bool
    protected const string OPTION_NULLABLE = 'nullable'; // bool
    protected const string OPTION_LENGTH = 'length'; // int
    protected const string OPTION_PRIMARY_KEY = 'primary_key'; // bool
    protected const string OPTION_CONSTRAINT = 'constraint'; // string

    protected array $options;

    public static function create(DataType $type, string $name): self
    {
        return (new self())->type($type)->name($name);
    }

    public function getName(): string
    {
        return $this->options[self::OPTION_NAME];
    }

    /**
     * @param string $name
     * @return $this
     * @todo: check restrictions: https://cratedb.com/docs/crate/reference/en/latest/general/ddl/create-table.html#naming-restrictions
     */
    public function name(string $name): self
    {
        $this->options[self::OPTION_NAME] = $name;
        return $this;
    }

    public function type(DataType $type): self
    {
        $this->options[self::OPTION_TYPE] = $type;
        return $this;
    }

    public function nullable(bool $value): self
    {
        $this->options[self::OPTION_NULLABLE] = $value;
        return $this;
    }

    public function length(int $value): self
    {
        $this->options[self::OPTION_LENGTH] = $value;
        return $this;
    }

    public function primaryKey(bool $value): self
    {
        $this->options[self::OPTION_PRIMARY_KEY] = $value;
        return $this;
    }

    public function constraint(string $value): self
    {
        $this->options[self::OPTION_CONSTRAINT] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $result = $this->options[self::OPTION_NAME] . ' ' . $this->options[self::OPTION_TYPE]->name;
        switch ($this->options[self::OPTION_TYPE]) {
            case DataType::VARCHAR:
            case DataType::CHARACTER:
            case DataType::CHAR:
            case DataType::BIT:
            case DataType::FLOAT_VECTOR:
                $result .= sprintf('(%d)', $this->options[self::OPTION_LENGTH] ?? throw new \InvalidArgumentException(sprintf('Missing "%s" option', self::OPTION_LENGTH)));
                break;
            case DataType::NUMERIC:
                throw new \InvalidArgumentException(sprintf('Type "%s" is not supported as table field', $this->options[self::OPTION_TYPE]->name));
        }
        if (!($this->options[self::OPTION_NULLABLE] ?? true)) {
            $result .= ' NOT NULL';
        }
        if ($constraint = $this->options[self::OPTION_CONSTRAINT] ?? false) {
            $result .= ' CONSTRAINT ' . $constraint . ' PRIMARY KEY';
        } elseif ($this->options[self::OPTION_PRIMARY_KEY] ?? false) {
            $result .= ' PRIMARY KEY';
        }
        return $result;
    }

}