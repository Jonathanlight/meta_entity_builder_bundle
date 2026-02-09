# MetaEntityBuilderBundle

A Symfony bundle that generates Doctrine entities from YAML schemas with smart update detection, custom code preservation, and automatic backups.

## Features

- **YAML-based schema definition** — Define your entities in a clean YAML format
- **Smart update detection** — Only regenerates entities when the schema changes (MD5 checksums)
- **Custom code preservation** — Code between `// @custom-code-start` and `// @custom-code-end` markers is preserved across regeneration
- **Automatic backups** — Timestamped backups before any file is overwritten
- **PHP 8 attributes** — Generated entities use modern Doctrine ORM attributes (`#[ORM\Entity]`, `#[ORM\Column]`, etc.)
- **Fluent setters** — All setters return `$this` for method chaining
- **Collection helpers** — Automatic `add*`/`remove*` methods for `*ToMany` relations
- **Event-driven** — Dispatches events on entity generation and update

## Requirements

- PHP 7.4+
- Symfony 5.4 | 6.x | 7.x
- Doctrine ORM 2.10+ | 3.x

## Installation

```bash
composer require meta/entity-builder-bundle
```

## Configuration

```yaml
# config/packages/meta_entity_builder.yaml
meta_entity_builder:
    schema_path: '%kernel.project_dir%/config/entities.yaml'
    entity_namespace: 'App\Entity'
    entity_directory: '%kernel.project_dir%/src/Entity'
    backup_enabled: true
    backup_directory: '%kernel.project_dir%/var/backups/entities'
    generate_repository: true
    repository_namespace: 'App\Repository'
    strict_mode: true
```

## Schema Definition

Create your entity definitions in YAML:

```yaml
# config/entities.yaml
entities:
    User:
        table: users
        repository: App\Repository\UserRepository
        properties:
            id:
                type: integer
                id: true
                autoIncrement: true
            email:
                type: string
                length: 180
                unique: true
            name:
                type: string
                length: 255
            createdAt:
                type: datetime_immutable
        relations:
            posts:
                type: OneToMany
                targetEntity: Post
                mappedBy: author
                cascade: [persist, remove]
                orphanRemoval: true
        indexes:
            idx_email: [email]

    Post:
        table: posts
        properties:
            id:
                type: integer
                id: true
                autoIncrement: true
            title:
                type: string
                length: 255
            content:
                type: text
                nullable: true
            published:
                type: boolean
                default: false
        relations:
            author:
                type: ManyToOne
                targetEntity: User
                inversedBy: posts
                joinColumn:
                    name: author_id
                    referencedColumnName: id
```

## Usage

### Generate entities

```bash
# Generate all entities
php bin/console generate:entity

# Dry run (preview changes without writing files)
php bin/console generate:entity --dry-run

# Force regeneration (ignore checksums)
php bin/console generate:entity --force

# Generate a specific entity
php bin/console generate:entity --entity=User
```

### Custom Code Preservation

Generated entities include markers where you can add custom code:

```php
class User
{
    // ... generated properties and methods ...

    // @custom-code-start
    // Add your custom methods here — this block is preserved on regeneration
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
    // @custom-code-end
}
```

## Events

The bundle dispatches the following events:

- `Meta\EntityBuilderBundle\Event\EntityGeneratedEvent` — When a new entity is created
- `Meta\EntityBuilderBundle\Event\EntityUpdatedEvent` — When an existing entity is updated

## License

Apache License 2.0 — see [LICENSE](LICENSE) for details.
