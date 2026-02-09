<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Parser;

use Meta\EntityBuilderBundle\Exception\SchemaParseException;
use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\PropertyDefinition;
use Meta\EntityBuilderBundle\Model\RelationDefinition;
use Symfony\Component\Yaml\Yaml;

final class YamlSchemaParser implements SchemaParserInterface
{
    private const VALID_PROPERTY_TYPES = [
        'integer', 'smallint', 'bigint', 'string', 'text',
        'boolean', 'decimal', 'float', 'datetime', 'datetime_immutable',
        'date', 'date_immutable', 'time', 'time_immutable',
        'json', 'array', 'simple_array', 'blob', 'guid', 'binary',
    ];

    private const VALID_RELATION_TYPES = [
        'OneToOne', 'OneToMany', 'ManyToOne', 'ManyToMany',
    ];

    /**
     * @return array<string, EntityDefinition>
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new SchemaParseException(\sprintf('Schema file not found: %s', $filePath));
        }

        $content = Yaml::parseFile($filePath);

        if (!\is_array($content)) {
            throw new SchemaParseException(\sprintf('Invalid YAML content in %s', $filePath));
        }

        if (!isset($content['entities']) || !\is_array($content['entities'])) {
            throw new SchemaParseException('Schema must contain an "entities" key with entity definitions');
        }

        $definitions = [];

        foreach ($content['entities'] as $entityName => $entityConfig) {
            if (!\is_string($entityName)) {
                throw new SchemaParseException('Entity name must be a string');
            }

            if (!\is_array($entityConfig)) {
                throw new SchemaParseException(\sprintf('Entity "%s" configuration must be an array', $entityName));
            }

            $definitions[$entityName] = $this->parseEntity($entityName, $entityConfig);
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function parseEntity(string $name, array $config): EntityDefinition
    {
        $table = $config['table'] ?? null;
        $repository = $config['repository'] ?? null;

        $properties = [];
        if (isset($config['properties']) && \is_array($config['properties'])) {
            foreach ($config['properties'] as $propName => $propConfig) {
                $properties[] = $this->parseProperty((string) $propName, $propConfig);
            }
        }

        $relations = [];
        if (isset($config['relations']) && \is_array($config['relations'])) {
            foreach ($config['relations'] as $relName => $relConfig) {
                $relations[] = $this->parseRelation((string) $relName, $relConfig);
            }
        }

        $indexes = [];
        if (isset($config['indexes']) && \is_array($config['indexes'])) {
            foreach ($config['indexes'] as $indexName => $columns) {
                if (!\is_array($columns)) {
                    throw new SchemaParseException(\sprintf(
                        'Index "%s" on entity "%s" must be an array of columns',
                        $indexName,
                        $name
                    ));
                }
                $indexes[(string) $indexName] = array_map('strval', $columns);
            }
        }

        $uniqueConstraints = [];
        if (isset($config['uniqueConstraints']) && \is_array($config['uniqueConstraints'])) {
            foreach ($config['uniqueConstraints'] as $constraintName => $columns) {
                if (!\is_array($columns)) {
                    throw new SchemaParseException(\sprintf(
                        'Unique constraint "%s" on entity "%s" must be an array of columns',
                        $constraintName,
                        $name
                    ));
                }
                $uniqueConstraints[(string) $constraintName] = array_map('strval', $columns);
            }
        }

        $definition = new EntityDefinition(
            $name,
            $table,
            $repository,
            $properties,
            $relations,
            $indexes,
            $uniqueConstraints
        );

        $checksum = md5(serialize($definition->toArray()));

        return new EntityDefinition(
            $name,
            $table,
            $repository,
            $properties,
            $relations,
            $indexes,
            $uniqueConstraints,
            $checksum
        );
    }

    /**
     * @param mixed $config
     */
    private function parseProperty(string $name, $config): PropertyDefinition
    {
        if (!\is_array($config)) {
            throw new SchemaParseException(\sprintf('Property "%s" configuration must be an array', $name));
        }

        if (!isset($config['type'])) {
            throw new SchemaParseException(\sprintf('Property "%s" must have a "type" field', $name));
        }

        $type = (string) $config['type'];

        if (!\in_array($type, self::VALID_PROPERTY_TYPES, true)) {
            throw new SchemaParseException(\sprintf(
                'Property "%s" has invalid type "%s". Valid types: %s',
                $name,
                $type,
                implode(', ', self::VALID_PROPERTY_TYPES)
            ));
        }

        return new PropertyDefinition(
            $name,
            $type,
            isset($config['length']) ? (int) $config['length'] : null,
            (bool) ($config['nullable'] ?? false),
            (bool) ($config['unique'] ?? false),
            $config['default'] ?? null,
            (bool) ($config['id'] ?? false),
            (bool) ($config['autoIncrement'] ?? false),
            isset($config['precision']) ? (int) $config['precision'] : null,
            isset($config['scale']) ? (int) $config['scale'] : null,
            $config['columnName'] ?? null
        );
    }

    /**
     * @param mixed $config
     */
    private function parseRelation(string $name, $config): RelationDefinition
    {
        if (!\is_array($config)) {
            throw new SchemaParseException(\sprintf('Relation "%s" configuration must be an array', $name));
        }

        if (!isset($config['type'])) {
            throw new SchemaParseException(\sprintf('Relation "%s" must have a "type" field', $name));
        }

        $type = (string) $config['type'];

        if (!\in_array($type, self::VALID_RELATION_TYPES, true)) {
            throw new SchemaParseException(\sprintf(
                'Relation "%s" has invalid type "%s". Valid types: %s',
                $name,
                $type,
                implode(', ', self::VALID_RELATION_TYPES)
            ));
        }

        if (!isset($config['targetEntity'])) {
            throw new SchemaParseException(\sprintf('Relation "%s" must have a "targetEntity" field', $name));
        }

        $cascade = [];
        if (isset($config['cascade'])) {
            $cascade = \is_array($config['cascade'])
                ? array_map('strval', $config['cascade'])
                : [(string) $config['cascade']];
        }

        /** @var array<string, string>|null $joinColumn */
        $joinColumn = isset($config['joinColumn']) && \is_array($config['joinColumn'])
            ? $config['joinColumn']
            : null;

        /** @var array<string, mixed>|null $joinTable */
        $joinTable = isset($config['joinTable']) && \is_array($config['joinTable'])
            ? $config['joinTable']
            : null;

        return new RelationDefinition(
            $name,
            $type,
            (string) $config['targetEntity'],
            $config['mappedBy'] ?? null,
            $config['inversedBy'] ?? null,
            $joinColumn,
            $joinTable,
            $cascade,
            (bool) ($config['orphanRemoval'] ?? false)
        );
    }
}
