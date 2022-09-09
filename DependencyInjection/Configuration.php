<?php

namespace SecIT\ImapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * @author Tomasz Gemza
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('imap');

        if (\method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('imap');
        }

        $rootNode
	        ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
	                ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('mailbox')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('username')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('password')
                                ->isRequired()
                            ->end()
                            ->scalarNode('attachments_dir')
                                ->cannotBeEmpty()
                                ->defaultValue(null)
                            ->end()
                            ->booleanNode('create_attachments_dir_if_not_exists')
                                ->defaultTrue()
                            ->end()
                            ->integerNode('created_attachments_dir_permissions')
                                ->defaultValue(770)
                            ->end()
                            ->scalarNode('server_encoding')
                                ->cannotBeEmpty()
                                ->defaultValue('UTF-8')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
