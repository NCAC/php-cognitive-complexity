<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Reporter\JsonReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonReporter::class)]
final class JsonReporterTest extends TestCase {

  private JsonReporter $reporter;

  public function testReportReturnsFalseWithNoViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 5, threshold: 15, line: 1),
    ];

    ob_start();
    $has_violations = $this->reporter->report($results);
    $json = ob_get_clean();

    self::assertFalse($has_violations);

    $data = json_decode((string) $json, true);
    self::assertSame(1, $data['summary']['analyzed']);
    self::assertSame(0, $data['summary']['violations']);
    self::assertSame([], $data['violations']);
  }

  public function testReportReturnsTrueWithViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
      new AnalysisResult('/src/Baz.php', 'qux', score: 5, threshold: 15, line: 3),
    ];

    ob_start();
    $has_violations = $this->reporter->report($results);
    $json = ob_get_clean();

    self::assertTrue($has_violations);

    $data = json_decode((string) $json, true);
    self::assertSame(2, $data['summary']['analyzed']);
    self::assertSame(1, $data['summary']['violations']);
    self::assertCount(1, $data['violations']);
    self::assertSame('bar', $data['violations'][0]['function']);
    self::assertSame(20, $data['violations'][0]['score']);
  }

  public function testReportOutputIsValidJson(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
    ];

    ob_start();
    $this->reporter->report($results);
    $json = ob_get_clean();

    self::assertJson((string) $json);
  }

  public function testReportWithEmptyResultsReturnsNoViolations(): void {
    ob_start();
    $has_violations = $this->reporter->report([]);
    ob_get_clean();

    self::assertFalse($has_violations);
  }

  protected function setUp(): void {
    $this->reporter = new JsonReporter();
  }

}
