<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\CLI;

use NCAC\CognitiveComplexity\CLI\Application;
use NCAC\CognitiveComplexity\CLI\CheckCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CheckCommand::class)]
final class CheckCommandTest extends TestCase {

  private CommandTester $tester;

  private string $fixturesDir;

  public function testExitCodeZeroWhenNoViolations(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.php',
      '--max' => '100',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    self::assertStringContainsString('No cognitive complexity violations found', $this->tester->getDisplay());
  }

  public function testExitCodeOneWhenViolationsFound(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
    ]);

    self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
  }

  public function testJsonFormatOutputsValidJson(): void {
    ob_start();
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.php',
      '--format' => 'json',
      '--max' => '100',
    ]);
    $output = ob_get_clean();

    self::assertJson((string) $output);
  }

  public function testCustomMaxThresholdIsRespected(): void {
    // With threshold 0, even a simple function should be analyzed
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.php',
      '--max' => '0',
    ]);

    // simple.php has score 0 so threshold 0 means no violation (score NOT > threshold)
    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testAnalyzesDirectory(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir,
      '--max' => '100',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testGitlabFormatOutputsValidJson(): void {
    ob_start();
    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--format' => 'gitlab',
      '--max' => '1',
    ]);
    $output = ob_get_clean();

    self::assertJson((string) $output);
  }

  public function testBaselineOptionSuppressesKnownViolations(): void {
    // First, generate a baseline from the high complexity file
    $baseline = [];
    $results = (new \NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer(
      new \NCAC\CognitiveComplexity\Config\Config(1)
    ))->analyze($this->fixturesDir . '/high_complexity.php');
    foreach ($results as $r) {
      $baseline[$r->file][$r->function] = $r->score;
    }
    $baseline_file = sys_get_temp_dir() . '/test-baseline-' . uniqid() . '.json';
    file_put_contents($baseline_file, json_encode($baseline));

    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
      '--baseline' => $baseline_file,
    ]);

    unlink($baseline_file);
    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  protected function setUp(): void {
    $app = new Application();
    $command = $app->find('check');
    $this->tester = new CommandTester($command);
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

}
