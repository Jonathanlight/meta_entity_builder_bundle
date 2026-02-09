<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Event;

use Meta\EntityBuilderBundle\Event\EntityGeneratedEvent;
use Meta\EntityBuilderBundle\Model\EntityDefinition;
use PHPUnit\Framework\TestCase;

final class EntityGeneratedEventTest extends TestCase
{
    public function testEventProperties(): void
    {
        $definition = new EntityDefinition('User');
        $event = new EntityGeneratedEvent('User', '/path/to/User.php', $definition);

        self::assertSame('User', $event->getEntityName());
        self::assertSame('/path/to/User.php', $event->getFilePath());
        self::assertSame($definition, $event->getEntityDefinition());
    }
}
