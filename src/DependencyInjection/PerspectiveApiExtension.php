<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\DependencyInjection;

use Freema\PerspectiveApiBundle\Contract\ThresholdProviderInterface;
use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;
use Freema\PerspectiveApiBundle\Service\StaticThresholdProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PerspectiveApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('perspective_api.api_key', $config['api_key']);
        $container->setParameter('perspective_api.config', $config);
        $container->setParameter('perspective_api.thresholds', $config['thresholds'] ?? []);
        $container->setParameter('perspective_api.analyze_attributes', $config['analyze_attributes']);
        $container->setParameter('perspective_api.default_language', $config['default_language']);
        $container->setParameter('perspective_api.allow_runtime_override', $config['allow_runtime_override']);
        $container->setParameter('perspective_api.threshold_provider', $config['threshold_provider']);

        $serviceDefinition = $container->getDefinition(PerspectiveApiService::class);
        $serviceDefinition
            ->setArgument('$apiKey', $config['api_key'])
            ->setArgument('$config', $config);

        if (!empty($config['threshold_provider'])) {
            $serviceDefinition->addMethodCall('setThresholdProvider', [new Reference($config['threshold_provider'])]);
        } elseif (!empty($config['thresholds'])) {
            $container
                ->register('perspective_api.static_threshold_provider', StaticThresholdProvider::class)
                ->setArgument('$thresholds', $config['thresholds']);

            $serviceDefinition->addMethodCall('setThresholdProvider', [new Reference('perspective_api.static_threshold_provider')]);
        }

        $container->registerForAutoconfiguration(ThresholdProviderInterface::class)
            ->addTag('perspective_api.threshold_provider');

        // Create public aliases
        $container->setAlias('perspective_api', PerspectiveApiService::class)->setPublic(true);
        $container->setAlias('perspective_api.service', PerspectiveApiService::class)->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'perspective_api';
    }
}
