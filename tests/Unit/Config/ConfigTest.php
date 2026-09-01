<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Config;

use NCAC\CognitiveComplexity\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Config::class)]
final class ConfigTest extends TestCase {

  public function testDefaultThreshold(): void {
    $config = new Config(15);
    self::assertSame(15, $config->getDefaultMax());
    self::assertSame(15, $config->getThresholdForPath('/any/path/file.php'));
  }

  public function testPerPathThreshold(): void {
    $config = new Config(15, [
      'src/Controller/' => 10,
      'src/Service/' => 12,
    ]);

    self::assertSame(10, $config->getThresholdForPath('src/Controller/FooController.php'));
    self::assertSame(12, $config->getThresholdForPath('src/Service/OrderService.php'));
    self::assertSame(15, $config->getThresholdForPath('src/Entity/Order.php'));
  }

  public function testMostSpecificPrefixWins(): void {
    $config = new Config(15, [
      'src/' => 20,
      'src/Controller/' => 10,
    ]);

    self::assertSame(10, $config->getThresholdForPath('src/Controller/FooController.php'));
    self::assertSame(20, $config->getThresholdForPath('src/Service/Bar.php'));
  }

  public function testExcludedPaths(): void {
    $config = new Config(15, [], ['vendor/', 'cache/']);
    self::assertSame(['vendor/', 'cache/'], $config->getExcludePatterns());
  }

  public function testIsExcludedMatchesGlobPatterns(): void {
    $config = new Config(15, [], ['vendor/', '**/files/php/'], ['php'], '/project');

    self::assertTrue($config->isExcluded('vendor/autoload.php'));
    self::assertTrue($config->isExcluded('web/sites/aaa/files/php/twig/x.php'));
    self::assertFalse($config->isExcluded('app/vendor-lib/x.php'));
    self::assertFalse($config->isExcluded('web/sites/aaa/files/phpstan.php'));
  }

  public function testGlobThresholdMostSpecificWins(): void {
    $config = new Config(15, [
      '**/Legacy/' => 40,
      'src/' => 20,
      'src/Domain/Legacy/' => 25,
    ]);

    self::assertSame(25, $config->getThresholdForPath('src/Domain/Legacy/Foo.php'));
    self::assertSame(20, $config->getThresholdForPath('src/Domain/Order.php'));
    self::assertSame(40, $config->getThresholdForPath('tests/Legacy/Bar.php'));
    self::assertSame(15, $config->getThresholdForPath('bin/console.php'));
  }

  public function testGetProjectRoot(): void {
    $config = new Config(15, [], [], ['php'], '/srv/app');
    self::assertSame('/srv/app', $config->getProjectRoot());
  }

  public function testGetExtensionsDefaultsToPhp(): void {
    $config = new Config(15);
    self::assertSame(['php'], $config->getExtensions());
  }

  public function testGetExtensionsCustom(): void {
    $config = new Config(15, [], [], ['php', 'module', 'inc']);
    self::assertSame(['php', 'module', 'inc'], $config->getExtensions());
  }

  public function testWithExtensionsReturnsNewInstanceWithOverriddenExtensions(): void {
    $original = new Config(15, ['src/' => 10], ['vendor/'], ['php']);
    $updated = $original->withExtensions(['php', 'module', 'inc']);

    self::assertSame(['php', 'module', 'inc'], $updated->getExtensions());
    self::assertSame(['php'], $original->getExtensions());
    self::assertSame(15, $updated->getDefaultMax());
    self::assertSame(['vendor/'], $updated->getExcludePatterns());
    self::assertSame(10, $updated->getThresholdForPath('src/Foo.php'));
  }

}
