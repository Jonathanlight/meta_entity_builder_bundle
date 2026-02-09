<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Model;

use Meta\EntityBuilderBundle\Model\ChangeSet;
use PHPUnit\Framework\TestCase;

final class ChangeSetTest extends TestCase
{
    public function testEmptyChangeSet(): void
    {
        $changeSet = new ChangeSet();

        self::assertSame([], $changeSet->getAddedEntities());
        self::assertSame([], $changeSet->getModifiedEntities());
        self::assertSame([], $changeSet->getRemovedEntities());
        self::assertFalse($changeSet->hasChanges());
        self::assertSame(0, $changeSet->getTotalChanges());
    }

    public function testChangeSetWithChanges(): void
    {
        $changeSet = new ChangeSet(
            ['User', 'Post'],
            ['Comment'],
            ['OldEntity']
        );

        self::assertSame(['User', 'Post'], $changeSet->getAddedEntities());
        self::assertSame(['Comment'], $changeSet->getModifiedEntities());
        self::assertSame(['OldEntity'], $changeSet->getRemovedEntities());
        self::assertTrue($changeSet->hasChanges());
        self::assertSame(4, $changeSet->getTotalChanges());
    }

    public function testHasChangesWithOnlyAdded(): void
    {
        $changeSet = new ChangeSet(['User']);

        self::assertTrue($changeSet->hasChanges());
        self::assertSame(1, $changeSet->getTotalChanges());
    }
}
