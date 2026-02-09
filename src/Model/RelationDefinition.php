<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Model;

final class RelationDefinition
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $targetEntity;

    /** @var string|null */
    private $mappedBy;

    /** @var string|null */
    private $inversedBy;

    /** @var array<string, string>|null */
    private $joinColumn;

    /** @var array<string, mixed>|null */
    private $joinTable;

    /** @var array<string> */
    private $cascade;

    /** @var bool */
    private $orphanRemoval;

    /**
     * @param array<string, string>|null $joinColumn
     * @param array<string, mixed>|null  $joinTable
     * @param array<string>              $cascade
     */
    public function __construct(
        string $name,
        string $type,
        string $targetEntity,
        ?string $mappedBy = null,
        ?string $inversedBy = null,
        ?array $joinColumn = null,
        ?array $joinTable = null,
        array $cascade = [],
        bool $orphanRemoval = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->targetEntity = $targetEntity;
        $this->mappedBy = $mappedBy;
        $this->inversedBy = $inversedBy;
        $this->joinColumn = $joinColumn;
        $this->joinTable = $joinTable;
        $this->cascade = $cascade;
        $this->orphanRemoval = $orphanRemoval;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    public function getMappedBy(): ?string
    {
        return $this->mappedBy;
    }

    public function getInversedBy(): ?string
    {
        return $this->inversedBy;
    }

    /**
     * @return array<string, string>|null
     */
    public function getJoinColumn(): ?array
    {
        return $this->joinColumn;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJoinTable(): ?array
    {
        return $this->joinTable;
    }

    /**
     * @return array<string>
     */
    public function getCascade(): array
    {
        return $this->cascade;
    }

    public function isOrphanRemoval(): bool
    {
        return $this->orphanRemoval;
    }

    public function isCollection(): bool
    {
        return \in_array($this->type, ['OneToMany', 'ManyToMany'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'targetEntity' => $this->targetEntity,
            'mappedBy' => $this->mappedBy,
            'inversedBy' => $this->inversedBy,
            'joinColumn' => $this->joinColumn,
            'joinTable' => $this->joinTable,
            'cascade' => $this->cascade,
            'orphanRemoval' => $this->orphanRemoval,
        ];
    }
}
