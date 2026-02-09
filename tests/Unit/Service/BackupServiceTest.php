<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Service;

use Meta\EntityBuilderBundle\Service\BackupService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class BackupServiceTest extends TestCase
{
    private string $tempDir;
    private string $backupDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/meta_entity_builder_backup_test_' . uniqid();
        $this->backupDir = $this->tempDir . '/backups';
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testBackupCreatesFile(): void
    {
        $sourceFile = $this->tempDir . '/User.php';
        $this->filesystem->dumpFile($sourceFile, '<?php class User {}');

        $service = new BackupService($this->backupDir);
        $backupPath = $service->backup($sourceFile);

        self::assertFileExists($backupPath);
        self::assertStringStartsWith($this->backupDir . '/User_', $backupPath);
        self::assertStringEndsWith('.php', $backupPath);
        self::assertSame('<?php class User {}', file_get_contents($backupPath));
    }

    public function testBackupCreatesDirectory(): void
    {
        $sourceFile = $this->tempDir . '/Post.php';
        $this->filesystem->dumpFile($sourceFile, '<?php class Post {}');

        self::assertDirectoryDoesNotExist($this->backupDir);

        $service = new BackupService($this->backupDir);
        $service->backup($sourceFile);

        self::assertDirectoryExists($this->backupDir);
    }

    public function testBackupPathContainsTimestamp(): void
    {
        $sourceFile = $this->tempDir . '/Entity.php';
        $this->filesystem->dumpFile($sourceFile, '<?php');

        $service = new BackupService($this->backupDir);
        $backupPath = $service->backup($sourceFile);

        // Should match pattern: Entity_YYYY-MM-DD_HH-MM-SS.php
        self::assertMatchesRegularExpression(
            '/Entity_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.php$/',
            $backupPath
        );
    }
}
