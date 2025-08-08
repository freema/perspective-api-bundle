<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Validator;

use Freema\PerspectiveApiBundle\Exception\PerspectiveApiException;
use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PerspectiveContentValidator extends ConstraintValidator
{
    public function __construct(private readonly PerspectiveApiService $perspectiveApiService)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PerspectiveContent) {
            throw new UnexpectedTypeException($constraint, PerspectiveContent::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        try {
            $thresholds = $constraint->getThresholds();

            $result = $this->perspectiveApiService->analyzeText(
                $value,
                $constraint->language,
                $thresholds
            );

            if (!$result->isSafe()) {
                $violations = implode(', ', $result->getViolations());
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ violations }}', $violations)
                    ->addViolation();
            }
        } catch (PerspectiveApiException $e) {
            // Log error but don't fail validation to avoid blocking forms
            // when the API is unavailable
            return;
        }
    }
}
