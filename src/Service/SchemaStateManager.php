<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

use Meta\EntityBuilderBundle\Model\SchemaState;
use Symfony\Component\Filesystem\Filesystem;

final class SchemaStateManager implements SchemaStateManagerInterface
{
    /** @var string */
    private $statePath;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $statePath, ?Filesystem $filesystem = null)
    {
        $this->statePath = $statePath;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function load(): SchemaState
    {
        if (!$this->hasState()) {
            return new SchemaState();
        }

        $content = \file_get_contents($this->statePath);
        if ($content === false) {
            return new SchemaState();
        }

        /** @var array<string, mixed>|null $data */
        $data = \json_decode($content, true);
        if (!\is_array($data)) {
            return new SchemaState();
        }

        return SchemaState::fromArray($data);
    }

    public function save(SchemaState $state): void
    {
        $dir = \dirname($this->statePath);
        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        $json = \json_encode($state->toArray(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        $this->filesystem->dumpFile($this->statePath, $json . "\n");
    }

    public function hasState(): bool
    {
        return \file_exists($this->statePath);
    }
}
