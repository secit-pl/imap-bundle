<?php

declare(strict_types=1);

namespace SecIT\ImapBundle\DependencyInjection;

use SecIT\ImapBundle\Connection\Connection;
use SecIT\ImapBundle\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * @author Tomasz Gemza
 */
class ImapExtension extends Extension
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['connections'] as $connectionName => $options) {
            $serviceName = sprintf('secit.imap.connection.%s', $connectionName);

            $definition = new Definition(Connection::class);
            $definition->setPublic(false);
            $definition->addArgument($connectionName);
            $definition->addArgument($options['imap_path']);
            $definition->addArgument($options['username']);
            $definition->addArgument($options['password']);
            $definition->addArgument($options['server_encoding']);
            $definition->addArgument($options['attachments_dir']);
            $definition->addArgument($options['create_attachments_dir_if_not_exists']);
            $definition->addArgument($options['created_attachments_dir_permissions']);
            $definition->addArgument($options['enabled']);
            $definition->addTag('secit.imap.connection');

            $container->setDefinition($serviceName, $definition);
            $container->registerAliasForArgument($serviceName, ConnectionInterface::class, sprintf('%sConnection', $connectionName));
        }

        $container->setParameter('secit.imap.connections', $config['connections']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
