<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Service;

use Freema\PerspectiveApiBundle\Contract\ThresholdProviderInterface;
use Freema\PerspectiveApiBundle\Dto\PerspectiveAnalysisResult;
use Freema\PerspectiveApiBundle\Exception\PerspectiveApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PerspectiveApiService
{
    private const API_BASE_URL = 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze';

    private const DEFAULT_ATTRIBUTES = [
        'TOXICITY',
        'SEVERE_TOXICITY',
        'IDENTITY_ATTACK',
        'INSULT',
        'PROFANITY',
        'THREAT',
    ];

    private ?ThresholdProviderInterface $thresholdProvider = null;
    private mixed $thresholdResolver = null;
    private array $staticThresholds = [];
    private array $analyzeAttributes = self::DEFAULT_ATTRIBUTES;
    private string $defaultLanguage = 'en';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        array $config = [],
    ) {
        if (empty($this->apiKey)) {
            throw PerspectiveApiException::apiKeyNotProvided();
        }

        $this->staticThresholds = $config['thresholds'] ?? [];
        $this->analyzeAttributes = $config['analyze_attributes'] ?? self::DEFAULT_ATTRIBUTES;
        $this->defaultLanguage = $config['default_language'] ?? 'en';
    }

    public function setThresholdProvider(ThresholdProviderInterface $provider): self
    {
        $this->thresholdProvider = $provider;

        return $this;
    }

    public function setThresholdResolver(callable $resolver): self
    {
        $this->thresholdResolver = $resolver;

        return $this;
    }

    public function analyzeText(
        string $text,
        ?string $language = null,
        ?array $customThresholds = null,
        array $context = [],
    ): PerspectiveAnalysisResult {
        $scores = $this->getScores($text, $language);
        $thresholds = $this->resolveThresholds($customThresholds, $context);

        return new PerspectiveAnalysisResult($scores, $thresholds);
    }

    public function getScores(string $text, ?string $language = null): array
    {
        $language = $language ?? $this->defaultLanguage;

        $requestData = [
            'comment' => [
                'text' => $text,
            ],
            'requestedAttributes' => $this->buildAttributesRequest(),
            'languages' => [$language],
        ];

        $response = $this->httpClient->request('POST', self::API_BASE_URL, [
            'query' => ['key' => $this->apiKey],
            'json' => $requestData,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        return $this->parseScoresFromResponse($response);
    }

    public function analyzeBatch(array $texts): array
    {
        $results = [];

        foreach ($texts as $text) {
            $results[] = $this->analyzeText($text);
        }

        return $results;
    }

    public function analyzeWithAttributes(
        string $text,
        array $attributes,
        ?string $language = null,
    ): PerspectiveAnalysisResult {
        $originalAttributes = $this->analyzeAttributes;
        $this->analyzeAttributes = $attributes;

        try {
            $result = $this->analyzeText($text, $language);
        } finally {
            $this->analyzeAttributes = $originalAttributes;
        }

        return $result;
    }

    private function buildAttributesRequest(): array
    {
        $attributes = [];

        foreach ($this->analyzeAttributes as $attribute) {
            $attributes[$attribute] = (object) [];
        }

        return $attributes;
    }

    private function parseScoresFromResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            throw PerspectiveApiException::apiRequestFailed(sprintf('HTTP %d: %s', $statusCode, $response->getContent(false)));
        }

        $data = $response->toArray();

        if (!isset($data['attributeScores'])) {
            throw PerspectiveApiException::invalidApiResponse('Missing attributeScores in response');
        }

        $scores = [];

        foreach ($data['attributeScores'] as $attribute => $scoreData) {
            if (isset($scoreData['summaryScore']['value'])) {
                $scores[$attribute] = (float) $scoreData['summaryScore']['value'];
            }
        }

        return $scores;
    }

    private function resolveThresholds(?array $customThresholds, array $context): array
    {
        if ($customThresholds !== null) {
            return $this->validateThresholds($customThresholds);
        }

        if ($this->thresholdResolver !== null && is_callable($this->thresholdResolver)) {
            $resolvedThresholds = [];

            foreach ($this->analyzeAttributes as $attribute) {
                $threshold = ($this->thresholdResolver)($attribute, $context);
                if ($threshold !== null) {
                    $resolvedThresholds[$attribute] = $threshold;
                }
            }

            return $this->validateThresholds($resolvedThresholds);
        }

        if ($this->thresholdProvider !== null) {
            return $this->validateThresholds($this->thresholdProvider->getThresholds());
        }

        return $this->validateThresholds($this->staticThresholds);
    }

    private function validateThresholds(array $thresholds): array
    {
        $validatedThresholds = [];

        foreach ($thresholds as $attribute => $threshold) {
            if (!is_numeric($threshold) || $threshold < 0.0 || $threshold > 1.0) {
                throw PerspectiveApiException::invalidThresholdValue($attribute, (float) $threshold);
            }

            $validatedThresholds[strtoupper($attribute)] = (float) $threshold;
        }

        return $validatedThresholds;
    }
}
