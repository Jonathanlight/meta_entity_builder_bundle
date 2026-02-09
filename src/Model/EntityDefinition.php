<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Model;

final class EntityDefinition
{
    /** @var string */
    private $name;

    /** @var string|null */
    private $table;

    /** @var string|null */
    private $repository;

    /** @var array<PropertyDefinition> */
    private $properties;

    /** @var array<RelationDefinition> */
    private $relations;

    /** @var array<string, array<string>> */
    private $indexes;

    /** @var array<string, array<string>> */
    private $uniqueConstraints;

    /** @var string */
    private $checksum;

    /**
     * @param array<PropertyDefinition>    $properties
     * @param array<RelationDefinition>    $relations
     * @param array<string, array<string>> $indexes
     * @param array<string, array<string>> $uniqueConstraints
     */
    public function __construct(
        string $name,
        ?string $table = null,
        ?string $repository = null,
        array $properties = [],
        array $relations = [],
        array $indexes = [],
        array $uniqueConstraints = [],
        string $checksum = ''
    ) {
        $this->name = $name;
        $this->table = $table;
        $this->repository = $repository;
        $this->properties = $properties;
        $this->relations = $relations;
        $this->indexes = $indexes;
        $this->uniqueConstraints = $uniqueConstraints;
        $this->checksum = $checksum;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    /**
     * @return array<PropertyDefinition>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array<RelationDefinition>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getUniqueConstraints(): array
    {
        return $this->uniqueConstraints;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'table' => $this->table,
            'repository' => $this->repository,
            'properties' => \array_map(static function (PropertyDefinition $p): array {
                return $p->toArray();
            }, $this->properties),
            'relations' => \array_map(static function (RelationDefinition $r): array {
                return $r->toArray();
            }, $this->relations),
            'indexes' => $this->indexes,
            'uniqueConstraints' => $this->uniqueConstraints,
        ];
    }
}
