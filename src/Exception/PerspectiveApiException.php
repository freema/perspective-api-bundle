<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Exception;

class PerspectiveApiException extends \Exception
{
    public static function apiKeyNotProvided(): self
    {
        return new self('API key not provided');
    }

    public static function invalidApiResponse(string $message): self
    {
        return new self(sprintf('Invalid API response: %s', $message));
    }

    public static function apiRequestFailed(string $message): self
    {
        return new self(sprintf('Google Perspective API request failed: %s', $message));
    }

    public static function invalidThresholdValue(string $attribute, float $value): self
    {
        return new self(sprintf('Invalid threshold value for attribute "%s": %f. Must be between 0.0 and 1.0.', $attribute, $value));
    }
}
