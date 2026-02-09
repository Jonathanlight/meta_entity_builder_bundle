<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Parser;

use Meta\EntityBuilderBundle\Exception\SchemaParseException;
use Meta\EntityBuilderBundle\Parser\YamlSchemaParser;
use PHPUnit\Framework\TestCase;

final class YamlSchemaParserTest extends TestCase
{
    private YamlSchemaParser $parser;

    protected function setUp(): void
    {
        $this->parser = new YamlSchemaParser();
    }

    public function testParseValidSchema(): void
    {
        $definitions = $this->parser->parse(__DIR__ . '/../../Fixtures/valid_schema.yaml');

        self::assertCount(2, $definitions);
        self::assertArrayHasKey('User', $definitions);
        self::assertArrayHasKey('Post', $definitions);

        $user = $definitions['User'];
        self::assertSame('User', $user->getName());
        self::assertSame('users', $user->getTable());
        self::assertSame('App\\Repository\\UserRepository', $user->getRepository());
        self::assertCount(5, $user->getProperties());
        self::assertCount(1, $user->getRelations());
        self::assertNotEmpty($user->getChecksum());

        // Check properties
        $idProp = $user->getProperties()[0];
        self::assertSame('id', $idProp->getName());
        self::assertSame('integer', $idProp->getType());
        self::assertTrue($idProp->isId());
        self::assertTrue($idProp->isAutoIncrement());

        $emailProp = $user->getProperties()[1];
        self::assertSame('email', $emailProp->getName());
        self::assertSame(180, $emailProp->getLength());
        self::assertTrue($emailProp->isUnique());

        // Check relations
        $postsRel = $user->getRelations()[0];
        self::assertSame('posts', $postsRel->getName());
        self::assertSame('OneToMany', $postsRel->getType());
        self::assertSame('Post', $postsRel->getTargetEntity());
        self::assertSame('author', $postsRel->getMappedBy());
        self::assertSame(['persist', 'remove'], $postsRel->getCascade());
        self::assertTrue($postsRel->isOrphanRemoval());

        // Check indexes
        self::assertArrayHasKey('idx_email', $user->getIndexes());
        self::assertSame(['email'], $user->getIndexes()['idx_email']);
    }

    public function testParseMinimalSchema(): void
    {
        $definitions = $this->parser->parse(__DIR__ . '/../../Fixtures/minimal_schema.yaml');

        self::assertCount(1, $definitions);
        self::assertArrayHasKey('SimpleEntity', $definitions);

        $entity = $definitions['SimpleEntity'];
        self::assertNull($entity->getTable());
        self::assertNull($entity->getRepository());
        self::assertCount(2, $entity->getProperties());
        self::assertCount(0, $entity->getRelations());
    }

    public function testParseFileNotFound(): void
    {
        $this->expectException(SchemaParseException::class);
        $this->expectExceptionMessage('Schema file not found');

        $this->parser->parse('/nonexistent/path/schema.yaml');
    }

    public function testParseNoEntitiesKey(): void
    {
        $this->expectException(SchemaParseException::class);
        $this->expectExceptionMessage('Schema must contain an "entities" key');

        $this->parser->parse(__DIR__ . '/../../Fixtures/invalid_schema_no_entities.yaml');
    }

    public function testParseInvalidPropertyType(): void
    {
        $this->expectException(SchemaParseException::class);
        $this->expectExceptionMessage('invalid type "nonexistent_type"');

        $this->parser->parse(__DIR__ . '/../../Fixtures/invalid_schema_bad_type.yaml');
    }

    public function testParseRelationWithoutTargetEntity(): void
    {
        $this->expectException(SchemaParseException::class);
        $this->expectExceptionMessage('must have a "targetEntity" field');

        $this->parser->parse(__DIR__ . '/../../Fixtures/invalid_schema_no_relation_target.yaml');
    }

    public function testChecksumDeterminism(): void
    {
        $definitions1 = $this->parser->parse(__DIR__ . '/../../Fixtures/valid_schema.yaml');
        $definitions2 = $this->parser->parse(__DIR__ . '/../../Fixtures/valid_schema.yaml');

        self::assertSame(
            $definitions1['User']->getChecksum(),
            $definitions2['User']->getChecksum()
        );
    }

    public function testParseAllTypes(): void
    {
        $definitions = $this->parser->parse(__DIR__ . '/../../Fixtures/schema_with_all_types.yaml');

        self::assertArrayHasKey('AllTypes', $definitions);
        $entity = $definitions['AllTypes'];
        self::assertCount(13, $entity->getProperties());
    }
}
