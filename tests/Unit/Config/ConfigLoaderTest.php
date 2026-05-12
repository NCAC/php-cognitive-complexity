<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Config;

use NCAC\CognitiveComplexity\Config\ConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
final class ConfigLoaderTest extends TestCase {

  private string $tmpDir;

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
    self::assertContains('vendor/', $config->getExcludedPaths());
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

  public function testReturnsDefaultWhenFileDoesNotExist(): void {
    $config = ConfigLoader::load('/nonexistent/path/cognitive.yaml', 12);
    self::assertSame(12, $config->getDefaultMax());
  }

  protected function setUp(): void {
    $this->tmpDir = sys_get_temp_dir() . '/php-cc-tests-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void {
    array_map('unlink', glob($this->tmpDir . '/*') ?: []);
    rmdir($this->tmpDir);
  }

}
