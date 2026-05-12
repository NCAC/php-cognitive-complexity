<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Reporter\ConsoleReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(ConsoleReporter::class)]
final class ConsoleReporterTest extends TestCase {

  private BufferedOutput $output;

  private ConsoleReporter $reporter;

  private string $tmpDir;

  public function testReportReturnsFalseWithNoViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 5, threshold: 15, line: 1),
    ];

    $has_violations = $this->reporter->report($results);

    self::assertFalse($has_violations);
    self::assertStringContainsString('No cognitive complexity violations found', $this->output->fetch());
  }

  public function testReportReturnsTrueWithViolations(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
    ];

    $has_violations = $this->reporter->report($results);

    self::assertTrue($has_violations);
    $out = $this->output->fetch();
    self::assertStringContainsString('bar', $out);
    self::assertStringContainsString('20', $out);
    self::assertStringContainsString('15', $out);
  }

  public function testReportSuppressesViolationsCoveredByBaseline(): void {
    $baseline = [$this->tmpDir . '/baseline.json', json_encode([
      '/src/Foo.php' => ['bar' => 20],
    ])];
    file_put_contents($baseline[0], $baseline[1]);

    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
    ];

    $has_violations = $this->reporter->report($results, $baseline[0]);

    self::assertFalse($has_violations);
    self::assertStringContainsString('No cognitive complexity violations found', $this->output->fetch());
  }

  public function testReportShowsViolationWhenScoreExceedsBaseline(): void {
    $baseline_file = $this->tmpDir . '/baseline.json';
    file_put_contents($baseline_file, json_encode([
      '/src/Foo.php' => ['bar' => 15],
    ]));

    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
    ];

    $has_violations = $this->reporter->report($results, $baseline_file);

    self::assertTrue($has_violations);
  }

  public function testReportWithMissingBaselineFileIgnoresBaseline(): void {
    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
    ];

    $has_violations = $this->reporter->report($results, '/nonexistent/baseline.json');

    self::assertTrue($has_violations);
  }

  public function testReportShowsRelativePathWhenFileIsUnderCwd(): void {
    $cwd = (string) getcwd();
    $file = $cwd . '/src/SomeClass.php';
    $results = [
      new AnalysisResult($file, 'myMethod', score: 20, threshold: 15, line: 5),
    ];

    $this->reporter->report($results);
    $out = $this->output->fetch();

    // Should show relative path, not absolute
    self::assertStringNotContainsString($cwd, $out);
    self::assertStringContainsString('src/SomeClass.php', $out);
  }

  public function testReportWithCorruptBaselineFileIgnoresBaseline(): void {
    $baseline_file = $this->tmpDir . '/corrupt.json';
    file_put_contents($baseline_file, 'not valid json {{');

    $results = [
      new AnalysisResult('/src/Foo.php', 'bar', score: 20, threshold: 15, line: 10),
    ];

    $has_violations = $this->reporter->report($results, $baseline_file);
    self::assertTrue($has_violations);
  }

  protected function setUp(): void {
    $this->output = new BufferedOutput();
    $this->reporter = new ConsoleReporter($this->output);
    $this->tmpDir = sys_get_temp_dir() . '/php-cc-reporter-tests-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void {
    array_map('unlink', glob($this->tmpDir . '/*') ?: []);
    rmdir($this->tmpDir);
  }

}
