<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Tests\Dto;

use Freema\PerspectiveApiBundle\Dto\PerspectiveAnalysisResult;
use PHPUnit\Framework\TestCase;

class PerspectiveAnalysisResultTest extends TestCase
{
    public function testIsSafeWithNoViolations(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.2, 'PROFANITY' => 0.1],
            ['TOXICITY' => 0.5, 'PROFANITY' => 0.3]
        );

        $this->assertTrue($result->isSafe());
        $this->assertEmpty($result->getViolations());
    }

    public function testIsSafeWithViolations(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.6, 'PROFANITY' => 0.4],
            ['TOXICITY' => 0.5, 'PROFANITY' => 0.3]
        );

        $this->assertFalse($result->isSafe());
        $this->assertContains('TOXICITY', $result->getViolations());
        $this->assertContains('PROFANITY', $result->getViolations());
    }

    public function testGetScore(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.25, 'PROFANITY' => 0.15],
            []
        );

        $this->assertEqualsWithDelta(0.25, $result->getScore('TOXICITY'), 0.01);
        $this->assertEqualsWithDelta(0.15, $result->getScore('PROFANITY'), 0.01);
        $this->assertNull($result->getScore('UNKNOWN'));
    }

    public function testGetScores(): void
    {
        $scores = ['TOXICITY' => 0.3, 'PROFANITY' => 0.2];
        $result = new PerspectiveAnalysisResult($scores, []);

        $this->assertEquals($scores, $result->getScores());
    }

    public function testGetThresholds(): void
    {
        $thresholds = ['TOXICITY' => 0.5, 'PROFANITY' => 0.3];
        $result = new PerspectiveAnalysisResult([], $thresholds);

        $this->assertEquals($thresholds, $result->getThresholds());
    }

    public function testHasAttribute(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.3, 'PROFANITY' => 0.2],
            []
        );

        $this->assertTrue($result->hasAttribute('TOXICITY'));
        $this->assertTrue($result->hasAttribute('PROFANITY'));
        $this->assertFalse($result->hasAttribute('UNKNOWN'));
    }

    public function testIsAttributeSafe(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.3, 'PROFANITY' => 0.6],
            ['TOXICITY' => 0.5, 'PROFANITY' => 0.5]
        );

        $this->assertTrue($result->isAttributeSafe('TOXICITY'));
        $this->assertFalse($result->isAttributeSafe('PROFANITY'));
        $this->assertTrue($result->isAttributeSafe('UNKNOWN')); // Unknown attributes are considered safe
    }

    public function testGetHighestScore(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.3, 'PROFANITY' => 0.6, 'THREAT' => 0.2],
            []
        );

        $this->assertEqualsWithDelta(0.6, $result->getHighestScore(), 0.01);
    }

    public function testGetHighestScoreWithEmptyScores(): void
    {
        $result = new PerspectiveAnalysisResult([], []);

        $this->assertEqualsWithDelta(0.0, $result->getHighestScore(), 0.01);
    }

    public function testIsSafeWithNoThresholds(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.9, 'PROFANITY' => 0.8],
            []
        );

        $this->assertTrue($result->isSafe()); // No thresholds means everything is safe
        $this->assertEmpty($result->getViolations());
    }

    public function testPartialThresholds(): void
    {
        $result = new PerspectiveAnalysisResult(
            ['TOXICITY' => 0.6, 'PROFANITY' => 0.4, 'THREAT' => 0.8],
            ['TOXICITY' => 0.5] // Only TOXICITY has a threshold
        );

        $this->assertFalse($result->isSafe());
        $violations = $result->getViolations();
        $this->assertContains('TOXICITY', $violations);
        $this->assertNotContains('PROFANITY', $violations); // No threshold for PROFANITY
        $this->assertNotContains('THREAT', $violations); // No threshold for THREAT
    }

    public function testToArray(): void
    {
        $scores = ['TOXICITY' => 0.3, 'PROFANITY' => 0.2];
        $thresholds = ['TOXICITY' => 0.5, 'PROFANITY' => 0.3];

        $result = new PerspectiveAnalysisResult($scores, $thresholds);

        $array = $result->toArray();

        $this->assertArrayHasKey('scores', $array);
        $this->assertArrayHasKey('thresholds', $array);
        $this->assertArrayHasKey('is_safe', $array);
        $this->assertArrayHasKey('violations', $array);

        $this->assertEquals($scores, $array['scores']);
        $this->assertEquals($thresholds, $array['thresholds']);
        $this->assertTrue($array['is_safe']);
        $this->assertEmpty($array['violations']);
    }
}
