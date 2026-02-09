<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

use Meta\EntityBuilderBundle\Model\ChangeSet;
use Meta\EntityBuilderBundle\Model\EntityDefinition;
use Meta\EntityBuilderBundle\Model\SchemaState;

interface SchemaComparatorInterface
{
    /**
     * @param array<string, EntityDefinition> $definitions
     */
    public function compare(array $definitions, SchemaState $state): ChangeSet;
}
