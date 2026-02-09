<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Service;

use Meta\EntityBuilderBundle\Generator\EntityGeneratorInterface;
use Meta\EntityBuilderBundle\Model\ChangeSet;
use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\PropertyDefinition;
use Meta\EntityBuilderBundle\Model\SchemaState;
use Meta\EntityBuilderBundle\Parser\SchemaParserInterface;
use Meta\EntityBuilderBundle\Service\BackupServiceInterface;
use Meta\EntityBuilderBundle\Service\EntityBuilderService;
use Meta\EntityBuilderBundle\Service\SchemaComparatorInterface;
use Meta\EntityBuilderBundle\Service\SchemaStateManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityBuilderServiceTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/meta_entity_builder_service_test_' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testBuildCreatesNewEntities(): void
    {
        $definition = new EntityDefinition(
            'User',
            'users',
            null,
            [new PropertyDefinition('id', 'integer', null, false, false, null, true, true)],
            [],
            [],
            [],
            'abc123'
        );

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $definition]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->method('generate')->willReturn('<?php class User {}');

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState());
        $stateManager->expects(self::once())->method('save');

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet(['User'], [], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())->method('dispatch');

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $results = $service->build();

        self::assertSame(['User'], $results['created']);
        self::assertSame([], $results['updated']);
        self::assertSame([], $results['skipped']);
        self::assertFileExists($this->tempDir . '/User.php');
    }

    public function testBuildSkipsUnchangedEntities(): void
    {
        $definition = new EntityDefinition(
            'User', null, null, [], [], [], [], 'abc'
        );

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $definition]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->expects(self::never())->method('generate');

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState(['User' => 'abc']));

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet([], [], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $results = $service->build();

        self::assertSame([], $results['created']);
        self::assertSame(['User'], $results['skipped']);
    }

    public function testDryRunDoesNotWriteFiles(): void
    {
        $definition = new EntityDefinition(
            'User', null, null, [], [], [], [], 'abc'
        );

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $definition]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->expects(self::never())->method('generate');

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState());
        $stateManager->expects(self::never())->method('save');

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet(['User'], [], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $results = $service->build(['dryRun' => true]);

        self::assertSame(['User'], $results['created']);
        self::assertFileDoesNotExist($this->tempDir . '/User.php');
    }

    public function testForceRegeneratesAllEntities(): void
    {
        $definition = new EntityDefinition(
            'User', null, null, [], [], [], [], 'abc'
        );

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $definition]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('<?php class User {}');

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState(['User' => 'abc']));

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet([], [], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $results = $service->build(['force' => true]);

        self::assertContains('User', array_merge($results['created'], $results['updated']));
    }

    public function testEntityFilterOnlyProcessesMatchingEntity(): void
    {
        $userDef = new EntityDefinition('User', null, null, [], [], [], [], 'abc');
        $postDef = new EntityDefinition('Post', null, null, [], [], [], [], 'def');

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $userDef, 'Post' => $postDef]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('<?php class User {}');

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState());

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet(['User', 'Post'], [], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $results = $service->build(['entityFilter' => 'User']);

        self::assertSame(['User'], $results['created']);
    }

    public function testBackupIsCreatedForExistingFile(): void
    {
        $existingFile = $this->tempDir . '/User.php';
        $this->filesystem->dumpFile($existingFile, '<?php class User { /* old */ }');

        $definition = new EntityDefinition('User', null, null, [], [], [], [], 'new');

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $definition]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->method('generate')->willReturn('<?php class User { /* new */ }');

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState(['User' => 'old']));

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet([], ['User'], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $backupService->expects(self::once())->method('backup')
            ->with($existingFile)
            ->willReturn('/backups/User_backup.php');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $results = $service->build();

        self::assertSame(['User'], $results['updated']);
    }

    public function testCustomCodePreservation(): void
    {
        $existingFile = $this->tempDir . '/User.php';
        $existingContent = <<<'PHP'
<?php
class User {
    // @custom-code-start
    public function customMethod(): string
    {
        return 'custom';
    }
    // @custom-code-end
}
PHP;
        $this->filesystem->dumpFile($existingFile, $existingContent);

        $definition = new EntityDefinition('User', null, null, [], [], [], [], 'new');

        $generatedCode = <<<'PHP'
<?php
class User {
    // @custom-code-start
    // @custom-code-end
}
PHP;

        $parser = $this->createMock(SchemaParserInterface::class);
        $parser->method('parse')->willReturn(['User' => $definition]);

        $generator = $this->createMock(EntityGeneratorInterface::class);
        $generator->method('generate')->willReturn($generatedCode);

        $stateManager = $this->createMock(SchemaStateManagerInterface::class);
        $stateManager->method('load')->willReturn(new SchemaState(['User' => 'old']));

        $comparator = $this->createMock(SchemaComparatorInterface::class);
        $comparator->method('compare')->willReturn(new ChangeSet([], ['User'], []));

        $backupService = $this->createMock(BackupServiceInterface::class);
        $backupService->method('backup')->willReturn('/backups/User_backup.php');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new EntityBuilderService(
            $parser,
            $generator,
            $stateManager,
            $comparator,
            $backupService,
            $eventDispatcher,
            '/dummy/schema.yaml',
            'App\\Entity',
            $this->tempDir,
            true,
            $this->filesystem
        );

        $service->build();

        $writtenContent = file_get_contents($existingFile);
        self::assertNotFalse($writtenContent);
        self::assertStringContainsString('customMethod', $writtenContent);
        self::assertStringContainsString('// @custom-code-start', $writtenContent);
        self::assertStringContainsString('// @custom-code-end', $writtenContent);
    }
}
