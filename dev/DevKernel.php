<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Dev;

use Freema\PerspectiveApiBundle\PerspectiveApiBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class DevKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new DebugBundle(),
            new FrameworkBundle(),
            new PerspectiveApiBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            // Load framework first
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
                'router' => [
                    'utf8' => true,
                    'resource' => '%kernel.project_dir%/dev/config/routes.yaml',
                ],
                'http_method_override' => false,
                'session' => [
                    'enabled' => true,
                    'storage_factory_id' => 'session.storage.factory.native',
                ],
                'http_client' => [
                    'default_options' => [
                        'timeout' => 30,
                        'max_redirects' => 3,
                        // Proxy support - can be overridden via environment
                        'proxy' => $_ENV['HTTP_PROXY'] ?? $_ENV['HTTPS_PROXY'] ?? null,
                        'no_proxy' => $_ENV['NO_PROXY'] ?? null,
                        'verify_peer' => !(($_ENV['HTTP_CLIENT_VERIFY_PEER'] ?? 'true') === 'false'),
                        'verify_host' => !(($_ENV['HTTP_CLIENT_VERIFY_HOST'] ?? 'true') === 'false'),
                        'headers' => [
                            'User-Agent' => 'Perspective-API-Bundle/1.0 (Symfony HttpClient)',
                        ],
                    ],
                ],
            ]);

            // Load Perspective API configuration immediately
            $container->loadFromExtension('perspective_api', [
                'api_key' => $_ENV['PERSPECTIVE_API_KEY'] ?? 'test_api_key_12345',
                'thresholds' => [
                    'TOXICITY' => (float) ($_ENV['THRESHOLD_TOXICITY'] ?? '0.5'),
                    'SEVERE_TOXICITY' => (float) ($_ENV['THRESHOLD_SEVERE_TOXICITY'] ?? '0.3'),
                    'IDENTITY_ATTACK' => (float) ($_ENV['THRESHOLD_IDENTITY_ATTACK'] ?? '0.5'),
                    'INSULT' => (float) ($_ENV['THRESHOLD_INSULT'] ?? '0.5'),
                    'PROFANITY' => (float) ($_ENV['THRESHOLD_PROFANITY'] ?? '0.5'),
                    'THREAT' => (float) ($_ENV['THRESHOLD_THREAT'] ?? '0.5'),
                ],
                'analyze_attributes' => array_filter(explode(',', $_ENV['ANALYZE_ATTRIBUTES'] ?? 'TOXICITY,SEVERE_TOXICITY,IDENTITY_ATTACK,INSULT,PROFANITY,THREAT')),
                'default_language' => $_ENV['DEFAULT_LANGUAGE'] ?? 'en',
                'allow_runtime_override' => filter_var($_ENV['ALLOW_RUNTIME_OVERRIDE'] ?? 'true', \FILTER_VALIDATE_BOOLEAN),
            ]);
        });

        // Load dev services after extensions
        $loader->load(__DIR__.'/config/services.yaml');
    }

    public function getCacheDir(): string
    {
        if (method_exists($this, 'getProjectDir')) {
            return $this->getProjectDir().'/dev/cache/'.$this->getEnvironment();
        }

        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if (method_exists($this, 'getProjectDir')) {
            return $this->getProjectDir().'/dev/cache/'.$this->getEnvironment();
        }

        return parent::getLogDir();
    }
}
