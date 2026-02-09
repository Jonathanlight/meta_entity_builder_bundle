<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Model;

final class ChangeSet
{
    /** @var array<string> */
    private $addedEntities;

    /** @var array<string> */
    private $modifiedEntities;

    /** @var array<string> */
    private $removedEntities;

    /**
     * @param array<string> $addedEntities
     * @param array<string> $modifiedEntities
     * @param array<string> $removedEntities
     */
    public function __construct(
        array $addedEntities = [],
        array $modifiedEntities = [],
        array $removedEntities = []
    ) {
        $this->addedEntities = $addedEntities;
        $this->modifiedEntities = $modifiedEntities;
        $this->removedEntities = $removedEntities;
    }

    /**
     * @return array<string>
     */
    public function getAddedEntities(): array
    {
        return $this->addedEntities;
    }

    /**
     * @return array<string>
     */
    public function getModifiedEntities(): array
    {
        return $this->modifiedEntities;
    }

    /**
     * @return array<string>
     */
    public function getRemovedEntities(): array
    {
        return $this->removedEntities;
    }

    public function hasChanges(): bool
    {
        return \count($this->addedEntities) > 0
            || \count($this->modifiedEntities) > 0
            || \count($this->removedEntities) > 0;
    }

    public function getTotalChanges(): int
    {
        return \count($this->addedEntities)
            + \count($this->modifiedEntities)
            + \count($this->removedEntities);
    }
}
