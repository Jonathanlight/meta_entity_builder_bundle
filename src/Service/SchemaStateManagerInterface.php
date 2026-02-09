<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

use Meta\EntityBuilderBundle\Model\SchemaState;

interface SchemaStateManagerInterface
{
    public function load(): SchemaState;

    public function save(SchemaState $state): void;

    public function hasState(): bool;
}
