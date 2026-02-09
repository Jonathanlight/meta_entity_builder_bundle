<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

use Symfony\Component\Filesystem\Filesystem;

final class BackupService implements BackupServiceInterface
{
    /** @var string */
    private $backupDirectory;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $backupDirectory, ?Filesystem $filesystem = null)
    {
        $this->backupDirectory = $backupDirectory;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function backup(string $filePath): string
    {
        if (!$this->filesystem->exists($this->backupDirectory)) {
            $this->filesystem->mkdir($this->backupDirectory, 0755);
        }

        $filename = \pathinfo($filePath, \PATHINFO_FILENAME);
        $extension = \pathinfo($filePath, \PATHINFO_EXTENSION);
        $timestamp = \date('Y-m-d_H-i-s');
        $backupFilename = \sprintf('%s_%s.%s', $filename, $timestamp, $extension);
        $backupPath = $this->backupDirectory . '/' . $backupFilename;

        $this->filesystem->copy($filePath, $backupPath, true);

        return $backupPath;
    }
}
