<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Functional\Command;

use Meta\EntityBuilderBundle\Command\GenerateEntityCommand;
use Meta\EntityBuilderBundle\Service\EntityBuilderServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateEntityCommandTest extends TestCase
{
    public function testExecuteWithCreatedEntities(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->method('build')->willReturn([
            'created' => ['User', 'Post'],
            'updated' => [],
            'skipped' => [],
            'errors' => [],
        ]);

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('Created 2 entities: User, Post', $output);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithUpdatedEntities(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->method('build')->willReturn([
            'created' => [],
            'updated' => ['User'],
            'skipped' => ['Post'],
            'errors' => [],
        ]);

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('Updated 1 entity: User', $output);
        self::assertStringContainsString('Skipped 1 entity (no changes): Post', $output);
        self::assertSame(0, $tester->getStatusCode());
    }

    public function testDryRunOption(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->expects(self::once())
            ->method('build')
            ->with(self::callback(function (array $options): bool {
                return true === $options['dryRun'];
            }))
            ->willReturn([
                'created' => ['User'],
                'updated' => [],
                'skipped' => [],
                'errors' => [],
            ]);

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => true]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('Dry run mode', $output);
    }

    public function testForceOption(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->expects(self::once())
            ->method('build')
            ->with(self::callback(function (array $options): bool {
                return true === $options['force'];
            }))
            ->willReturn([
                'created' => [],
                'updated' => ['User'],
                'skipped' => [],
                'errors' => [],
            ]);

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute(['--force' => true]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('Force mode', $output);
    }

    public function testEntityFilterOption(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->expects(self::once())
            ->method('build')
            ->with(self::callback(function (array $options): bool {
                return 'User' === $options['entityFilter'];
            }))
            ->willReturn([
                'created' => ['User'],
                'updated' => [],
                'skipped' => [],
                'errors' => [],
            ]);

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute(['--entity' => 'User']);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteShowsSummaryTable(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->method('build')->willReturn([
            'created' => ['User'],
            'updated' => ['Post'],
            'skipped' => ['Comment'],
            'errors' => [],
        ]);

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('Created', $output);
        self::assertStringContainsString('Updated', $output);
        self::assertStringContainsString('Skipped', $output);
    }

    public function testExecuteWithExceptionReturnsFailure(): void
    {
        $builderService = $this->createMock(EntityBuilderServiceInterface::class);
        $builderService->method('build')->willThrowException(
            new \Meta\EntityBuilderBundle\Exception\SchemaParseException('Schema file not found')
        );

        $command = new GenerateEntityCommand($builderService);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        self::assertStringContainsString('Schema file not found', $output);
        self::assertSame(1, $tester->getStatusCode());
    }
}
