<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Event;

use Meta\EntityBuilderBundle\Event\EntityUpdatedEvent;
use Meta\EntityBuilderBundle\Model\ChangeSet;
use PHPUnit\Framework\TestCase;

final class EntityUpdatedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $changeSet = new ChangeSet([], ['User'], []);
        $event = new EntityUpdatedEvent('User', '/path/to/User.php', $changeSet, '/path/to/backup.php');

        self::assertSame('User', $event->getEntityName());
        self::assertSame('/path/to/User.php', $event->getFilePath());
        self::assertSame($changeSet, $event->getChangeSet());
        self::assertSame('/path/to/backup.php', $event->getBackupPath());
    }

    public function testEventWithNullBackupPath(): void
    {
        $changeSet = new ChangeSet();
        $event = new EntityUpdatedEvent('User', '/path/to/User.php', $changeSet);

        self::assertNull($event->getBackupPath());
    }
}
