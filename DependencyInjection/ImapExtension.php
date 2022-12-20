<?php

declare(strict_types=1);

namespace SecIT\ImapBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class ImapExtension.
 *
 * @author Tomasz Gemza
 */
class ImapExtension extends Extension
{
    /**
     * {@inheritdoc}
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('secit.imap.connections', $config['connections']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
