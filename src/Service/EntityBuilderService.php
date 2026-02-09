<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

use Meta\EntityBuilderBundle\Event\EntityGeneratedEvent;
use Meta\EntityBuilderBundle\Event\EntityUpdatedEvent;
use Meta\EntityBuilderBundle\Exception\GenerationException;
use Meta\EntityBuilderBundle\Generator\EntityGeneratorInterface;
use Meta\EntityBuilderBundle\Model\ChangeSet;
use Meta\EntityBuilderBundle\Parser\SchemaParserInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityBuilderService implements EntityBuilderServiceInterface
{
    /** @var SchemaParserInterface */
    private $parser;

    /** @var EntityGeneratorInterface */
    private $generator;

    /** @var SchemaStateManagerInterface */
    private $stateManager;

    /** @var SchemaComparatorInterface */
    private $comparator;

    /** @var BackupServiceInterface */
    private $backupService;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var string */
    private $schemaPath;

    /** @var string */
    private $entityNamespace;

    /** @var string */
    private $entityDirectory;

    /** @var bool */
    private $backupEnabled;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(
        SchemaParserInterface $parser,
        EntityGeneratorInterface $generator,
        SchemaStateManagerInterface $stateManager,
        SchemaComparatorInterface $comparator,
        BackupServiceInterface $backupService,
        EventDispatcherInterface $eventDispatcher,
        string $schemaPath,
        string $entityNamespace,
        string $entityDirectory,
        bool $backupEnabled = true,
        ?Filesystem $filesystem = null
    ) {
        $this->parser = $parser;
        $this->generator = $generator;
        $this->stateManager = $stateManager;
        $this->comparator = $comparator;
        $this->backupService = $backupService;
        $this->eventDispatcher = $eventDispatcher;
        $this->schemaPath = $schemaPath;
        $this->entityNamespace = $entityNamespace;
        $this->entityDirectory = $entityDirectory;
        $this->backupEnabled = $backupEnabled;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function build(array $options = []): array
    {
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dryRun'] ?? false);
        /** @var string|null $entityFilter */
        $entityFilter = $options['entityFilter'] ?? null;

        $definitions = $this->parser->parse($this->schemaPath);
        $state = $this->stateManager->load();
        $changeSet = $this->comparator->compare($definitions, $state);

        $results = [
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'errors' => [],
        ];

        foreach ($definitions as $entityName => $definition) {
            if ($entityFilter !== null && $entityName !== $entityFilter) {
                continue;
            }

            $filePath = $this->entityDirectory . '/' . $entityName . '.php';
            $isNew = \in_array($entityName, $changeSet->getAddedEntities(), true);
            $isModified = \in_array($entityName, $changeSet->getModifiedEntities(), true);

            if (!$force && !$isNew && !$isModified) {
                $results['skipped'][] = $entityName;
                continue;
            }

            if ($dryRun) {
                if ($isNew) {
                    $results['created'][] = $entityName;
                } else {
                    $results['updated'][] = $entityName;
                }
                continue;
            }

            try {
                $backupPath = null;
                $customCode = '';

                // Handle existing file
                if ($this->filesystem->exists($filePath)) {
                    // Backup
                    if ($this->backupEnabled) {
                        $backupPath = $this->backupService->backup($filePath);
                    }

                    // Extract custom code
                    $existingContent = \file_get_contents($filePath);
                    if ($existingContent !== false) {
                        $customCode = $this->extractCustomCode($existingContent);
                    }
                }

                // Generate new entity code
                $code = $this->generator->generate($definition, $this->entityNamespace);

                // Inject preserved custom code
                if ($customCode !== '') {
                    $code = $this->injectCustomCode($code, $customCode);
                }

                // Write file
                if (!$this->filesystem->exists($this->entityDirectory)) {
                    $this->filesystem->mkdir($this->entityDirectory, 0755);
                }

                $this->filesystem->dumpFile($filePath, $code);

                // Update state
                $state = $state->withChecksum($entityName, $definition->getChecksum());

                // Dispatch events
                if ($isNew) {
                    $results['created'][] = $entityName;
                    $this->eventDispatcher->dispatch(
                        new EntityGeneratedEvent($entityName, $filePath, $definition)
                    );
                } else {
                    $results['updated'][] = $entityName;
                    $this->eventDispatcher->dispatch(
                        new EntityUpdatedEvent($entityName, $filePath, $changeSet, $backupPath)
                    );
                }
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'entity' => $entityName,
                    'message' => $e->getMessage(),
                ];
                throw new GenerationException(
                    \sprintf('Failed to generate entity "%s": %s', $entityName, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        if (!$dryRun) {
            $this->stateManager->save($state);
        }

        return $results;
    }

    private function extractCustomCode(string $content): string
    {
        $pattern = '/\/\/\s*@custom-code-start\s*\n(.*?)\/\/\s*@custom-code-end/s';
        if (\preg_match($pattern, $content, $matches)) {
            return \trim($matches[1]);
        }

        return '';
    }

    private function injectCustomCode(string $code, string $customCode): string
    {
        $pattern = '/(\/\/\s*@custom-code-start)\s*\n\s*(\/\/\s*@custom-code-end)/s';
        $replacement = \sprintf("$1\n    %s\n    $2", $customCode);

        $result = \preg_replace($pattern, $replacement, $code);

        return $result !== null ? $result : $code;
    }
}
