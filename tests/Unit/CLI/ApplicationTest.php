<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\CLI;

use NCAC\CognitiveComplexity\CLI\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
final class ApplicationTest extends TestCase {

  public function testApplicationRegistersAnalyseCommand(): void {
    $app = new Application();
    self::assertTrue($app->has('analyse'));
  }

  public function testApplicationRegistersCheckCommand(): void {
    $app = new Application();
    self::assertTrue($app->has('check'));
  }

  public function testApplicationRegistersBaselineCommand(): void {
    $app = new Application();
    self::assertTrue($app->has('baseline'));
  }

}
