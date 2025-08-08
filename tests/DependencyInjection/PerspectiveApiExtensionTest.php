<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Tests\DependencyInjection;

use Freema\PerspectiveApiBundle\DependencyInjection\PerspectiveApiExtension;
use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;
use Freema\PerspectiveApiBundle\Validator\PerspectiveContentValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PerspectiveApiExtensionTest extends TestCase
{
    private PerspectiveApiExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new PerspectiveApiExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadWithMinimalConfiguration(): void
    {
        $config = [
            'api_key' => 'test-key',
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasDefinition(PerspectiveApiService::class));
        $this->assertEquals('test-key', $this->container->getParameter('perspective_api.api_key'));
    }

    public function testLoadWithFullConfiguration(): void
    {
        $config = [
            'api_key' => 'test-key',
            'thresholds' => [
                'TOXICITY' => 0.5,
                'PROFANITY' => 0.3,
            ],
            'analyze_attributes' => ['TOXICITY', 'PROFANITY', 'THREAT'],
            'default_language' => 'fr',
            'allow_runtime_override' => false,
            'threshold_provider' => 'my.custom.provider',
        ];

        $this->extension->load([$config], $this->container);

        $this->assertEquals('test-key', $this->container->getParameter('perspective_api.api_key'));
        $this->assertEquals(
            ['TOXICITY' => 0.5, 'PROFANITY' => 0.3],
            $this->container->getParameter('perspective_api.thresholds')
        );
        $this->assertEquals(
            ['TOXICITY', 'PROFANITY', 'THREAT'],
            $this->container->getParameter('perspective_api.analyze_attributes')
        );
        $this->assertEquals('fr', $this->container->getParameter('perspective_api.default_language'));
        $this->assertFalse($this->container->getParameter('perspective_api.allow_runtime_override'));
        $this->assertEquals('my.custom.provider', $this->container->getParameter('perspective_api.threshold_provider'));
    }

    public function testServicesAreRegistered(): void
    {
        $config = [
            'api_key' => 'test-key',
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasDefinition(PerspectiveApiService::class));
        $this->assertTrue($this->container->hasDefinition(PerspectiveContentValidator::class));
    }

    public function testServiceAliasesAreCreated(): void
    {
        $config = [
            'api_key' => 'test-key',
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasAlias('perspective_api'));
        $this->assertTrue($this->container->hasAlias('perspective_api.service'));
    }

    public function testDefaultValues(): void
    {
        $config = [
            'api_key' => 'test-key',
        ];

        $this->extension->load([$config], $this->container);

        $this->assertEquals(
            [
                'TOXICITY',
                'SEVERE_TOXICITY',
                'IDENTITY_ATTACK',
                'INSULT',
                'PROFANITY',
                'THREAT',
            ],
            $this->container->getParameter('perspective_api.analyze_attributes')
        );
        $this->assertEquals('en', $this->container->getParameter('perspective_api.default_language'));
        $this->assertTrue($this->container->getParameter('perspective_api.allow_runtime_override'));
        $this->assertNull($this->container->getParameter('perspective_api.threshold_provider'));
    }

    public function testThresholdProviderReference(): void
    {
        $config = [
            'api_key' => 'test-key',
            'threshold_provider' => 'my.custom.threshold.provider',
        ];

        $this->extension->load([$config], $this->container);

        $this->assertEquals('my.custom.threshold.provider', $this->container->getParameter('perspective_api.threshold_provider'));
    }

    public function testValidatorIsTagged(): void
    {
        $config = [
            'api_key' => 'test-key',
        ];

        $this->extension->load([$config], $this->container);

        $definition = $this->container->getDefinition(PerspectiveContentValidator::class);
        $this->assertTrue($definition->hasTag('validator.constraint_validator'));
    }
}
