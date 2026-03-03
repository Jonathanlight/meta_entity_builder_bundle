# MetaEntityBuilderBundle

## Project Overview
Symfony bundle that generates Doctrine entities from YAML schema definitions.

## Tech Stack
- PHP 7.4+ (bundle source), PHP 8 attributes in generated output
- Symfony 5.4 / 6.x / 7.x
- Doctrine ORM 2.10+ / 3.x
- PHPUnit 9.x, PHPStan level 8

## Key Commands
```bash
vendor/bin/phpunit              # Run tests (79 tests, 287 assertions)
vendor/bin/phpstan analyse      # Static analysis (level 8)
php bin/console meta-generate:entity  # Generate entities from schema
```

## Architecture
- Namespace: `Meta\EntityBuilderBundle`
- Entry point: `src/MetaEntityBuilderBundle.php`
- DI config: `src/Resources/config/services.yaml`
- Command: `src/Command/GenerateEntityCommand.php`
- All services have interfaces; concrete classes are `final`

## Conventions
- PHP 7.4 compatible syntax in bundle source (`?Type`, no `readonly`/unions)
- Custom code preserved between `// @custom-code-start` / `// @custom-code-end` markers
- Schema change detection via MD5 checksums
- State stored in `var/meta_entity_builder/.schema_state.json`
- Default schema path: `config/entities.yaml`
- Tests in `tests/Unit/` and `tests/Functional/`
