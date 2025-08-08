<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PerspectiveContent extends Constraint
{
    public string $message = 'This content violates our community guidelines.';
    public ?float $maxToxicity = null;
    public ?float $maxSevereToxicity = null;
    public ?float $maxInsult = null;
    public ?float $maxProfanity = null;
    public ?float $maxThreat = null;
    public ?float $maxIdentityAttack = null;
    public array $customThresholds = [];
    public array $thresholds = [];
    public ?string $language = null;

    public function __construct(
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);

        if (is_array($options)) {
            $this->maxToxicity = $options['maxToxicity'] ?? null;
            $this->maxSevereToxicity = $options['maxSevereToxicity'] ?? null;
            $this->maxInsult = $options['maxInsult'] ?? null;
            $this->maxProfanity = $options['maxProfanity'] ?? null;
            $this->maxThreat = $options['maxThreat'] ?? null;
            $this->maxIdentityAttack = $options['maxIdentityAttack'] ?? null;
            $this->customThresholds = $options['customThresholds'] ?? [];
            $this->thresholds = $options['thresholds'] ?? [];
            $this->language = $options['language'] ?? null;

            if (isset($options['message'])) {
                $this->message = $options['message'];
            }
        }
    }

    public function getThresholds(): array
    {
        if (!empty($this->thresholds)) {
            return $this->thresholds;
        }

        if (!empty($this->customThresholds)) {
            return $this->customThresholds;
        }

        $thresholds = [];

        if ($this->maxToxicity !== null) {
            $thresholds['TOXICITY'] = $this->maxToxicity;
        }

        if ($this->maxSevereToxicity !== null) {
            $thresholds['SEVERE_TOXICITY'] = $this->maxSevereToxicity;
        }

        if ($this->maxInsult !== null) {
            $thresholds['INSULT'] = $this->maxInsult;
        }

        if ($this->maxProfanity !== null) {
            $thresholds['PROFANITY'] = $this->maxProfanity;
        }

        if ($this->maxThreat !== null) {
            $thresholds['THREAT'] = $this->maxThreat;
        }

        if ($this->maxIdentityAttack !== null) {
            $thresholds['IDENTITY_ATTACK'] = $this->maxIdentityAttack;
        }

        return $thresholds;
    }
}
