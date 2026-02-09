<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Generator;

use Meta\EntityBuilderBundle\Model\EntityDefinition;

interface EntityGeneratorInterface
{
    public function generate(EntityDefinition $definition, string $namespace): string;
}
