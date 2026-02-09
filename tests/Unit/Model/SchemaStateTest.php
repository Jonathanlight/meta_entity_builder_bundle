<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Model;

use Meta\EntityBuilderBundle\Model\SchemaState;
use PHPUnit\Framework\TestCase;

final class SchemaStateTest extends TestCase
{
    public function testEmptyState(): void
    {
        $state = new SchemaState();

        self::assertSame([], $state->getChecksums());
        self::assertNull($state->getLastGeneratedAt());
        self::assertFalse($state->hasEntity('User'));
        self::assertNull($state->getChecksum('User'));
    }

    public function testStateWithChecksums(): void
    {
        $checksums = ['User' => 'abc123', 'Post' => 'def456'];
        $state = new SchemaState($checksums, 1700000000);

        self::assertSame($checksums, $state->getChecksums());
        self::assertSame(1700000000, $state->getLastGeneratedAt());
        self::assertTrue($state->hasEntity('User'));
        self::assertSame('abc123', $state->getChecksum('User'));
        self::assertFalse($state->hasEntity('Comment'));
    }

    public function testWithChecksum(): void
    {
        $state = new SchemaState(['User' => 'abc123']);

        $newState = $state->withChecksum('Post', 'def456');

        // Original unchanged
        self::assertFalse($state->hasEntity('Post'));

        // New state has both
        self::assertTrue($newState->hasEntity('User'));
        self::assertTrue($newState->hasEntity('Post'));
        self::assertSame('def456', $newState->getChecksum('Post'));
        self::assertNotNull($newState->getLastGeneratedAt());
    }

    public function testToArray(): void
    {
        $state = new SchemaState(['User' => 'abc'], 1700000000);

        $array = $state->toArray();

        self::assertSame(['User' => 'abc'], $array['checksums']);
        self::assertSame(1700000000, $array['lastGeneratedAt']);
    }

    public function testFromArray(): void
    {
        $data = [
            'checksums' => ['User' => 'abc', 'Post' => 'def'],
            'lastGeneratedAt' => 1700000000,
        ];

        $state = SchemaState::fromArray($data);

        self::assertSame('abc', $state->getChecksum('User'));
        self::assertSame('def', $state->getChecksum('Post'));
        self::assertSame(1700000000, $state->getLastGeneratedAt());
    }

    public function testFromArrayWithMissingKeys(): void
    {
        $state = SchemaState::fromArray([]);

        self::assertSame([], $state->getChecksums());
        self::assertNull($state->getLastGeneratedAt());
    }
}
