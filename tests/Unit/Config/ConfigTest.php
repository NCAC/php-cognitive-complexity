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
    self::assertSame(['vendor/', 'cache/'], $config->getExcludedPaths());
  }

  public function testGetExtensionsDefaultsToPhp(): void {
    $config = new Config(15);
    self::assertSame(['php'], $config->getExtensions());
  }

  public function testGetExtensionsCustom(): void {
    $config = new Config(15, [], [], ['php', 'module', 'inc']);
    self::assertSame(['php', 'module', 'inc'], $config->getExtensions());
  }

}
