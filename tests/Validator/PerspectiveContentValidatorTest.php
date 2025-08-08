<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Tests\Validator;

use Freema\PerspectiveApiBundle\Dto\PerspectiveAnalysisResult;
use Freema\PerspectiveApiBundle\Exception\PerspectiveApiException;
use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;
use Freema\PerspectiveApiBundle\Validator\PerspectiveContent;
use Freema\PerspectiveApiBundle\Validator\PerspectiveContentValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class PerspectiveContentValidatorTest extends TestCase
{
    private PerspectiveContentValidator $validator;
    private PerspectiveApiService $perspectiveApi;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->perspectiveApi = $this->createMock(PerspectiveApiService::class);
        $this->validator = new PerspectiveContentValidator($this->perspectiveApi);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->validator->initialize($this->context);
    }

    public function testValidateWithSafeContent(): void
    {
        $constraint = new PerspectiveContent();

        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.2],
            ['TOXICITY' => 0.5]
        );

        $this->perspectiveApi
            ->expects($this->once())
            ->method('analyzeText')
            ->with('Safe content', null, [])
            ->willReturn($result);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('Safe content', $constraint);
    }

    public function testValidateWithUnsafeContent(): void
    {
        $constraint = new PerspectiveContent();

        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.8, 'PROFANITY' => 0.7],
            ['TOXICITY' => 0.5, 'PROFANITY' => 0.5]
        );

        $this->perspectiveApi
            ->expects($this->once())
            ->method('analyzeText')
            ->with('Unsafe content', null, [])
            ->willReturn($result);

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ violations }}', 'TOXICITY, PROFANITY')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('Unsafe content', $constraint);
    }

    public function testValidateWithCustomThresholds(): void
    {
        $constraint = new PerspectiveContent([
            'thresholds' => ['TOXICITY' => 0.3],
        ]);

        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.4],
            ['TOXICITY' => 0.3]
        );

        $this->perspectiveApi
            ->expects($this->once())
            ->method('analyzeText')
            ->with('Content', null, ['TOXICITY' => 0.3])
            ->willReturn($result);

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ violations }}', 'TOXICITY')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('Content', $constraint);
    }

    public function testValidateWithCustomLanguage(): void
    {
        $constraint = new PerspectiveContent([
            'language' => 'fr',
        ]);

        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.2],
            ['TOXICITY' => 0.5]
        );

        $this->perspectiveApi
            ->expects($this->once())
            ->method('analyzeText')
            ->with('French content', 'fr', [])
            ->willReturn($result);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('French content', $constraint);
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new PerspectiveContent();

        $this->perspectiveApi
            ->expects($this->never())
            ->method('analyzeText');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    public function testValidateWithEmptyString(): void
    {
        $constraint = new PerspectiveContent();

        $this->perspectiveApi
            ->expects($this->never())
            ->method('analyzeText');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    public function testValidateWithCustomMessage(): void
    {
        $customMessage = 'This content is not appropriate for our platform.';
        $constraint = new PerspectiveContent([
            'message' => $customMessage,
        ]);

        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.8],
            ['TOXICITY' => 0.5]
        );

        $this->perspectiveApi
            ->expects($this->once())
            ->method('analyzeText')
            ->willReturn($result);

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($customMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('Inappropriate content', $constraint);
    }

    public function testValidateWithApiException(): void
    {
        $constraint = new PerspectiveContent();

        $this->perspectiveApi
            ->expects($this->once())
            ->method('analyzeText')
            ->willThrowException(new PerspectiveApiException('API Error'));

        // When API fails, validator should not add violation
        // to avoid blocking content submission due to API issues
        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('Content', $constraint);
    }
}
