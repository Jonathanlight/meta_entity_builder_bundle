<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Event;

use Meta\EntityBuilderBundle\Model\ChangeSet;
use Symfony\Contracts\EventDispatcher\Event;

final class EntityUpdatedEvent extends Event
{
    /** @var string */
    private $entityName;

    /** @var string */
    private $filePath;

    /** @var ChangeSet */
    private $changeSet;

    /** @var string|null */
    private $backupPath;

    public function __construct(string $entityName, string $filePath, ChangeSet $changeSet, ?string $backupPath = null)
    {
        $this->entityName = $entityName;
        $this->filePath = $filePath;
        $this->changeSet = $changeSet;
        $this->backupPath = $backupPath;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getChangeSet(): ChangeSet
    {
        return $this->changeSet;
    }

    public function getBackupPath(): ?string
    {
        return $this->backupPath;
    }
}
