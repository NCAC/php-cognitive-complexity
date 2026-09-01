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

  private static function removeTree(string $dir): void {
    foreach (glob($dir . '/*') ?: [] as $path) {
      is_dir($path) ? self::removeTree($path) : unlink($path);
    }
    if (is_dir($dir)) {
      rmdir($dir);
    }
  }

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

  public function testExtOptionScansNonPhpFiles(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.module',
      '--ext' => 'module',
      '--max' => '100',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    self::assertStringContainsString('No cognitive complexity violations found', $this->tester->getDisplay());
  }

  public function testExtOptionOverridesConfig(): void {
    // Without --ext, a .module file is ignored (only .php scanned)
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.module',
      '--max' => '100',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    // No functions analyzed since .module is not scanned by default
    self::assertStringContainsString('No cognitive complexity violations found', $this->tester->getDisplay());
  }

  public function testExtOptionAcceptsMultipleExtensions(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir,
      '--ext' => 'php,module',
      '--max' => '100',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testBaselineOptionSuppressesKnownViolations(): void {
    // First, generate a baseline from the high complexity file. The baseline
    // keys must be project-root-relative, exactly as the check command below
    // produces them (no --config -> project root is the current directory).
    $baseline = [];
    $results = (new \NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer(
      new \NCAC\CognitiveComplexity\Config\Config(1, [], [], ['php'], (string) getcwd())
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

  public function testMissingExplicitConfigFileReturnsInvalid(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.php',
      '--config' => sys_get_temp_dir() . '/php-cc-absent-' . uniqid() . '.yaml',
    ]);

    self::assertSame(Command::INVALID, $this->tester->getStatusCode());
    self::assertStringContainsString('Config file not found', $this->tester->getDisplay());
  }

  public function testExcludeGlobFromConfigSkipsMatchingFiles(): void {
    $tmp = sys_get_temp_dir() . '/php-cc-check-exclude-' . uniqid();
    mkdir($tmp . '/src', 0755, true);
    mkdir($tmp . '/web/sites/aaa/files/php', 0755, true);
    $heavy = <<<'PHP'
    <?php
    function %s($a, $b) {
      if ($a) { foreach ([] as $x) { if ($x) { while ($x) { if ($x && $b) { echo 1; } } } } }
      return $a;
    }
    PHP;
    file_put_contents($tmp . '/src/real.php', sprintf($heavy, 'realFn'));
    file_put_contents($tmp . '/web/sites/aaa/files/php/gen.php', sprintf($heavy, 'generatedFn'));
    file_put_contents($tmp . '/cognitive.yaml', "max_complexity: 1\nexclude:\n  - \"**/files/php/\"\n");

    $this->tester->execute([
      'path' => $tmp,
      '--config' => $tmp . '/cognitive.yaml',
    ]);

    $out = $this->tester->getDisplay();
    self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    self::assertStringContainsString('src/real.php::realFn', $out);
    self::assertStringNotContainsString('generatedFn', $out);

    self::removeTree($tmp);
  }

  protected function setUp(): void {
    $app = new Application();
    $command = $app->find('check');
    $this->tester = new CommandTester($command);
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

}
