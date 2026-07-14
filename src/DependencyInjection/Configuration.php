<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('helppc_supervisor');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_environment')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('servers')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->requiresAtLeastOneElement()
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('scheme')->defaultValue('http')->end()
                                ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('port')->defaultValue(9001)->end()
                                ->scalarNode('username')->defaultNull()->end()
                                ->scalarNode('password')->defaultNull()->end()
                                ->arrayNode('hidden_processes')
                                    ->info('Process names or group names that must never be listed or controlled through the bundle.')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->integerNode('log_tail_bytes')
                                    ->info('How many bytes of a process log tail to fetch and render.')
                                    ->min(1)
                                    ->defaultValue(16384)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
