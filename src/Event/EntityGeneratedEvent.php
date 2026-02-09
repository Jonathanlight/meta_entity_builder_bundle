<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Event;

use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Symfony\Contracts\EventDispatcher\Event;

final class EntityGeneratedEvent extends Event
{
    /** @var string */
    private $entityName;

    /** @var string */
    private $filePath;

    /** @var EntityDefinition */
    private $entityDefinition;

    public function __construct(string $entityName, string $filePath, EntityDefinition $entityDefinition)
    {
        $this->entityName = $entityName;
        $this->filePath = $filePath;
        $this->entityDefinition = $entityDefinition;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entityDefinition;
    }
}
