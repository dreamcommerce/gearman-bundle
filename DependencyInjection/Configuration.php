<?php

namespace DreamCommerce\GearmanBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dream_commerce_gearman');

        $rootNode
            ->children()
            ->scalarNode('php_path')->defaultValue('/usr/local/bin/php')->end()
            ->scalarNode('default_workers')->defaultValue(3)->end()
            ->scalarNode('name_prefix')->defaultValue('')->end()
            ->arrayNode('workers')->prototype('scalar')->end()->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
