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

  private CommandTester $tester;

  private string $fixturesDir;

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

  protected function setUp(): void {
    $app = new Application();
    $command = $app->find('analyse');
    $this->tester = new CommandTester($command);
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

}
