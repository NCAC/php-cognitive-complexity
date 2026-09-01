<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Config;

use NCAC\CognitiveComplexity\Config\ConfigException;
use NCAC\CognitiveComplexity\Config\ConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
final class ConfigLoaderTest extends TestCase {

  private string $tmpDir;

  private static function removeTree(string $dir): void {
    foreach (glob($dir . '/*') ?: [] as $path) {
      is_dir($path) ? self::removeTree($path) : unlink($path);
    }
    if (is_dir($dir)) {
      rmdir($dir);
    }
  }

  public function testLoadsDefaultsWithNoFile(): void {
    $config = ConfigLoader::load(null, 15);
    self::assertSame(15, $config->getDefaultMax());
  }

  public function testLoadsFromYamlFile(): void {
    $yaml = <<<YAML
max_complexity: 10
paths:
  src/Controller/: 8
exclude:
  - vendor/
  - cache/
YAML;
    file_put_contents($this->tmpDir . '/cognitive.yaml', $yaml);

    $config = ConfigLoader::load($this->tmpDir . '/cognitive.yaml');

    self::assertSame(10, $config->getDefaultMax());
    self::assertSame(8, $config->getThresholdForPath('src/Controller/Foo.php'));
    self::assertContains('vendor/', $config->getExcludePatterns());
  }

  public function testFallsBackToDefaultMaxWhenNotInFile(): void {
    file_put_contents($this->tmpDir . '/cognitive.yaml', "exclude:\n  - vendor/\n");

    $config = ConfigLoader::load($this->tmpDir . '/cognitive.yaml', 20);

    self::assertSame(20, $config->getDefaultMax());
  }

  public function testAutoDiscoversCognitiveYamlInCwd(): void {
    $orig_cwd = (string) getcwd();
    chdir($this->tmpDir);
    file_put_contents($this->tmpDir . '/cognitive.yaml', "max_complexity: 7\n");

    try {
      $config = ConfigLoader::load(null);
      self::assertSame(7, $config->getDefaultMax());
    } finally {
      chdir($orig_cwd);
    }
  }

  public function testThrowsWhenExplicitConfigFileDoesNotExist(): void {
    $this->expectException(ConfigException::class);
    ConfigLoader::load('/nonexistent/path/cognitive.yaml', 12);
  }

  public function testThrowsConfigExceptionOnMalformedYaml(): void {
    file_put_contents($this->tmpDir . '/cognitive.yaml', "max_complexity: [unclosed\n  - : :\n");

    $this->expectException(ConfigException::class);
    $this->expectExceptionMessage('Cannot parse config file');
    ConfigLoader::load($this->tmpDir . '/cognitive.yaml');
  }

  public function testProjectRootDefaultsToConfigFileDirectory(): void {
    file_put_contents($this->tmpDir . '/cognitive.yaml', "max_complexity: 15\n");

    $config = ConfigLoader::load($this->tmpDir . '/cognitive.yaml');

    self::assertSame(realpath($this->tmpDir), $config->getProjectRoot());
  }

  public function testRootKeyOverridesProjectRootRelativeToConfigDir(): void {
    mkdir($this->tmpDir . '/nested');
    file_put_contents($this->tmpDir . '/nested/cognitive.yaml', "root: ..\nmax_complexity: 15\n");

    $config = ConfigLoader::load($this->tmpDir . '/nested/cognitive.yaml');

    self::assertSame(realpath($this->tmpDir), $config->getProjectRoot());
  }

  public function testExcludePatternsAreGlobAware(): void {
    $yaml = <<<YAML
    max_complexity: 15
    exclude:
      - "**/files/php/"
    YAML;
    file_put_contents($this->tmpDir . '/cognitive.yaml', $yaml);

    $config = ConfigLoader::load($this->tmpDir . '/cognitive.yaml');

    self::assertTrue($config->isExcluded('web/sites/aaa/files/php/twig/x.php'));
    self::assertFalse($config->isExcluded('web/modules/custom/real.php'));
  }

  public function testLoadsExtensionsFromYaml(): void {
    $yaml = <<<YAML
max_complexity: 15
extensions:
  - php
  - module
  - inc
YAML;
    file_put_contents($this->tmpDir . '/cognitive.yaml', $yaml);

    $config = ConfigLoader::load($this->tmpDir . '/cognitive.yaml');

    self::assertSame(['php', 'module', 'inc'], $config->getExtensions());
  }

  public function testDefaultExtensionsWhenNotInYaml(): void {
    file_put_contents($this->tmpDir . '/cognitive.yaml', "max_complexity: 15\n");

    $config = ConfigLoader::load($this->tmpDir . '/cognitive.yaml');

    self::assertSame(['php'], $config->getExtensions());
  }

  protected function setUp(): void {
    $this->tmpDir = sys_get_temp_dir() . '/php-cc-tests-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void {
    self::removeTree($this->tmpDir);
  }

}
