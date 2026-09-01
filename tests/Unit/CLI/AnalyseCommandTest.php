<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\CLI;

use NCAC\CognitiveComplexity\CLI\AnalyseCommand;
use NCAC\CognitiveComplexity\CLI\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(AnalyseCommand::class)]
final class AnalyseCommandTest extends TestCase {

  private const HEAVY_FN = <<<'PHP'
  <?php
  function heavy($a, $b) {
    if ($a) {
      foreach ([] as $x) {
        if ($x) {
          while ($x) {
            if ($x && $b) {
              echo 1;
            }
          }
        }
      }
    }
    return $a;
  }
  PHP;

  private CommandTester $tester;

  private string $fixturesDir;

  private string $tmpDir;

  private static function removeTree(string $dir): void {
    foreach (glob($dir . '/*') ?: [] as $path) {
      is_dir($path) ? self::removeTree($path) : unlink($path);
    }
    if (is_dir($dir)) {
      rmdir($dir);
    }
  }

  public function testReturnsSuccessWithNoViolations(): void {
    $this->tester->execute(['path' => $this->fixturesDir . '/simple.php']);
    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testReturnsSuccessEvenWithViolations(): void {
    // analyse always exits 0 — it is an exploration tool, not a gate
    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
    ]);
    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testShowAllDisplaysAllFunctions(): void {
    $this->tester->execute([
      'path'  => $this->fixturesDir . '/simple.php',
      '--all' => true,
    ]);
    $output = $this->tester->getDisplay();
    self::assertStringContainsString('simple_function', $output);
  }

  public function testSortByScoreOption(): void {
    $this->tester->execute([
      'path'   => $this->fixturesDir . '/high_complexity.php',
      '--all'  => true,
      '--sort' => 'score',
    ]);
    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testExtOptionFiltersFiles(): void {
    // Analysing a .php file with --ext=module should find nothing
    $this->tester->execute([
      'path'  => $this->fixturesDir,
      '--ext' => 'module',
    ]);
    $output = $this->tester->getDisplay();
    self::assertStringContainsString('No violations', $output);
  }

  public function testViolationAppearsInOutput(): void {
    $this->tester->execute([
      'path'  => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
    ]);
    $output = $this->tester->getDisplay();
    self::assertStringContainsString('violation(s)', $output);
  }

  public function testMissingExplicitConfigFileReturnsInvalid(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.php',
      '--config' => $this->tmpDir . '/does-not-exist.yaml',
    ]);

    self::assertSame(Command::INVALID, $this->tester->getStatusCode());
    self::assertStringContainsString('Config file not found', $this->tester->getDisplay());
  }

  public function testPathThresholdOverrideFromConfigIsApplied(): void {
    mkdir($this->tmpDir . '/src');
    file_put_contents($this->tmpDir . '/src/heavy.php', self::HEAVY_FN);
    file_put_contents(
      $this->tmpDir . '/cognitive.yaml',
      "max_complexity: 5\npaths:\n  src/: 100\n",
    );

    $this->tester->execute([
      'path' => $this->tmpDir . '/src',
      '--config' => $this->tmpDir . '/cognitive.yaml',
      '--all' => true,
    ]);

    // The per-path override (src/: 100) must survive into the analyse command.
    $output = $this->tester->getDisplay();
    self::assertStringContainsString('No violations', $output);
    self::assertStringContainsString('threshold=100', $output);
  }

  public function testExcludeGlobFromConfigIsHonoured(): void {
    mkdir($this->tmpDir . '/src', 0755, true);
    mkdir($this->tmpDir . '/var/cache', 0755, true);
    file_put_contents($this->tmpDir . '/src/real.php', str_replace('function heavy', 'function realFn', self::HEAVY_FN));
    file_put_contents($this->tmpDir . '/var/cache/gen.php', str_replace('function heavy', 'function generatedFn', self::HEAVY_FN));
    file_put_contents(
      $this->tmpDir . '/cognitive.yaml',
      "max_complexity: 1\nexclude:\n  - \"**/cache/\"\n",
    );

    $this->tester->execute([
      'path' => $this->tmpDir,
      '--config' => $this->tmpDir . '/cognitive.yaml',
      '--all' => true,
    ]);

    $output = $this->tester->getDisplay();
    self::assertStringContainsString('realFn', $output);
    self::assertStringNotContainsString('generatedFn', $output);
  }

  protected function setUp(): void {
    $app = new Application();
    $command = $app->find('analyse');
    $this->tester = new CommandTester($command);
    $this->fixturesDir = __DIR__ . '/../../fixtures';
    $this->tmpDir = sys_get_temp_dir() . '/php-cc-analyse-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void {
    self::removeTree($this->tmpDir);
  }

}
