<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Reporter\GitlabReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitlabReporter::class)]
final class GitlabReporterTest extends TestCase {

  private GitlabReporter $reporter;

  public function testReportReturnsFalseWithNoViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 5, threshold: 15, line: 1),
    ];

    ob_start();
    $has_violations = $this->reporter->report($results);
    $output = ob_get_clean();

    self::assertFalse($has_violations);
    $decoded = json_decode((string) $output, true);
    self::assertIsArray($decoded);
    self::assertCount(0, $decoded);
  }

  public function testReportReturnsTrueWithViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'processOrder', score: 20, threshold: 15, line: 10),
    ];

    ob_start();
    $has_violations = $this->reporter->report($results);
    $output = ob_get_clean();

    self::assertTrue($has_violations);
    $decoded = json_decode((string) $output, true);
    self::assertIsArray($decoded);
    self::assertCount(1, $decoded);

    $issue = $decoded[0];
    self::assertSame('issue', $issue['type']);
    self::assertSame('CognitiveComplexity', $issue['check_name']);
    self::assertStringContainsString('processOrder', $issue['description']);
    self::assertStringContainsString('20', $issue['description']);
    self::assertStringContainsString('15', $issue['description']);
    self::assertSame(['Complexity'], $issue['categories']);
    self::assertSame('major', $issue['severity']);
    self::assertSame(10, $issue['location']['lines']['begin']);
  }

  public function testReportMarksCriticalSeverityWhenScoreDoubleThreshold(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bloated', score: 31, threshold: 15, line: 5),
    ];

    ob_start();
    $this->reporter->report($results);
    $output = ob_get_clean();

    $decoded = json_decode((string) $output, true);
    self::assertSame('critical', $decoded[0]['severity']);
  }

  public function testReportOnlyIncludesViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'clean', score: 3, threshold: 15, line: 1),
      new AnalysisResult('/src/Foo.php', 'dirty', score: 20, threshold: 15, line: 30),
    ];

    ob_start();
    $has_violations = $this->reporter->report($results);
    $output = ob_get_clean();

    self::assertTrue($has_violations);
    $decoded = json_decode((string) $output, true);
    self::assertCount(1, $decoded);
    self::assertStringContainsString('dirty', $decoded[0]['description']);
  }

  public function testFingerprintIsDeterministic(): void {
    $result = new AnalysisResult('/src/Foo.php', 'myMethod', score: 20, threshold: 15, line: 1);

    ob_start();
    $this->reporter->report([$result]);
    $out1 = ob_get_clean();

    ob_start();
    $this->reporter->report([$result]);
    $out2 = ob_get_clean();

    $d1 = json_decode((string) $out1, true);
    $d2 = json_decode((string) $out2, true);
    self::assertSame($d1[0]['fingerprint'], $d2[0]['fingerprint']);
    self::assertSame(md5('/src/Foo.php::myMethod'), $d1[0]['fingerprint']);
  }

  protected function setUp(): void {
    $this->reporter = new GitlabReporter();
  }

}
