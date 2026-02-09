<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Model;

final class SchemaState
{
    /** @var array<string, string> */
    private $checksums;

    /** @var int|null */
    private $lastGeneratedAt;

    /**
     * @param array<string, string> $checksums
     */
    public function __construct(array $checksums = [], ?int $lastGeneratedAt = null)
    {
        $this->checksums = $checksums;
        $this->lastGeneratedAt = $lastGeneratedAt;
    }

    /**
     * @return array<string, string>
     */
    public function getChecksums(): array
    {
        return $this->checksums;
    }

    public function getChecksum(string $entityName): ?string
    {
        return $this->checksums[$entityName] ?? null;
    }

    public function hasEntity(string $entityName): bool
    {
        return isset($this->checksums[$entityName]);
    }

    public function getLastGeneratedAt(): ?int
    {
        return $this->lastGeneratedAt;
    }

    public function withChecksum(string $entityName, string $checksum): self
    {
        $new = clone $this;
        $new->checksums[$entityName] = $checksum;
        $new->lastGeneratedAt = time();

        return $new;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'checksums' => $this->checksums,
            'lastGeneratedAt' => $this->lastGeneratedAt,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['checksums'] ?? [],
            $data['lastGeneratedAt'] ?? null
        );
    }
}
