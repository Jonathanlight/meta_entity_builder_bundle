<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\Service;

interface EntityBuilderServiceInterface
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function build(array $options = []): array;
}
