<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Parser;

use Meta\EntityBuilderBundle\Model\EntityDefinition;

interface SchemaParserInterface
{
    /**
     * @return array<string, EntityDefinition>
     */
    public function parse(string $filePath): array;
}
