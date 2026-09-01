<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\CLI;

use NCAC\CognitiveComplexity\CLI\Application;
use NCAC\CognitiveComplexity\CLI\BaselineCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(BaselineCommand::class)]
final class BaselineCommandTest extends TestCase {

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

  public function testBaselineOutputsValidJson(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir,
      '--max' => '5',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    $output = $this->tester->getDisplay();
    self::assertJson($output);
  }

  public function testBaselineJsonContainsViolatingFunctions(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
    ]);

    $output = $this->tester->getDisplay();
    $data = json_decode($output, true);
    self::assertIsArray($data);
    self::assertNotEmpty($data);
  }

  public function testBaselineWithNoViolationsOutputsEmptyJson(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/simple.php',
      '--max' => '100',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    $output = $this->tester->getDisplay();
    $data = json_decode($output, true);
    self::assertIsArray($data);
    // simple.php score is 0, threshold 100 → no violation → empty baseline
    foreach ($data as $functions) {
      foreach ($functions as $score) {
        self::assertLessThanOrEqual(100, $score);
      }
    }
  }

  public function testBaselineWritesToOutputFile(): void {
    $output_file = $this->tmpDir . '/baseline.json';

    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
      '--output' => $output_file,
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    self::assertFileExists($output_file);
    $content = file_get_contents($output_file);
    self::assertJson((string) $content);
  }

  public function testBaselineReturnsSuccessAlways(): void {
    // Baseline command always exits 0 regardless of violations
    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--max' => '1',
    ]);

    self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
  }

  public function testMissingExplicitConfigFileReturnsInvalid(): void {
    $this->tester->execute([
      'path' => $this->fixturesDir . '/high_complexity.php',
      '--config' => $this->tmpDir . '/nope.yaml',
    ]);

    self::assertSame(Command::INVALID, $this->tester->getStatusCode());
    self::assertStringContainsString('Config file not found', $this->tester->getDisplay());
  }

  public function testBaselineKeysAreProjectRootRelative(): void {
    mkdir($this->tmpDir . '/modules/custom', 0755, true);
    file_put_contents(
      $this->tmpDir . '/modules/custom/heavy.php',
      <<<'PHP'
      <?php
      function heavy($a, $b) {
        if ($a) { foreach ([] as $x) { if ($x) { while ($x) { if ($x && $b) { echo 1; } } } } }
        return $a;
      }
      PHP,
    );
    file_put_contents($this->tmpDir . '/cognitive.yaml', "max_complexity: 1\n");

    $this->tester->execute([
      'path' => $this->tmpDir . '/modules',
      '--config' => $this->tmpDir . '/cognitive.yaml',
    ]);

    /** @var array<string, array<string, int>> $data */
    $data = json_decode($this->tester->getDisplay(), true);
    self::assertArrayHasKey('modules/custom/heavy.php', $data);
    foreach (array_keys($data) as $key) {
      self::assertStringStartsNotWith('/', $key);
    }
  }

  protected function setUp(): void {
    $app = new Application();
    $command = $app->find('baseline');
    $this->tester = new CommandTester($command);
    $this->fixturesDir = __DIR__ . '/../../fixtures';
    $this->tmpDir = sys_get_temp_dir() . '/php-cc-baseline-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void {
    self::removeTree($this->tmpDir);
  }

}
