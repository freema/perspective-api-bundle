<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Tests\Service;

use Freema\PerspectiveApiBundle\Contract\ThresholdProviderInterface;
use Freema\PerspectiveApiBundle\Exception\PerspectiveApiException;
use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PerspectiveApiServiceTest extends TestCase
{
    private PerspectiveApiService $service;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->service = new PerspectiveApiService(
            $this->httpClient,
            'test-api-key',
            [
                'thresholds' => [
                    'TOXICITY' => 0.5,
                    'PROFANITY' => 0.3,
                ],
                'analyze_attributes' => ['TOXICITY', 'PROFANITY'],
                'default_language' => 'en',
            ]
        );
    }

    public function testAnalyzeTextWithSuccessfulResponse(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'attributeScores' => [
                'TOXICITY' => [
                    'summaryScore' => ['value' => 0.2],
                ],
                'PROFANITY' => [
                    'summaryScore' => ['value' => 0.1],
                ],
            ],
        ]));

        $this->httpClient->setResponseFactory($mockResponse);

        $result = $this->service->analyzeText('Hello world');

        $this->assertTrue($result->isSafe());
        $this->assertEqualsWithDelta(0.2, $result->getScore('TOXICITY'), 0.01);
        $this->assertEqualsWithDelta(0.1, $result->getScore('PROFANITY'), 0.01);
        $this->assertEmpty($result->getViolations());
    }

    public function testAnalyzeTextWithViolations(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'attributeScores' => [
                'TOXICITY' => [
                    'summaryScore' => ['value' => 0.7],
                ],
                'PROFANITY' => [
                    'summaryScore' => ['value' => 0.5],
                ],
            ],
        ]));

        $this->httpClient->setResponseFactory($mockResponse);

        $result = $this->service->analyzeText('Bad text');

        $this->assertFalse($result->isSafe());
        $this->assertContains('TOXICITY', $result->getViolations());
        $this->assertContains('PROFANITY', $result->getViolations());
    }

    public function testAnalyzeTextWithCustomThresholds(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'attributeScores' => [
                'TOXICITY' => [
                    'summaryScore' => ['value' => 0.4],
                ],
                'PROFANITY' => [
                    'summaryScore' => ['value' => 0.2],
                ],
            ],
        ]));

        $this->httpClient->setResponseFactory($mockResponse);

        $result = $this->service->analyzeText(
            'Text',
            null,
            ['TOXICITY' => 0.3, 'PROFANITY' => 0.1]
        );

        $this->assertFalse($result->isSafe());
        $this->assertContains('TOXICITY', $result->getViolations());
        $this->assertContains('PROFANITY', $result->getViolations());
    }

    public function testAnalyzeBatch(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'attributeScores' => [
                    'TOXICITY' => ['summaryScore' => ['value' => 0.1]],
                    'PROFANITY' => ['summaryScore' => ['value' => 0.1]],
                ],
            ])),
            new MockResponse(json_encode([
                'attributeScores' => [
                    'TOXICITY' => ['summaryScore' => ['value' => 0.8]],
                    'PROFANITY' => ['summaryScore' => ['value' => 0.7]],
                ],
            ])),
        ];

        $this->httpClient->setResponseFactory($responses);

        $results = $this->service->analyzeBatch(['Good text', 'Bad text']);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isSafe());
        $this->assertFalse($results[1]->isSafe());
    }

    public function testThresholdProvider(): void
    {
        $provider = $this->createMock(ThresholdProviderInterface::class);
        $provider->method('getThresholds')->willReturn([
            'TOXICITY' => 0.2,
            'PROFANITY' => 0.1,
        ]);

        $this->service->setThresholdProvider($provider);

        $mockResponse = new MockResponse(json_encode([
            'attributeScores' => [
                'TOXICITY' => ['summaryScore' => ['value' => 0.3]],
                'PROFANITY' => ['summaryScore' => ['value' => 0.2]],
            ],
        ]));

        $this->httpClient->setResponseFactory($mockResponse);

        $result = $this->service->analyzeText('Text');

        $this->assertFalse($result->isSafe());
        $this->assertContains('TOXICITY', $result->getViolations());
        $this->assertContains('PROFANITY', $result->getViolations());
    }

    public function testThresholdResolver(): void
    {
        $this->service->setThresholdResolver(function (string $attribute, array $context) {
            if ($attribute === 'TOXICITY' && isset($context['strict'])) {
                return 0.1;
            }

            return null;
        });

        $mockResponse = new MockResponse(json_encode([
            'attributeScores' => [
                'TOXICITY' => ['summaryScore' => ['value' => 0.2]],
                'PROFANITY' => ['summaryScore' => ['value' => 0.2]],
            ],
        ]));

        $this->httpClient->setResponseFactory($mockResponse);

        $result = $this->service->analyzeText('Text', null, null, ['strict' => true]);

        $this->assertFalse($result->isSafe());
        $this->assertContains('TOXICITY', $result->getViolations());
        $this->assertNotContains('PROFANITY', $result->getViolations());
    }

    public function testApiErrorHandling(): void
    {
        $mockResponse = new MockResponse('Error', ['http_code' => 400]);
        $this->httpClient->setResponseFactory($mockResponse);

        $this->expectException(PerspectiveApiException::class);
        $this->expectExceptionMessage('API request failed');

        $this->service->analyzeText('Text');
    }

    public function testInvalidResponseHandling(): void
    {
        $mockResponse = new MockResponse(json_encode(['invalid' => 'response']));
        $this->httpClient->setResponseFactory($mockResponse);

        $this->expectException(PerspectiveApiException::class);
        $this->expectExceptionMessage('Invalid API response');

        $this->service->analyzeText('Text');
    }

    public function testEmptyApiKeyThrowsException(): void
    {
        $this->expectException(PerspectiveApiException::class);
        $this->expectExceptionMessage('API key not provided');

        new PerspectiveApiService($this->httpClient, '');
    }

    public function testAnalyzeWithAttributes(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'attributeScores' => [
                'TOXICITY' => ['summaryScore' => ['value' => 0.3]],
                'THREAT' => ['summaryScore' => ['value' => 0.1]],
            ],
        ]));

        $this->httpClient->setResponseFactory($mockResponse);

        $result = $this->service->analyzeWithAttributes(
            'Text',
            ['TOXICITY', 'THREAT'],
            'en'
        );

        $scores = $result->getScores();
        $this->assertArrayHasKey('TOXICITY', $scores);
        $this->assertArrayHasKey('THREAT', $scores);
        $this->assertEqualsWithDelta(0.3, $scores['TOXICITY'], 0.01);
        $this->assertEqualsWithDelta(0.1, $scores['THREAT'], 0.01);
    }
}
