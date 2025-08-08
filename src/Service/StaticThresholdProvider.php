<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Service;

use Freema\PerspectiveApiBundle\Contract\ThresholdProviderInterface;

class StaticThresholdProvider implements ThresholdProviderInterface
{
    public function __construct(private readonly array $thresholds = [])
    {
    }

    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    public function getThreshold(string $attribute): ?float
    {
        return $this->thresholds[strtoupper($attribute)] ?? null;
    }
}
