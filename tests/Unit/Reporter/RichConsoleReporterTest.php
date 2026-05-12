<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Reporter\RichConsoleReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(RichConsoleReporter::class)]
final class RichConsoleReporterTest extends TestCase {

  private RichConsoleReporter $reporter;

  private BufferedOutput $output;

  public function testNoViolationsShowsSuccessSummary(): void {
    $results = [
      new AnalysisResult('f.php', 'foo', score: 5, threshold: 15, line: 1),
    ];
    $this->reporter->report($this->output, $results, show_all: false);
    $out = $this->output->fetch();
    self::assertStringContainsString('No violations', $out);
  }

  public function testViolationShowsInOutput(): void {
    $results = [
      new AnalysisResult('f.php', 'heavyMethod', score: 20, threshold: 15, line: 10),
    ];
    $this->reporter->report($this->output, $results);
    $out = $this->output->fetch();
    self::assertStringContainsString('heavyMethod', $out);
    self::assertStringContainsString('20', $out);
    self::assertStringContainsString('minor', $out);
  }

  public function testShowAllDisplaysNonViolations(): void {
    $results = [
      new AnalysisResult('f.php', 'simple', score: 2, threshold: 15, line: 1),
    ];
    $this->reporter->report($this->output, $results, show_all: true);
    $out = $this->output->fetch();
    self::assertStringContainsString('simple', $out);
  }

  public function testShowAllFalseHidesNonViolations(): void {
    $results = [
      new AnalysisResult('f.php', 'simple', score: 2, threshold: 15, line: 1),
    ];
    $this->reporter->report($this->output, $results, show_all: false);
    $out = $this->output->fetch();
    self::assertStringNotContainsString('simple', $out);
  }

  public function testSortByScoreOrders(): void {
    $results = [
      new AnalysisResult('f.php', 'low', score: 5, threshold: 15, line: 1),
      new AnalysisResult('f.php', 'high', score: 20, threshold: 15, line: 2),
      new AnalysisResult('f.php', 'mid', score: 10, threshold: 15, line: 3),
    ];
    $this->reporter->report($this->output, $results, show_all: true, sort_by_score: true);
    $out = $this->output->fetch();
    // 'high' (20) must appear before 'mid' (10) before 'low' (5)
    self::assertGreaterThan(strpos($out, 'high'), strpos($out, 'mid'));
    self::assertGreaterThan(strpos($out, 'mid'), strpos($out, 'low'));
  }

  public function testSeverityCriticalLabel(): void {
    $results = [
      new AnalysisResult('f.php', 'monster', score: 55, threshold: 15, line: 1),
    ];
    $this->reporter->report($this->output, $results);
    $out = $this->output->fetch();
    self::assertStringContainsString('critical', $out);
  }

  public function testParseErrorsAppearInOutput(): void {
    $this->reporter->report($this->output, [], parse_errors: ['broken.php:syntax error']);
    $out = $this->output->fetch();
    self::assertStringContainsString('Parse error', $out);
    self::assertStringContainsString('broken.php', $out);
  }

  public function testSummaryViolationCount(): void {
    $results = [
      new AnalysisResult('f.php', 'a', score: 20, threshold: 15, line: 1),
      new AnalysisResult('f.php', 'b', score: 5, threshold: 15, line: 2),
    ];
    $this->reporter->report($this->output, $results, show_all: true);
    $out = $this->output->fetch();
    self::assertStringContainsString('1 violation(s)', $out);
    self::assertStringContainsString('2 function(s)', $out);
  }

  protected function setUp(): void {
    $this->reporter = new RichConsoleReporter();
    $this->output = new BufferedOutput();
  }

}
