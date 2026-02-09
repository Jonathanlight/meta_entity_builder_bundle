<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Tests\Unit\Generator;

use Meta\EntityBuilderBundle\Generator\EntityGenerator;
use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\PropertyDefinition;
use Meta\EntityBuilderBundle\Model\RelationDefinition;
use PHPUnit\Framework\TestCase;

final class EntityGeneratorTest extends TestCase
{
    private EntityGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new EntityGenerator();
    }

    public function testGenerateSimpleEntity(): void
    {
        $definition = new EntityDefinition(
            'User',
            'users',
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('name', 'string', 255),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('declare(strict_types=1);', $code);
        self::assertStringContainsString('namespace App\\Entity;', $code);
        self::assertStringContainsString('use Doctrine\\ORM\\Mapping as ORM;', $code);
        self::assertStringContainsString('#[ORM\\Entity]', $code);
        self::assertStringContainsString("#[ORM\\Table(name: 'users')]", $code);
        self::assertStringContainsString('class User', $code);
        self::assertStringContainsString('#[ORM\\Id]', $code);
        self::assertStringContainsString('#[ORM\\GeneratedValue]', $code);
        self::assertStringContainsString("private ?int \$id = null;", $code);
        self::assertStringContainsString("private string \$name;", $code);
        self::assertStringContainsString('public function getId(): ?int', $code);
        self::assertStringContainsString('public function getName(): string', $code);
        self::assertStringContainsString('public function setName(string $name): self', $code);
        self::assertStringContainsString('return $this;', $code);
        self::assertStringContainsString('// @custom-code-start', $code);
        self::assertStringContainsString('// @custom-code-end', $code);
    }

    public function testGenerateEntityWithRepository(): void
    {
        $definition = new EntityDefinition(
            'User',
            'users',
            'App\\Repository\\UserRepository',
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('use App\\Repository\\UserRepository;', $code);
        self::assertStringContainsString('#[ORM\\Entity(repositoryClass: UserRepository::class)]', $code);
    }

    public function testGenerateEntityWithNullableProperty(): void
    {
        $definition = new EntityDefinition(
            'Post',
            null,
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('content', 'text', null, true),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('nullable: true', $code);
        self::assertStringContainsString('private ?string $content = null;', $code);
        self::assertStringContainsString('public function getContent(): ?string', $code);
        self::assertStringContainsString('public function setContent(?string $content): self', $code);
    }

    public function testGenerateEntityWithBooleanProperty(): void
    {
        $definition = new EntityDefinition(
            'Post',
            null,
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('published', 'boolean', null, false, false, false),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('private bool $published = false;', $code);
        self::assertStringContainsString('public function isPublished(): bool', $code);
    }

    public function testGenerateEntityWithDefaultValue(): void
    {
        $definition = new EntityDefinition(
            'Post',
            null,
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('status', 'string', 20, false, false, 'draft'),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString("private string \$status = 'draft';", $code);
    }

    public function testGenerateEntityWithOneToManyRelation(): void
    {
        $definition = new EntityDefinition(
            'User',
            'users',
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
            ],
            [
                new RelationDefinition(
                    'posts',
                    'OneToMany',
                    'Post',
                    'author',
                    null,
                    null,
                    null,
                    ['persist', 'remove'],
                    true
                ),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('use Doctrine\\Common\\Collections\\ArrayCollection;', $code);
        self::assertStringContainsString('use Doctrine\\Common\\Collections\\Collection;', $code);
        self::assertStringContainsString("#[ORM\\OneToMany(targetEntity: Post::class, mappedBy: 'author', cascade: ['persist', 'remove'], orphanRemoval: true)]", $code);
        self::assertStringContainsString('private Collection $posts;', $code);
        self::assertStringContainsString('$this->posts = new ArrayCollection();', $code);
        self::assertStringContainsString('public function getPosts(): Collection', $code);
        self::assertStringContainsString('public function addPost(Post $post): self', $code);
        self::assertStringContainsString('public function removePost(Post $post): self', $code);
    }

    public function testGenerateEntityWithManyToOneRelation(): void
    {
        $definition = new EntityDefinition(
            'Post',
            'posts',
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
            ],
            [
                new RelationDefinition(
                    'author',
                    'ManyToOne',
                    'User',
                    null,
                    'posts',
                    ['name' => 'author_id', 'referencedColumnName' => 'id']
                ),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString("#[ORM\\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]", $code);
        self::assertStringContainsString("#[ORM\\JoinColumn(name: 'author_id', referencedColumnName: 'id')]", $code);
        self::assertStringContainsString('private ?User $author = null;', $code);
        self::assertStringContainsString('public function getAuthor(): ?User', $code);
        self::assertStringContainsString('public function setAuthor(?User $author): self', $code);
    }

    public function testGenerateEntityWithIndexes(): void
    {
        $definition = new EntityDefinition(
            'User',
            'users',
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('email', 'string', 180),
            ],
            [],
            ['idx_email' => ['email']]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString("#[ORM\\Index(name: 'idx_email', columns: ['email'])]", $code);
    }

    public function testGenerateEntityWithUniqueConstraints(): void
    {
        $definition = new EntityDefinition(
            'User',
            'users',
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
            ],
            [],
            [],
            ['uq_email' => ['email']]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString("#[ORM\\UniqueConstraint(name: 'uq_email', columns: ['email'])]", $code);
    }

    public function testNoSetterForAutoIncrementId(): void
    {
        $definition = new EntityDefinition(
            'User',
            null,
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('public function getId(): ?int', $code);
        self::assertStringNotContainsString('public function setId(', $code);
    }

    public function testGenerateEntityWithDecimalPrecision(): void
    {
        $definition = new EntityDefinition(
            'Product',
            null,
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('price', 'decimal', null, false, false, null, false, false, 10, 2),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString('precision: 10', $code);
        self::assertStringContainsString('scale: 2', $code);
        self::assertStringContainsString('private string $price;', $code);
    }

    public function testGenerateEntityWithColumnName(): void
    {
        $definition = new EntityDefinition(
            'User',
            null,
            null,
            [
                new PropertyDefinition('id', 'integer', null, false, false, null, true, true),
                new PropertyDefinition('firstName', 'string', 100, false, false, null, false, false, null, null, 'first_name'),
            ]
        );

        $code = $this->generator->generate($definition, 'App\\Entity');

        self::assertStringContainsString("name: 'first_name'", $code);
    }
}
