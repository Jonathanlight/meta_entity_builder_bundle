<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\CacheWarmer;

use Meta\EntityBuilderBundle\CacheWarmer\EntitiesSchemaWarmer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class EntitiesSchemaWarmerTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir().'/meta_entity_builder_warmer_test_'.uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testWarmUpCreatesFileWhenNotExists(): void
    {
        $schemaPath = $this->tempDir.'/config/packages/entities.yaml';

        $warmer = new EntitiesSchemaWarmer($schemaPath);
        $warmer->warmUp($this->tempDir.'/cache');

        self::assertFileExists($schemaPath);
        self::assertStringContainsString('entities: {}', (string) file_get_contents($schemaPath));
        self::assertStringContainsString('Meta Entity Builder', (string) file_get_contents($schemaPath));
    }

    public function testWarmUpDoesNotOverwriteExistingFile(): void
    {
        $schemaPath = $this->tempDir.'/entities.yaml';
        $existingContent = "entities:\n    User:\n        table: users\n";
        $this->filesystem->dumpFile($schemaPath, $existingContent);

        $warmer = new EntitiesSchemaWarmer($schemaPath);
        $warmer->warmUp($this->tempDir.'/cache');

        self::assertSame($existingContent, file_get_contents($schemaPath));
    }

    public function testIsOptionalReturnsTrue(): void
    {
        $warmer = new EntitiesSchemaWarmer($this->tempDir.'/entities.yaml');

        self::assertTrue($warmer->isOptional());
    }

    public function testWarmUpReturnsEmptyArray(): void
    {
        $schemaPath = $this->tempDir.'/entities.yaml';

        $warmer = new EntitiesSchemaWarmer($schemaPath);
        $result = $warmer->warmUp($this->tempDir.'/cache');

        self::assertSame([], $result);
    }

    public function testWarmUpCreatesParentDirectories(): void
    {
        $schemaPath = $this->tempDir.'/deep/nested/path/entities.yaml';

        $warmer = new EntitiesSchemaWarmer($schemaPath);
        $warmer->warmUp($this->tempDir.'/cache');

        self::assertFileExists($schemaPath);
    }

    public function testStarterContentContainsExample(): void
    {
        $schemaPath = $this->tempDir.'/entities.yaml';

        $warmer = new EntitiesSchemaWarmer($schemaPath);
        $warmer->warmUp($this->tempDir.'/cache');

        $content = (string) file_get_contents($schemaPath);
        self::assertStringContainsString('Example:', $content);
        self::assertStringContainsString('meta-generate:entity', $content);
    }
}
