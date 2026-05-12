<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Analyzer;

use NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer;
use NCAC\CognitiveComplexity\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CognitiveAnalyzer::class)]
final class CognitiveAnalyzerExtendedTest extends TestCase {

  private string $fixturesDir;

  public function testAnalyzeSingleFile(): void {
    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($this->fixturesDir . '/simple.php');

    self::assertNotEmpty($results);
  }

  public function testAnalyzeDirectoryReturnsResultsForAllFiles(): void {
    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($this->fixturesDir);

    // 3 fixture files → at least 3 results
    self::assertGreaterThanOrEqual(3, \count($results));
  }

  public function testExcludedPathsAreSkipped(): void {
    $config = new Config(15, [], ['fixtures/']);
    $analyzer = new CognitiveAnalyzer($config);

    // Create a temp directory with a subdirectory to exclude
    $tmp_dir = sys_get_temp_dir() . '/php-cc-exclude-test-' . uniqid();
    $sub_dir = $tmp_dir . '/excluded';
    mkdir($sub_dir, 0755, true);
    file_put_contents($tmp_dir . '/included.php', '<?php function included(): void {}');
    file_put_contents($sub_dir . '/excluded.php', '<?php function excluded(): void {}');

    $config = new Config(15, [], ['excluded']);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($tmp_dir);

    $files = array_map(static fn ($r) => basename($r->file), $results);
    self::assertContains('included.php', $files);
    self::assertNotContains('excluded.php', $files);

    // Cleanup
    unlink($tmp_dir . '/included.php');
    unlink($sub_dir . '/excluded.php');
    rmdir($sub_dir);
    rmdir($tmp_dir);
  }

  public function testAnalyzeReturnsEmptyForDirectoryWithNoPhpFiles(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-empty-' . uniqid();
    mkdir($tmp_dir, 0755, true);
    file_put_contents($tmp_dir . '/README.md', '# nothing');

    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($tmp_dir);

    self::assertSame([], $results);

    unlink($tmp_dir . '/README.md');
    rmdir($tmp_dir);
  }

  public function testAnalyzeDiffOnlyReturnsResultsFromGitStagedFiles(): void {
    // We're in a real git repo with staged files — git diff --cached returns actual PHP files
    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);

    // diff_only = true triggers getGitModifiedFiles()
    $results = $analyzer->analyze((string) getcwd(), true);

    // We can't assert exact results (depends on git state), but the method must not throw
    self::assertIsArray($results);
    foreach ($results as $result) {
      self::assertInstanceOf(\NCAC\CognitiveComplexity\Analyzer\AnalysisResult::class, $result);
    }
  }

  public function testAnalyzeDiffOnlyWithPathFilterReturnsOnlyMatchingFiles(): void {
    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);

    // Restrict to a subdirectory that contains no staged files
    $results = $analyzer->analyze('/nonexistent/path', true);

    self::assertSame([], $results);
  }

  public function testAnalyzeDiffOnlyWithStagedFileInTempRepo(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-git-' . uniqid();
    mkdir($tmp_dir, 0755, true);

    $orig_cwd = (string) getcwd();
    chdir($tmp_dir);

    try {
      exec('git init -q ' . escapeshellarg($tmp_dir));
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.email "test@test.com"');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.name "Test"');

      $php_file = $tmp_dir . '/sample.php';
      file_put_contents($php_file, '<?php function staged_fn(): void {}');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' add sample.php');

      $config = new Config(15);
      $analyzer = new CognitiveAnalyzer($config);
      $results = $analyzer->analyze($tmp_dir, true);

      self::assertIsArray($results);
      self::assertNotEmpty($results);
      self::assertSame('staged_fn', $results[0]->function);
    } finally {
      chdir($orig_cwd);
      exec('rm -rf ' . escapeshellarg($tmp_dir));
    }
  }

  protected function setUp(): void {
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

}
