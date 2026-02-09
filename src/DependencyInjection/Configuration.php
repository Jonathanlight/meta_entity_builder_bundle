<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('meta_entity_builder');

        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition);

        /** @var \Symfony\Component\Config\Definition\Builder\NodeBuilder $children */
        $children = $rootNode->children();

        $children
            ->scalarNode('schema_path')
                ->defaultValue('%kernel.project_dir%/config/entities.yaml')
            ->end()
            ->scalarNode('entity_namespace')
                ->defaultValue('App\\Entity')
            ->end()
            ->scalarNode('entity_directory')
                ->defaultValue('%kernel.project_dir%/src/Entity')
            ->end()
            ->booleanNode('backup_enabled')
                ->defaultTrue()
            ->end()
            ->scalarNode('backup_directory')
                ->defaultValue('%kernel.project_dir%/var/backups/entities')
            ->end()
            ->booleanNode('generate_repository')
                ->defaultTrue()
            ->end()
            ->scalarNode('repository_namespace')
                ->defaultValue('App\\Repository')
            ->end()
            ->booleanNode('strict_mode')
                ->defaultTrue()
            ->end()
        ;

        return $treeBuilder;
    }
}
