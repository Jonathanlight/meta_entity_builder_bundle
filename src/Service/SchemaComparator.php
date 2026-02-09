<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

use Meta\EntityBuilderBundle\Model\ChangeSet;
use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\SchemaState;

final class SchemaComparator implements SchemaComparatorInterface
{
    /**
     * @param array<string, EntityDefinition> $definitions
     */
    public function compare(array $definitions, SchemaState $state): ChangeSet
    {
        $added = [];
        $modified = [];
        $removed = [];

        foreach ($definitions as $entityName => $definition) {
            if (!$state->hasEntity($entityName)) {
                $added[] = $entityName;
            } elseif ($state->getChecksum($entityName) !== $definition->getChecksum()) {
                $modified[] = $entityName;
            }
        }

        foreach ($state->getChecksums() as $entityName => $checksum) {
            if (!isset($definitions[$entityName])) {
                $removed[] = $entityName;
            }
        }

        return new ChangeSet($added, $modified, $removed);
    }
}
