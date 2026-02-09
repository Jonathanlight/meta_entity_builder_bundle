<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Service;

use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\SchemaState;
use Meta\EntityBuilderBundle\Service\SchemaComparator;
use PHPUnit\Framework\TestCase;

final class SchemaComparatorTest extends TestCase
{
    private SchemaComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new SchemaComparator();
    }

    public function testAllNewEntities(): void
    {
        $definitions = [
            'User' => new EntityDefinition('User', null, null, [], [], [], [], 'abc'),
            'Post' => new EntityDefinition('Post', null, null, [], [], [], [], 'def'),
        ];

        $state = new SchemaState();
        $changeSet = $this->comparator->compare($definitions, $state);

        self::assertSame(['User', 'Post'], $changeSet->getAddedEntities());
        self::assertSame([], $changeSet->getModifiedEntities());
        self::assertSame([], $changeSet->getRemovedEntities());
    }

    public function testNoChanges(): void
    {
        $definitions = [
            'User' => new EntityDefinition('User', null, null, [], [], [], [], 'abc'),
        ];

        $state = new SchemaState(['User' => 'abc']);
        $changeSet = $this->comparator->compare($definitions, $state);

        self::assertSame([], $changeSet->getAddedEntities());
        self::assertSame([], $changeSet->getModifiedEntities());
        self::assertSame([], $changeSet->getRemovedEntities());
        self::assertFalse($changeSet->hasChanges());
    }

    public function testModifiedEntity(): void
    {
        $definitions = [
            'User' => new EntityDefinition('User', null, null, [], [], [], [], 'xyz'),
        ];

        $state = new SchemaState(['User' => 'abc']);
        $changeSet = $this->comparator->compare($definitions, $state);

        self::assertSame([], $changeSet->getAddedEntities());
        self::assertSame(['User'], $changeSet->getModifiedEntities());
        self::assertSame([], $changeSet->getRemovedEntities());
    }

    public function testRemovedEntity(): void
    {
        $definitions = [
            'User' => new EntityDefinition('User', null, null, [], [], [], [], 'abc'),
        ];

        $state = new SchemaState(['User' => 'abc', 'OldEntity' => 'old']);
        $changeSet = $this->comparator->compare($definitions, $state);

        self::assertSame([], $changeSet->getAddedEntities());
        self::assertSame([], $changeSet->getModifiedEntities());
        self::assertSame(['OldEntity'], $changeSet->getRemovedEntities());
    }

    public function testMixedChanges(): void
    {
        $definitions = [
            'User' => new EntityDefinition('User', null, null, [], [], [], [], 'abc'),
            'Post' => new EntityDefinition('Post', null, null, [], [], [], [], 'new_checksum'),
            'Comment' => new EntityDefinition('Comment', null, null, [], [], [], [], 'com'),
        ];

        $state = new SchemaState([
            'User' => 'abc',           // unchanged
            'Post' => 'old_checksum',   // modified
            'OldEntity' => 'old',       // removed
        ]);

        $changeSet = $this->comparator->compare($definitions, $state);

        self::assertSame(['Comment'], $changeSet->getAddedEntities());
        self::assertSame(['Post'], $changeSet->getModifiedEntities());
        self::assertSame(['OldEntity'], $changeSet->getRemovedEntities());
    }
}
