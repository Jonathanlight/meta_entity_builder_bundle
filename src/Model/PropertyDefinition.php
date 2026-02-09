<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Model;

final class PropertyDefinition
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var int|null */
    private $length;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $unique;

    /** @var mixed */
    private $default;

    /** @var bool */
    private $id;

    /** @var bool */
    private $autoIncrement;

    /** @var int|null */
    private $precision;

    /** @var int|null */
    private $scale;

    /** @var string|null */
    private $columnName;

    /**
     * @param mixed $default
     */
    public function __construct(
        string $name,
        string $type,
        ?int $length = null,
        bool $nullable = false,
        bool $unique = false,
        $default = null,
        bool $id = false,
        bool $autoIncrement = false,
        ?int $precision = null,
        ?int $scale = null,
        ?string $columnName = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->nullable = $nullable;
        $this->unique = $unique;
        $this->default = $default;
        $this->id = $id;
        $this->autoIncrement = $autoIncrement;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->columnName = $columnName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function isId(): bool
    {
        return $this->id;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'length' => $this->length,
            'nullable' => $this->nullable,
            'unique' => $this->unique,
            'default' => $this->default,
            'id' => $this->id,
            'autoIncrement' => $this->autoIncrement,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'columnName' => $this->columnName,
        ];
    }
}
