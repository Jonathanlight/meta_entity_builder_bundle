<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Model;

use Meta\EntityBuilderBundle\Model\RelationDefinition;
use PHPUnit\Framework\TestCase;

final class RelationDefinitionTest extends TestCase
{
    public function testOneToManyRelation(): void
    {
        $relation = new RelationDefinition(
            'posts',
            'OneToMany',
            'Post',
            'author',
            null,
            null,
            null,
            ['persist', 'remove'],
            true
        );

        self::assertSame('posts', $relation->getName());
        self::assertSame('OneToMany', $relation->getType());
        self::assertSame('Post', $relation->getTargetEntity());
        self::assertSame('author', $relation->getMappedBy());
        self::assertNull($relation->getInversedBy());
        self::assertNull($relation->getJoinColumn());
        self::assertNull($relation->getJoinTable());
        self::assertSame(['persist', 'remove'], $relation->getCascade());
        self::assertTrue($relation->isOrphanRemoval());
        self::assertTrue($relation->isCollection());
    }

    public function testManyToOneRelation(): void
    {
        $relation = new RelationDefinition(
            'author',
            'ManyToOne',
            'User',
            null,
            'posts',
            ['name' => 'author_id', 'referencedColumnName' => 'id']
        );

        self::assertSame('ManyToOne', $relation->getType());
        self::assertNull($relation->getMappedBy());
        self::assertSame('posts', $relation->getInversedBy());
        self::assertSame(['name' => 'author_id', 'referencedColumnName' => 'id'], $relation->getJoinColumn());
        self::assertFalse($relation->isCollection());
    }

    public function testManyToManyIsCollection(): void
    {
        $relation = new RelationDefinition('tags', 'ManyToMany', 'Tag');

        self::assertTrue($relation->isCollection());
    }

    public function testOneToOneIsNotCollection(): void
    {
        $relation = new RelationDefinition('profile', 'OneToOne', 'Profile');

        self::assertFalse($relation->isCollection());
    }

    public function testDefaultValues(): void
    {
        $relation = new RelationDefinition('author', 'ManyToOne', 'User');

        self::assertNull($relation->getMappedBy());
        self::assertNull($relation->getInversedBy());
        self::assertNull($relation->getJoinColumn());
        self::assertNull($relation->getJoinTable());
        self::assertSame([], $relation->getCascade());
        self::assertFalse($relation->isOrphanRemoval());
    }

    public function testToArray(): void
    {
        $relation = new RelationDefinition(
            'posts',
            'OneToMany',
            'Post',
            'author',
            null,
            null,
            null,
            ['persist'],
            true
        );

        $array = $relation->toArray();

        self::assertSame('posts', $array['name']);
        self::assertSame('OneToMany', $array['type']);
        self::assertSame('Post', $array['targetEntity']);
        self::assertSame('author', $array['mappedBy']);
        self::assertSame(['persist'], $array['cascade']);
        self::assertTrue($array['orphanRemoval']);
    }
}
