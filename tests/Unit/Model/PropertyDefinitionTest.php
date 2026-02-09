<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Model;

use Meta\EntityBuilderBundle\Model\PropertyDefinition;
use PHPUnit\Framework\TestCase;

final class PropertyDefinitionTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $property = new PropertyDefinition(
            'email',
            'string',
            180,
            false,
            true,
            null,
            false,
            false,
            null,
            null,
            'user_email'
        );

        self::assertSame('email', $property->getName());
        self::assertSame('string', $property->getType());
        self::assertSame(180, $property->getLength());
        self::assertFalse($property->isNullable());
        self::assertTrue($property->isUnique());
        self::assertNull($property->getDefault());
        self::assertFalse($property->isId());
        self::assertFalse($property->isAutoIncrement());
        self::assertNull($property->getPrecision());
        self::assertNull($property->getScale());
        self::assertSame('user_email', $property->getColumnName());
    }

    public function testIdProperty(): void
    {
        $property = new PropertyDefinition(
            'id',
            'integer',
            null,
            false,
            false,
            null,
            true,
            true
        );

        self::assertTrue($property->isId());
        self::assertTrue($property->isAutoIncrement());
    }

    public function testDecimalProperty(): void
    {
        $property = new PropertyDefinition(
            'price',
            'decimal',
            null,
            false,
            false,
            null,
            false,
            false,
            10,
            2
        );

        self::assertSame(10, $property->getPrecision());
        self::assertSame(2, $property->getScale());
    }

    public function testDefaultValues(): void
    {
        $property = new PropertyDefinition('name', 'string');

        self::assertNull($property->getLength());
        self::assertFalse($property->isNullable());
        self::assertFalse($property->isUnique());
        self::assertNull($property->getDefault());
        self::assertFalse($property->isId());
        self::assertFalse($property->isAutoIncrement());
        self::assertNull($property->getPrecision());
        self::assertNull($property->getScale());
        self::assertNull($property->getColumnName());
    }

    public function testToArray(): void
    {
        $property = new PropertyDefinition('email', 'string', 180, false, true);

        $array = $property->toArray();

        self::assertSame('email', $array['name']);
        self::assertSame('string', $array['type']);
        self::assertSame(180, $array['length']);
        self::assertFalse($array['nullable']);
        self::assertTrue($array['unique']);
    }
}
