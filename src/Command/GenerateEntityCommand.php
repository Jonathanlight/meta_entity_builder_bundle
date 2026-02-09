<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Command;

use Meta\EntityBuilderBundle\Exception\MetaEntityBuilderException;
use Meta\EntityBuilderBundle\Service\EntityBuilderServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateEntityCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'generate:entity';

    /** @var EntityBuilderServiceInterface */
    private $builderService;

    public function __construct(EntityBuilderServiceInterface $builderService)
    {
        $this->builderService = $builderService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:entity')
            ->setAliases(['meta:entity:generate', 'entity:generate'])
            ->setDescription('Generate Doctrine entities from YAML schema')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force regeneration of all entities (ignore checksums)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without writing files')
            ->addOption('entity', 'e', InputOption::VALUE_REQUIRED, 'Generate only a specific entity')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Meta Entity Builder');

        $options = [
            'force' => (bool) $input->getOption('force'),
            'dryRun' => (bool) $input->getOption('dry-run'),
            'entityFilter' => $input->getOption('entity'),
        ];

        if ($options['dryRun']) {
            $io->note('Dry run mode — no files will be written');
        }

        if ($options['force']) {
            $io->note('Force mode — all entities will be regenerated');
        }

        try {
            $results = $this->builderService->build($options);
        } catch (MetaEntityBuilderException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        /** @var array<string> $created */
        $created = $results['created'] ?? [];
        /** @var array<string> $updated */
        $updated = $results['updated'] ?? [];
        /** @var array<string> $skipped */
        $skipped = $results['skipped'] ?? [];
        /** @var array<array{entity: string, message: string}> $errors */
        $errors = $results['errors'] ?? [];

        if (\count($created) > 0) {
            $io->success(\sprintf('Created %d entit%s: %s', \count($created), 1 === \count($created) ? 'y' : 'ies', implode(', ', $created)));
        }

        if (\count($updated) > 0) {
            $io->success(\sprintf('Updated %d entit%s: %s', \count($updated), 1 === \count($updated) ? 'y' : 'ies', implode(', ', $updated)));
        }

        if (\count($skipped) > 0) {
            $io->comment(\sprintf('Skipped %d entit%s (no changes): %s', \count($skipped), 1 === \count($skipped) ? 'y' : 'ies', implode(', ', $skipped)));
        }

        foreach ($errors as $error) {
            $io->error(\sprintf('Error generating %s: %s', $error['entity'], $error['message']));
        }

        // Summary table
        $io->table(
            ['Status', 'Count'],
            [
                ['Created', (string) \count($created)],
                ['Updated', (string) \count($updated)],
                ['Skipped', (string) \count($skipped)],
                ['Errors', (string) \count($errors)],
            ]
        );

        if (\count($errors) > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
