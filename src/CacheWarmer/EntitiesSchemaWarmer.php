<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\CacheWarmer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class EntitiesSchemaWarmer implements CacheWarmerInterface
{
    /** @var string */
    private $schemaPath;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $schemaPath, ?Filesystem $filesystem = null)
    {
        $this->schemaPath = $schemaPath;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * @return string[]
     */
    public function warmUp(string $cacheDir): array
    {
        if ($this->filesystem->exists($this->schemaPath)) {
            return [];
        }

        $this->filesystem->dumpFile($this->schemaPath, $this->getStarterContent());

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }

    private function getStarterContent(): string
    {
        return <<<'YAML'
# Meta Entity Builder - Entity Schema Definition
# Documentation: https://github.com/Jonathanlight/meta-entity-builder-bundle
#
# Example:
#   entities:
#       User:
#           table: users
#           properties:
#               id:
#                   type: integer
#                   id: true
#                   autoIncrement: true
#               email:
#                   type: string
#                   length: 180
#                   unique: true
#               name:
#                   type: string
#                   length: 255
#
# Run: php bin/console meta-generate:entity

entities: {}
YAML;
    }
}
