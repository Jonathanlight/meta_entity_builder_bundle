<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Generator;

use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\PropertyDefinition;
use Meta\EntityBuilderBundle\Model\RelationDefinition;

final class EntityGenerator implements EntityGeneratorInterface
{
    private const TYPE_MAP = [
        'integer' => 'int',
        'smallint' => 'int',
        'bigint' => 'int',
        'string' => 'string',
        'text' => 'string',
        'boolean' => 'bool',
        'decimal' => 'string',
        'float' => 'float',
        'datetime' => '\DateTimeInterface',
        'datetime_immutable' => '\DateTimeImmutable',
        'date' => '\DateTimeInterface',
        'date_immutable' => '\DateTimeImmutable',
        'time' => '\DateTimeInterface',
        'time_immutable' => '\DateTimeImmutable',
        'json' => 'array',
        'array' => 'array',
        'simple_array' => 'array',
        'blob' => 'string',
        'guid' => 'string',
        'binary' => 'string',
    ];

    public function generate(EntityDefinition $definition, string $namespace): string
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = \sprintf('namespace %s;', $namespace);
        $lines[] = '';

        $useStatements = $this->buildUseStatements($definition, $namespace);
        foreach ($useStatements as $use) {
            $lines[] = \sprintf('use %s;', $use);
        }

        if (\count($useStatements) > 0) {
            $lines[] = '';
        }

        $classAttributes = $this->buildClassAttributes($definition);
        foreach ($classAttributes as $attr) {
            $lines[] = $attr;
        }

        $lines[] = \sprintf('class %s', $definition->getName());
        $lines[] = '{';

        $properties = $this->buildProperties($definition, $namespace);
        $lines[] = $properties;

        $constructor = $this->buildConstructor($definition, $namespace);
        if ($constructor !== '') {
            $lines[] = $constructor;
        }

        $methods = $this->buildMethods($definition, $namespace);
        $lines[] = $methods;

        $lines[] = '    // @custom-code-start';
        $lines[] = '    // @custom-code-end';
        $lines[] = '}';
        $lines[] = '';

        return \implode("\n", $lines);
    }

    /**
     * @return array<string>
     */
    private function buildUseStatements(EntityDefinition $definition, string $namespace): array
    {
        $uses = [
            'Doctrine\ORM\Mapping as ORM',
        ];

        $needsCollection = false;
        foreach ($definition->getRelations() as $relation) {
            if ($relation->isCollection()) {
                $needsCollection = true;
                break;
            }
        }

        if ($needsCollection) {
            $uses[] = 'Doctrine\Common\Collections\ArrayCollection';
            $uses[] = 'Doctrine\Common\Collections\Collection';
        }

        foreach ($definition->getRelations() as $relation) {
            $targetEntity = $relation->getTargetEntity();
            if (\strpos($targetEntity, '\\') !== false) {
                $targetNamespace = \substr($targetEntity, 0, (int) \strrpos($targetEntity, '\\'));
                if ($targetNamespace !== $namespace) {
                    $uses[] = $targetEntity;
                }
            }
        }

        if ($definition->getRepository() !== null) {
            $uses[] = $definition->getRepository();
        }

        \sort($uses);

        return \array_unique($uses);
    }

    /**
     * @return array<string>
     */
    private function buildClassAttributes(EntityDefinition $definition): array
    {
        $attrs = [];

        $entityArgs = [];
        if ($definition->getRepository() !== null) {
            $shortName = $this->getShortClassName($definition->getRepository());
            $entityArgs[] = \sprintf('repositoryClass: %s::class', $shortName);
        }

        $attrs[] = \count($entityArgs) > 0
            ? \sprintf('#[ORM\Entity(%s)]', \implode(', ', $entityArgs))
            : '#[ORM\Entity]';

        if ($definition->getTable() !== null) {
            $attrs[] = \sprintf("#[ORM\\Table(name: '%s')]", $definition->getTable());

            foreach ($definition->getIndexes() as $indexName => $columns) {
                $colList = \implode("', '", $columns);
                $attrs[] = \sprintf("#[ORM\\Index(name: '%s', columns: ['%s'])]", $indexName, $colList);
            }

            foreach ($definition->getUniqueConstraints() as $constraintName => $columns) {
                $colList = \implode("', '", $columns);
                $attrs[] = \sprintf("#[ORM\\UniqueConstraint(name: '%s', columns: ['%s'])]", $constraintName, $colList);
            }
        }

        return $attrs;
    }

    private function buildProperties(EntityDefinition $definition, string $namespace): string
    {
        $lines = [];

        foreach ($definition->getProperties() as $property) {
            $propLines = $this->buildPropertyCode($property);
            $lines[] = $propLines;
        }

        foreach ($definition->getRelations() as $relation) {
            $relLines = $this->buildRelationPropertyCode($relation, $namespace);
            $lines[] = $relLines;
        }

        return \implode("\n", $lines);
    }

    private function buildPropertyCode(PropertyDefinition $property): string
    {
        $lines = [];

        if ($property->isId()) {
            $lines[] = '    #[ORM\Id]';
            if ($property->isAutoIncrement()) {
                $lines[] = '    #[ORM\GeneratedValue]';
            }
        }

        $columnArgs = [\sprintf("type: '%s'", $property->getType())];

        if ($property->getColumnName() !== null) {
            $columnArgs[] = \sprintf("name: '%s'", $property->getColumnName());
        }

        if ($property->getLength() !== null) {
            $columnArgs[] = \sprintf('length: %d', $property->getLength());
        }

        if ($property->getPrecision() !== null) {
            $columnArgs[] = \sprintf('precision: %d', $property->getPrecision());
        }

        if ($property->getScale() !== null) {
            $columnArgs[] = \sprintf('scale: %d', $property->getScale());
        }

        if ($property->isUnique()) {
            $columnArgs[] = 'unique: true';
        }

        if ($property->isNullable()) {
            $columnArgs[] = 'nullable: true';
        }

        $lines[] = \sprintf('    #[ORM\Column(%s)]', \implode(', ', $columnArgs));

        $phpType = self::TYPE_MAP[$property->getType()] ?? 'mixed';
        $defaultStr = '';

        if ($property->isId() && $property->isAutoIncrement()) {
            $phpType = '?' . $phpType;
            $defaultStr = ' = null';
        } elseif ($property->getDefault() !== null) {
            $defaultStr = ' = ' . $this->formatDefaultValue($property->getDefault(), $phpType);
        } elseif ($property->isNullable()) {
            $phpType = '?' . $phpType;
            $defaultStr = ' = null';
        }

        $lines[] = \sprintf('    private %s $%s%s;', $phpType, $property->getName(), $defaultStr);
        $lines[] = '';

        return \implode("\n", $lines);
    }

    private function buildRelationPropertyCode(RelationDefinition $relation, string $namespace): string
    {
        $lines = [];
        $targetShort = $this->resolveTargetEntityShort($relation->getTargetEntity(), $namespace);

        $attrArgs = [\sprintf('targetEntity: %s::class', $targetShort)];

        if ($relation->getMappedBy() !== null) {
            $attrArgs[] = \sprintf("mappedBy: '%s'", $relation->getMappedBy());
        }

        if ($relation->getInversedBy() !== null) {
            $attrArgs[] = \sprintf("inversedBy: '%s'", $relation->getInversedBy());
        }

        if (\count($relation->getCascade()) > 0) {
            $cascadeList = \implode("', '", $relation->getCascade());
            $attrArgs[] = \sprintf("cascade: ['%s']", $cascadeList);
        }

        if ($relation->isOrphanRemoval()) {
            $attrArgs[] = 'orphanRemoval: true';
        }

        $lines[] = \sprintf('    #[ORM\%s(%s)]', $relation->getType(), \implode(', ', $attrArgs));

        $joinColumn = $relation->getJoinColumn();
        if ($joinColumn !== null) {
            $jcArgs = [];
            if (isset($joinColumn['name'])) {
                $jcArgs[] = \sprintf("name: '%s'", $joinColumn['name']);
            }
            if (isset($joinColumn['referencedColumnName'])) {
                $jcArgs[] = \sprintf("referencedColumnName: '%s'", $joinColumn['referencedColumnName']);
            }
            if (isset($joinColumn['nullable'])) {
                $jcArgs[] = \sprintf('nullable: %s', $joinColumn['nullable'] ? 'true' : 'false');
            }
            $lines[] = \sprintf('    #[ORM\JoinColumn(%s)]', \implode(', ', $jcArgs));
        }

        $joinTable = $relation->getJoinTable();
        if ($joinTable !== null) {
            $jtArgs = [];
            if (isset($joinTable['name'])) {
                $jtArgs[] = \sprintf("name: '%s'", $joinTable['name']);
            }
            $lines[] = \sprintf('    #[ORM\JoinTable(%s)]', \implode(', ', $jtArgs));
        }

        if ($relation->isCollection()) {
            $lines[] = \sprintf('    private Collection $%s;', $relation->getName());
        } else {
            $lines[] = \sprintf('    private ?%s $%s = null;', $targetShort, $relation->getName());
        }

        $lines[] = '';

        return \implode("\n", $lines);
    }

    private function buildConstructor(EntityDefinition $definition, string $namespace): string
    {
        $collectionRelations = [];
        foreach ($definition->getRelations() as $relation) {
            if ($relation->isCollection()) {
                $collectionRelations[] = $relation;
            }
        }

        // Also check for array/json properties that need default initialization
        $arrayProperties = [];
        foreach ($definition->getProperties() as $property) {
            $phpType = self::TYPE_MAP[$property->getType()] ?? 'mixed';
            if ($phpType === 'array' && $property->getDefault() === null && !$property->isNullable()) {
                $arrayProperties[] = $property;
            }
        }

        if (\count($collectionRelations) === 0 && \count($arrayProperties) === 0) {
            return '';
        }

        $lines = [];
        $lines[] = '    public function __construct()';
        $lines[] = '    {';

        foreach ($collectionRelations as $relation) {
            $lines[] = \sprintf('        $this->%s = new ArrayCollection();', $relation->getName());
        }

        foreach ($arrayProperties as $property) {
            $lines[] = \sprintf('        $this->%s = [];', $property->getName());
        }

        $lines[] = '    }';
        $lines[] = '';

        return \implode("\n", $lines);
    }

    private function buildMethods(EntityDefinition $definition, string $namespace): string
    {
        $lines = [];

        foreach ($definition->getProperties() as $property) {
            $lines[] = $this->buildGetterSetter($property);
        }

        foreach ($definition->getRelations() as $relation) {
            $lines[] = $this->buildRelationMethods($relation, $namespace);
        }

        return \implode("\n", $lines);
    }

    private function buildGetterSetter(PropertyDefinition $property): string
    {
        $lines = [];
        $phpType = self::TYPE_MAP[$property->getType()] ?? 'mixed';
        $name = $property->getName();
        $ucName = \ucfirst($name);

        $returnType = $phpType;
        if ($property->isId() && $property->isAutoIncrement()) {
            $returnType = '?' . $phpType;
        } elseif ($property->isNullable()) {
            $returnType = '?' . $phpType;
        }

        // Getter
        if ($phpType === 'bool') {
            $getterName = 'is' . $ucName;
        } else {
            $getterName = 'get' . $ucName;
        }

        $lines[] = \sprintf('    public function %s(): %s', $getterName, $returnType);
        $lines[] = '    {';
        $lines[] = \sprintf('        return $this->%s;', $name);
        $lines[] = '    }';
        $lines[] = '';

        // Setter (skip for auto-increment IDs)
        if (!($property->isId() && $property->isAutoIncrement())) {
            $paramType = $phpType;
            if ($property->isNullable()) {
                $paramType = '?' . $phpType;
            }

            $lines[] = \sprintf('    public function set%s(%s $%s): self', $ucName, $paramType, $name);
            $lines[] = '    {';
            $lines[] = \sprintf('        $this->%s = $%s;', $name, $name);
            $lines[] = '';
            $lines[] = '        return $this;';
            $lines[] = '    }';
            $lines[] = '';
        }

        return \implode("\n", $lines);
    }

    private function buildRelationMethods(RelationDefinition $relation, string $namespace): string
    {
        $lines = [];
        $name = $relation->getName();
        $ucName = \ucfirst($name);
        $targetShort = $this->resolveTargetEntityShort($relation->getTargetEntity(), $namespace);

        if ($relation->isCollection()) {
            // Getter returns Collection
            $lines[] = \sprintf('    /**');
            $lines[] = \sprintf('     * @return Collection<%s>', $targetShort);
            $lines[] = \sprintf('     */');
            $lines[] = \sprintf('    public function get%s(): Collection', $ucName);
            $lines[] = '    {';
            $lines[] = \sprintf('        return $this->%s;', $name);
            $lines[] = '    }';
            $lines[] = '';

            // Singular name for add/remove
            $singular = $this->singularize($name);
            $ucSingular = \ucfirst($singular);

            // Add method
            $lines[] = \sprintf('    public function add%s(%s $%s): self', $ucSingular, $targetShort, $singular);
            $lines[] = '    {';
            $lines[] = \sprintf('        if (!$this->%s->contains($%s)) {', $name, $singular);
            $lines[] = \sprintf('            $this->%s->add($%s);', $name, $singular);

            // Set inverse side for OneToMany
            if ($relation->getType() === 'OneToMany' && $relation->getMappedBy() !== null) {
                $lines[] = \sprintf('            $%s->set%s($this);', $singular, \ucfirst($relation->getMappedBy()));
            }

            $lines[] = '        }';
            $lines[] = '';
            $lines[] = '        return $this;';
            $lines[] = '    }';
            $lines[] = '';

            // Remove method
            $lines[] = \sprintf('    public function remove%s(%s $%s): self', $ucSingular, $targetShort, $singular);
            $lines[] = '    {';
            $lines[] = \sprintf('        if ($this->%s->removeElement($%s)) {', $name, $singular);

            if ($relation->getType() === 'OneToMany' && $relation->getMappedBy() !== null) {
                $lines[] = \sprintf('            if ($%s->get%s() === $this) {', $singular, \ucfirst($relation->getMappedBy()));
                $lines[] = \sprintf('                $%s->set%s(null);', $singular, \ucfirst($relation->getMappedBy()));
                $lines[] = '            }';
            }

            $lines[] = '        }';
            $lines[] = '';
            $lines[] = '        return $this;';
            $lines[] = '    }';
            $lines[] = '';
        } else {
            // Getter
            $lines[] = \sprintf('    public function get%s(): ?%s', $ucName, $targetShort);
            $lines[] = '    {';
            $lines[] = \sprintf('        return $this->%s;', $name);
            $lines[] = '    }';
            $lines[] = '';

            // Setter
            $lines[] = \sprintf('    public function set%s(?%s $%s): self', $ucName, $targetShort, $name);
            $lines[] = '    {';
            $lines[] = \sprintf('        $this->%s = $%s;', $name, $name);
            $lines[] = '';
            $lines[] = '        return $this;';
            $lines[] = '    }';
            $lines[] = '';
        }

        return \implode("\n", $lines);
    }

    private function resolveTargetEntityShort(string $targetEntity, string $namespace): string
    {
        if (\strpos($targetEntity, '\\') !== false) {
            $targetNamespace = \substr($targetEntity, 0, (int) \strrpos($targetEntity, '\\'));
            if ($targetNamespace === $namespace) {
                return $this->getShortClassName($targetEntity);
            }

            return $this->getShortClassName($targetEntity);
        }

        return $targetEntity;
    }

    private function getShortClassName(string $fqcn): string
    {
        $pos = \strrpos($fqcn, '\\');
        if ($pos === false) {
            return $fqcn;
        }

        return \substr($fqcn, $pos + 1);
    }

    /**
     * @param mixed  $value
     */
    private function formatDefaultValue($value, string $phpType): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        if (\is_string($value)) {
            return \sprintf("'%s'", \addslashes($value));
        }

        if (\is_array($value)) {
            return '[]';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    private function singularize(string $name): string
    {
        if (\substr($name, -3) === 'ies') {
            return \substr($name, 0, -3) . 'y';
        }

        if (\substr($name, -2) === 'es' && \substr($name, -3) !== 'ses') {
            return \substr($name, 0, -2);
        }

        if (\substr($name, -1) === 's' && \substr($name, -2) !== 'ss') {
            return \substr($name, 0, -1);
        }

        return $name;
    }
}
