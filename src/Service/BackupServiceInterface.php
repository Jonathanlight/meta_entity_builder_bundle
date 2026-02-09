<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

interface BackupServiceInterface
{
    /**
     * Creates a backup of the given file and returns the backup path.
     */
    public function backup(string $filePath): string;
}
