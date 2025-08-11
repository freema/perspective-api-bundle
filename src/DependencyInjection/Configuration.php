<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('perspective_api');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Google Perspective API key')
                ->end()
                ->scalarNode('default_language')
                    ->defaultValue('en')
                    ->info('Default language for analysis')
                ->end()
                ->arrayNode('thresholds')
                    ->info('Static threshold values for each attribute')
                    ->scalarPrototype()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return !is_numeric($v) || $v < 0.0 || $v > 1.0;
                            })
                            ->thenInvalid('Threshold must be a number between 0.0 and 1.0')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('threshold_provider')
                    ->defaultNull()
                    ->info('Service ID of threshold provider')
                ->end()
                ->booleanNode('allow_runtime_override')
                    ->defaultTrue()
                    ->info('Allow runtime threshold override')
                ->end()
                ->arrayNode('analyze_attributes')
                    ->info('List of attributes to analyze')
                    ->scalarPrototype()->end()
                    ->defaultValue(['TOXICITY', 'SEVERE_TOXICITY', 'IDENTITY_ATTACK', 'INSULT', 'PROFANITY', 'THREAT'])
                ->end()
                ->arrayNode('http_client_options')
                    ->info('Additional options for HTTP client (proxy, timeout, etc.)')
                    ->variablePrototype()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
