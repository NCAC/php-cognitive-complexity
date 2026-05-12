<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Analyzer;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnalysisResult::class)]
final class AnalysisResultTest extends TestCase {

  public function testConstructorExposesProperties(): void {
    $result = new AnalysisResult(
      file: '/src/Foo.php',
      function: 'myMethod',
      score: 10,
      threshold: 15,
      line: 42,
    );

    self::assertSame('/src/Foo.php', $result->file);
    self::assertSame('myMethod', $result->function);
    self::assertSame(10, $result->score);
    self::assertSame(15, $result->threshold);
    self::assertSame(42, $result->line);
  }

  public function testHasViolationReturnsFalseWhenScoreBelowThreshold(): void {
    $result = new AnalysisResult('f.php', 'fn', score: 5, threshold: 15, line: 1);
    self::assertFalse($result->hasViolation());
  }

  public function testHasViolationReturnsFalseWhenScoreEqualsThreshold(): void {
    $result = new AnalysisResult('f.php', 'fn', score: 15, threshold: 15, line: 1);
    self::assertFalse($result->hasViolation());
  }

  public function testHasViolationReturnsTrueWhenScoreExceedsThreshold(): void {
    $result = new AnalysisResult('f.php', 'fn', score: 16, threshold: 15, line: 1);
    self::assertTrue($result->hasViolation());
  }

}
