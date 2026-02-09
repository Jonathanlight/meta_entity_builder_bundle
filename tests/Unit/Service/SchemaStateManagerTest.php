<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Service;

use Meta\EntityBuilderBundle\Model\SchemaState;
use Meta\EntityBuilderBundle\Service\SchemaStateManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class SchemaStateManagerTest extends TestCase
{
    private string $tempDir;
    private string $statePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/meta_entity_builder_test_'.uniqid();
        $this->statePath = $this->tempDir.'/.schema_state.json';
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        if ($fs->exists($this->tempDir)) {
            $fs->remove($this->tempDir);
        }
    }

    public function testHasStateReturnsFalseWhenNoFile(): void
    {
        $manager = new SchemaStateManager($this->statePath);

        self::assertFalse($manager->hasState());
    }

    public function testLoadReturnsEmptyStateWhenNoFile(): void
    {
        $manager = new SchemaStateManager($this->statePath);

        $state = $manager->load();

        self::assertSame([], $state->getChecksums());
        self::assertNull($state->getLastGeneratedAt());
    }

    public function testSaveAndLoad(): void
    {
        $manager = new SchemaStateManager($this->statePath);

        $state = new SchemaState(['User' => 'abc123', 'Post' => 'def456'], 1700000000);
        $manager->save($state);

        self::assertTrue($manager->hasState());

        $loaded = $manager->load();
        self::assertSame('abc123', $loaded->getChecksum('User'));
        self::assertSame('def456', $loaded->getChecksum('Post'));
        self::assertSame(1700000000, $loaded->getLastGeneratedAt());
    }

    public function testSaveCreatesDirectory(): void
    {
        $deepPath = $this->tempDir.'/sub/dir/.schema_state.json';
        $manager = new SchemaStateManager($deepPath);

        $manager->save(new SchemaState(['User' => 'abc']));

        self::assertFileExists($deepPath);
    }

    public function testLoadHandlesCorruptedJson(): void
    {
        $fs = new Filesystem();
        $fs->mkdir(\dirname($this->statePath));
        $fs->dumpFile($this->statePath, 'not json');

        $manager = new SchemaStateManager($this->statePath);
        $state = $manager->load();

        self::assertSame([], $state->getChecksums());
    }
}
