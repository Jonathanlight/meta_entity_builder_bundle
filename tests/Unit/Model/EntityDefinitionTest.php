<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Model;

use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\PropertyDefinition;
use Meta\EntityBuilderBundle\Model\RelationDefinition;
use PHPUnit\Framework\TestCase;

final class EntityDefinitionTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $properties = [
            new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
            new PropertyDefinition('name', 'string', 255),
        ];

        $relations = [
            new RelationDefinition('posts', 'OneToMany', 'Post', 'author'),
        ];

        $indexes = ['idx_name' => ['name']];
        $uniqueConstraints = ['uq_name' => ['name']];

        $definition = new EntityDefinition(
            'User',
            'users',
            'App\\Repository\\UserRepository',
            $properties,
            $relations,
            $indexes,
            $uniqueConstraints,
            'abc123'
        );

        self::assertSame('User', $definition->getName());
        self::assertSame('users', $definition->getTable());
        self::assertSame('App\\Repository\\UserRepository', $definition->getRepository());
        self::assertCount(2, $definition->getProperties());
        self::assertCount(1, $definition->getRelations());
        self::assertSame(['idx_name' => ['name']], $definition->getIndexes());
        self::assertSame(['uq_name' => ['name']], $definition->getUniqueConstraints());
        self::assertSame('abc123', $definition->getChecksum());
    }

    public function testDefaultValues(): void
    {
        $definition = new EntityDefinition('Simple');

        self::assertSame('Simple', $definition->getName());
        self::assertNull($definition->getTable());
        self::assertNull($definition->getRepository());
        self::assertSame([], $definition->getProperties());
        self::assertSame([], $definition->getRelations());
        self::assertSame([], $definition->getIndexes());
        self::assertSame([], $definition->getUniqueConstraints());
        self::assertSame('', $definition->getChecksum());
    }

    public function testToArray(): void
    {
        $properties = [
            new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
        ];

        $definition = new EntityDefinition('User', 'users', null, $properties);

        $array = $definition->toArray();

        self::assertSame('User', $array['name']);
        self::assertSame('users', $array['table']);
        self::assertNull($array['repository']);
        self::assertCount(1, $array['properties']);
        self::assertSame('id', $array['properties'][0]['name']);
    }
}
