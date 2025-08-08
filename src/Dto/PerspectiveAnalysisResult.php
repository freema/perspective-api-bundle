<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Dto;

class PerspectiveAnalysisResult
{
    private array $scores;
    private array $thresholds;
    private array $exceededThresholds;

    public function __construct(array $scores, array $thresholds)
    {
        $this->scores = $scores;
        $this->thresholds = $thresholds;
        $this->exceededThresholds = $this->calculateExceededThresholds();
    }

    public function isAllowed(): bool
    {
        return empty($this->exceededThresholds);
    }

    public function getScores(): array
    {
        return $this->scores;
    }

    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    public function getExceededThresholds(): array
    {
        return $this->exceededThresholds;
    }

    public function getViolations(): array
    {
        return array_keys($this->exceededThresholds);
    }

    public function getHighestScore(): float
    {
        return empty($this->scores) ? 0.0 : max($this->scores);
    }

    public function getLowestScore(): float
    {
        return empty($this->scores) ? 0.0 : min($this->scores);
    }

    public function getScoreFor(string $attribute): ?float
    {
        return $this->scores[strtoupper($attribute)] ?? null;
    }

    public function getScore(string $attribute): ?float
    {
        return $this->getScoreFor($attribute);
    }

    public function hasAttribute(string $attribute): bool
    {
        return isset($this->scores[strtoupper($attribute)]);
    }

    public function isAttributeSafe(string $attribute): bool
    {
        $attribute = strtoupper($attribute);

        if (!isset($this->scores[$attribute])) {
            return true; // Unknown attributes are considered safe
        }

        if (!isset($this->thresholds[$attribute])) {
            return true; // No threshold means no restriction
        }

        return $this->scores[$attribute] <= $this->thresholds[$attribute];
    }

    public function toArray(): array
    {
        return [
            'scores' => $this->scores,
            'thresholds' => $this->thresholds,
            'is_safe' => $this->isAllowed(),
            'violations' => $this->getViolations(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), \JSON_THROW_ON_ERROR);
    }

    public function isToxic(?float $customThreshold = null): bool
    {
        $threshold = $customThreshold ?? $this->thresholds['TOXICITY'] ?? 0.7;

        return ($this->scores['TOXICITY'] ?? 0.0) > $threshold;
    }

    public function isSafe(): bool
    {
        return $this->isAllowed();
    }

    public function getSeverityLevel(): string
    {
        $highestScore = $this->getHighestScore();

        if ($highestScore >= 0.8) {
            return 'danger';
        }

        if ($highestScore >= 0.5) {
            return 'warning';
        }

        return 'safe';
    }

    private function calculateExceededThresholds(): array
    {
        $exceeded = [];

        foreach ($this->scores as $attribute => $score) {
            if (isset($this->thresholds[$attribute]) && $score > $this->thresholds[$attribute]) {
                $exceeded[$attribute] = [
                    'score' => $score,
                    'threshold' => $this->thresholds[$attribute],
                ];
            }
        }

        return $exceeded;
    }
}
