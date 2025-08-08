<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Contract;

interface ThresholdProviderInterface
{
    public function getThresholds(): array;

    public function getThreshold(string $attribute): ?float;
}
