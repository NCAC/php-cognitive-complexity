<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Analyzer;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Analyzer\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnalysisResult::class)]
#[CoversClass(Severity::class)]
final class AnalysisResultTest extends TestCase {

  /**
   * @return list<array{int, Severity}>
   */
  public static function severityProvider(): array {
    // threshold = 15
    return [
      [15, Severity::Ok],        // at threshold → ok
      [14, Severity::Ok],        // below threshold → ok
      [16, Severity::Minor],     // just above → minor (≤ 2×15=30)
      [30, Severity::Minor],     // exactly 2×threshold → minor
      [31, Severity::Moderate],  // just above 2×threshold → moderate
      [45, Severity::Moderate],  // exactly 3×threshold → moderate
      [46, Severity::High],      // just above 3×threshold → high
      [50, Severity::High],      // still high at 50
      [51, Severity::Critical],  // > 50 → critical
      [99, Severity::Critical],  // far above → critical
    ];
  }

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
  }// -------------------------------------------------------------------------
  // severity()
  // -------------------------------------------------------------------------


  #[DataProvider('severityProvider')]
  public function testSeverity(int $score, Severity $expected): void {
    $result = new AnalysisResult('f.php', 'fn', score: $score, threshold: 15, line: 1);
    self::assertSame($expected, $result->severity());
  }

  public function testSeverityLabelReturnsString(): void {
    self::assertSame('ok', Severity::Ok->label());
    self::assertSame('minor', Severity::Minor->label());
    self::assertSame('moderate', Severity::Moderate->label());
    self::assertSame('high', Severity::High->label());
    self::assertSame('critical', Severity::Critical->label());
  }

}
