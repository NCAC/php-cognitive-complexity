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

  private static function removeTree(string $dir): void {
    foreach (glob($dir . '/*') ?: [] as $path) {
      is_dir($path) ? self::removeTree($path) : unlink($path);
    }
    if (is_dir($dir)) {
      rmdir($dir);
    }
  }

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
    // Directory tree mimicking web/sites/<site>/files/php generated caches.
    $tmp_dir = sys_get_temp_dir() . '/php-cc-exclude-test-' . uniqid();
    mkdir($tmp_dir . '/modules/custom', 0755, true);
    mkdir($tmp_dir . '/sites/aaa/files/php', 0755, true);
    file_put_contents($tmp_dir . '/modules/custom/included.php', '<?php function included(): void {}');
    file_put_contents($tmp_dir . '/sites/aaa/files/php/excluded.php', '<?php function excluded(): void {}');

    // Patterns are project-root-relative; **/ makes them match at any depth.
    $config = new Config(15, [], ['**/files/php/'], ['php'], $tmp_dir);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($tmp_dir);

    $files = array_map(static fn ($r) => basename($r->file), $results);
    self::assertContains('included.php', $files);
    self::assertNotContains('excluded.php', $files);
    // Reported paths are project-root-relative.
    self::assertContains('modules/custom/included.php', array_map(static fn ($r) => $r->file, $results));

    self::removeTree($tmp_dir);
  }

  public function testExcludeMatchingIsIndependentOfTheScannedArgument(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-exclude-arg-' . uniqid();
    mkdir($tmp_dir . '/web/sites/aaa/files/php', 0755, true);
    mkdir($tmp_dir . '/web/modules', 0755, true);
    file_put_contents($tmp_dir . '/web/modules/real.php', '<?php function real(): void {}');
    file_put_contents($tmp_dir . '/web/sites/aaa/files/php/gen.php', '<?php function gen(): void {}');

    $config = new Config(15, [], ['web/sites/**/files/php/'], ['php'], $tmp_dir);

    $from_root = (new CognitiveAnalyzer($config))->analyze($tmp_dir);
    $from_web = (new CognitiveAnalyzer($config))->analyze($tmp_dir . '/web');
    $from_sites = (new CognitiveAnalyzer($config))->analyze($tmp_dir . '/web/sites');

    $names = static fn (array $rs) => array_map(static fn ($r) => basename($r->file), $rs);

    self::assertNotContains('gen.php', $names($from_root));
    self::assertNotContains('gen.php', $names($from_web));
    self::assertNotContains('gen.php', $names($from_sites));
    self::assertContains('real.php', $names($from_root));

    self::removeTree($tmp_dir);
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

  public function testDiffModeHonoursExcludePatterns(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-git-exclude-' . uniqid();
    mkdir($tmp_dir . '/sites/aaa/files/php', 0755, true);
    mkdir($tmp_dir . '/modules', 0755, true);

    $orig_cwd = (string) getcwd();
    chdir($tmp_dir);

    try {
      exec('git init -q ' . escapeshellarg($tmp_dir));
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.email "test@test.com"');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.name "Test"');

      file_put_contents($tmp_dir . '/modules/real.php', '<?php function real_fn(): void {}');
      file_put_contents($tmp_dir . '/sites/aaa/files/php/gen.php', '<?php function generated_fn(): void {}');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' add -A');

      $config = new Config(15, [], ['**/files/php/'], ['php'], $tmp_dir);
      $results = (new CognitiveAnalyzer($config))->analyze($tmp_dir, true);

      $functions = array_map(static fn ($r) => $r->function, $results);
      self::assertContains('real_fn', $functions);
      self::assertNotContains('generated_fn', $functions);
    } finally {
      chdir($orig_cwd);
      exec('rm -rf ' . escapeshellarg($tmp_dir));
    }
  }

  public function testFileOutsideProjectRootKeepsRootlessRelativePath(): void {
    $root = sys_get_temp_dir() . '/php-cc-root-' . uniqid();
    $outside = sys_get_temp_dir() . '/php-cc-outside-' . uniqid();
    mkdir($root, 0755, true);
    mkdir($outside, 0755, true);
    file_put_contents($outside . '/heavy.php', '<?php function heavy(){ if(1){ if(2){ echo 3; } } }');

    $config = new Config(15, [], [], ['php'], $root);
    $results = (new CognitiveAnalyzer($config))->analyze($outside . '/heavy.php');

    self::assertNotEmpty($results);
    self::assertStringStartsNotWith('/', $results[0]->file);
    self::assertStringContainsString('heavy.php', $results[0]->file);

    self::removeTree($root);
    self::removeTree($outside);
  }

  public function testDiffModeIgnoresNonMatchingExtensions(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-git-ext-' . uniqid();
    mkdir($tmp_dir, 0755, true);
    $orig_cwd = (string) getcwd();
    chdir($tmp_dir);

    try {
      exec('git init -q ' . escapeshellarg($tmp_dir));
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.email "test@test.com"');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.name "Test"');

      file_put_contents($tmp_dir . '/sample.php', '<?php function staged_fn(): void {}');
      file_put_contents($tmp_dir . '/notes.txt', 'not php');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' add -A');

      $config = new Config(15, [], [], ['php'], $tmp_dir);
      $results = (new CognitiveAnalyzer($config))->analyze($tmp_dir, true);

      self::assertSame(['staged_fn'], array_map(static fn ($r) => $r->function, $results));
    } finally {
      chdir($orig_cwd);
      exec('rm -rf ' . escapeshellarg($tmp_dir));
    }
  }

  public function testDiffModeReturnsEmptyWhenNothingChanged(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-git-clean-' . uniqid();
    mkdir($tmp_dir, 0755, true);
    $orig_cwd = (string) getcwd();
    chdir($tmp_dir);

    try {
      exec('git init -q ' . escapeshellarg($tmp_dir));
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.email "test@test.com"');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.name "Test"');
      file_put_contents($tmp_dir . '/committed.php', '<?php function committed_fn(): void {}');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' add -A');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' commit -q -m init');

      $config = new Config(15, [], [], ['php'], $tmp_dir);
      $results = (new CognitiveAnalyzer($config))->analyze($tmp_dir, true);

      self::assertSame([], $results);
    } finally {
      chdir($orig_cwd);
      exec('rm -rf ' . escapeshellarg($tmp_dir));
    }
  }

  public function testDiffModeSkipsStagedFilesDeletedFromWorktree(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-git-gone-' . uniqid();
    mkdir($tmp_dir, 0755, true);
    $orig_cwd = (string) getcwd();
    chdir($tmp_dir);

    try {
      exec('git init -q ' . escapeshellarg($tmp_dir));
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.email "test@test.com"');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.name "Test"');
      file_put_contents($tmp_dir . '/ghost.php', '<?php function ghost_fn(): void {}');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' add -A');
      unlink($tmp_dir . '/ghost.php'); // staged but no longer on disk -> realpath() fails

      $config = new Config(15, [], [], ['php'], $tmp_dir);
      $results = (new CognitiveAnalyzer($config))->analyze($tmp_dir, true);

      self::assertSame([], $results);
    } finally {
      chdir($orig_cwd);
      exec('rm -rf ' . escapeshellarg($tmp_dir));
    }
  }

  public function testDiffModeSkipsFilesOutsideTheBasePath(): void {
    $tmp_dir = sys_get_temp_dir() . '/php-cc-git-base-' . uniqid();
    mkdir($tmp_dir . '/src', 0755, true);
    mkdir($tmp_dir . '/other', 0755, true);
    $orig_cwd = (string) getcwd();
    chdir($tmp_dir);

    try {
      exec('git init -q ' . escapeshellarg($tmp_dir));
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.email "test@test.com"');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' config user.name "Test"');

      file_put_contents($tmp_dir . '/src/x.php', '<?php function in_src(): void {}');
      exec('git -C ' . escapeshellarg($tmp_dir) . ' add -A');

      $config = new Config(15, [], [], ['php'], $tmp_dir);
      $results = (new CognitiveAnalyzer($config))->analyze($tmp_dir . '/other', true);

      self::assertSame([], $results);
    } finally {
      chdir($orig_cwd);
      exec('rm -rf ' . escapeshellarg($tmp_dir));
    }
  }

  protected function setUp(): void {
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

}
