<?php

declare(strict_types=1);

namespace Meta\EntityBuilderBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class MetaEntityBuilderExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('meta_entity_builder.schema_path', $config['schema_path']);
        $container->setParameter('meta_entity_builder.entity_namespace', $config['entity_namespace']);
        $container->setParameter('meta_entity_builder.entity_directory', $config['entity_directory']);
        $container->setParameter('meta_entity_builder.backup_enabled', $config['backup_enabled']);
        $container->setParameter('meta_entity_builder.backup_directory', $config['backup_directory']);
        $container->setParameter('meta_entity_builder.generate_repository', $config['generate_repository']);
        $container->setParameter('meta_entity_builder.repository_namespace', $config['repository_namespace']);
        $container->setParameter('meta_entity_builder.strict_mode', $config['strict_mode']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
