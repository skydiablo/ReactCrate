<?php

namespace SkyDiablo\ReactCrate\DBAL\Table;

use SkyDiablo\ReactCrate\DBAL\Functions\FunctionDefinition;
use SkyDiablo\ReactCrate\DBAL\Table\Enums\DataType;

class TableField
{

    protected const string OPTION_TYPE = 'type'; // bool
    protected const string OPTION_NAME = 'name'; // bool
    protected const string OPTION_NULLABLE = 'nullable'; // bool
    protected const string OPTION_LENGTH = 'length'; // int
    protected const string OPTION_PRIMARY_KEY = 'primary_key'; // bool
    protected const string OPTION_CONSTRAINT = 'constraint'; // string
    protected const string OPTION_GENERATED_ALWAYS_AS = 'generated_always_as';
    protected const string OPTION_AS = 'as';

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

    public function generatedAlwaysAs(FunctionDefinition $function): self
    {
        $this->options[self::OPTION_GENERATED_ALWAYS_AS] = $function;
        return $this;
    }

    public function as(string $value): self
    {
        $this->options[self::OPTION_AS] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $result = '"'.$this->options[self::OPTION_NAME] . '" ' . $this->options[self::OPTION_TYPE]->value;
        switch ($this->options[self::OPTION_TYPE]) {
            case DataType::VARCHAR:
            case DataType::CHARACTER:
            case DataType::CHAR:
            case DataType::BIT:
                $result .= sprintf('(%d)', $this->options[self::OPTION_LENGTH] ?? throw new \InvalidArgumentException(sprintf('Missing "%s" option', self::OPTION_LENGTH)));
                break;
            case DataType::FLOAT_VECTOR:
                //TODO: special case, not supported yet!
                throw new \InvalidArgumentException($this->options[self::OPTION_TYPE]->value . ' currently not supported!');
            case DataType::NUMERIC: //TODO: why is this type defined?
                throw new \InvalidArgumentException(sprintf('Type "%s" is not supported as table field', $this->options[self::OPTION_TYPE]->name));
        }
        if (!($this->options[self::OPTION_NULLABLE] ?? true)) {
            $result .= ' NOT NULL';
        }

        if ($this->options[self::OPTION_GENERATED_ALWAYS_AS] ?? false) {
            $result .= ' GENERATED ALWAYS AS (' . $this->options[self::OPTION_GENERATED_ALWAYS_AS] . ')';
        }

        if ($constraint = $this->options[self::OPTION_CONSTRAINT] ?? false) {
            $result .= ' CONSTRAINT ' . $constraint . ' PRIMARY KEY';
        } elseif ($this->options[self::OPTION_PRIMARY_KEY] ?? false) {
            $result .= ' PRIMARY KEY';
        }
        if ($this->options[self::OPTION_AS] ?? false) {
            $result .= ' AS ' . $this->options[self::OPTION_AS];
        }
        return $result;
    }

}