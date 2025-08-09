<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Service;

use Freema\PerspectiveApiBundle\Contract\ThresholdProviderInterface;
use Freema\PerspectiveApiBundle\Dto\PerspectiveAnalysisResult;
use Freema\PerspectiveApiBundle\Exception\PerspectiveApiException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PerspectiveApiService implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    private const API_BASE_URL = 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze';

    private const DEFAULT_ATTRIBUTES = [
        'TOXICITY',
        'SEVERE_TOXICITY',
        'IDENTITY_ATTACK',
        'INSULT',
        'PROFANITY',
        'THREAT',
    ];

    private const DEFAULT_FALLBACK_THRESHOLDS = [
        'TOXICITY' => 0.7,
        'SEVERE_TOXICITY' => 0.5,
        'IDENTITY_ATTACK' => 0.6,
        'INSULT' => 0.6,
        'PROFANITY' => 0.8,
        'THREAT' => 0.5,
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
        $this->logger = new NullLogger();
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
        $this->logger->debug('Starting Perspective API text analysis', [
            'text_length' => strlen($text),
            'language' => $language ?? $this->defaultLanguage,
            'custom_thresholds_provided' => $customThresholds !== null,
            'context_keys' => array_keys($context),
        ]);

        $scores = $this->getScores($text, $language);
        $thresholds = $this->resolveThresholds($customThresholds, $context);
        $result = new PerspectiveAnalysisResult($scores, $thresholds);

        $this->logger->info('Perspective API analysis completed', [
            'scores' => $scores,
            'thresholds' => $thresholds,
            'violations' => array_keys($result->getViolations()),
            'is_acceptable' => $result->isAllowed(),
        ]);

        return $result;
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
            $errorContent = $response->getContent(false);
            $this->logger->error('Perspective API request failed', [
                'status_code' => $statusCode,
                'response' => $errorContent,
            ]);
            throw PerspectiveApiException::apiRequestFailed(sprintf('HTTP %d: %s', $statusCode, $errorContent));
        }

        $data = $response->toArray();

        if (!isset($data['attributeScores'])) {
            $this->logger->error('Invalid Perspective API response', [
                'response_data' => $data,
            ]);
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

        $thresholds = $this->validateThresholds($this->staticThresholds);

        if (empty($thresholds)) {
            $thresholds = array_intersect_key(
                self::DEFAULT_FALLBACK_THRESHOLDS,
                array_flip($this->analyzeAttributes)
            );

            $this->logger->info('Using fallback thresholds for Perspective API analysis', [
                'attributes' => array_keys($thresholds),
                'thresholds' => $thresholds,
            ]);
        }

        return $thresholds;
    }

    private function validateThresholds(array $thresholds): array
    {
        $validatedThresholds = [];

        foreach ($thresholds as $attribute => $threshold) {
            if (!is_numeric($threshold) || $threshold < 0.0 || $threshold > 1.0) {
                $this->logger->error('Invalid threshold value detected', [
                    'attribute' => $attribute,
                    'threshold' => $threshold,
                ]);
                throw PerspectiveApiException::invalidThresholdValue($attribute, (float) $threshold);
            }

            $validatedThresholds[strtoupper($attribute)] = (float) $threshold;
        }

        return $validatedThresholds;
    }
}
