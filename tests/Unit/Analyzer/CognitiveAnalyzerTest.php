<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Analyzer;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer;
use NCAC\CognitiveComplexity\Analyzer\ComplexityVisitor;
use NCAC\CognitiveComplexity\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CognitiveAnalyzer::class)]
#[CoversClass(ComplexityVisitor::class)]
final class CognitiveAnalyzerTest extends TestCase {

  private string $fixturesDir;

  public function testAnalyzeSimpleFunction(): void {
    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($this->fixturesDir . '/simple.php');

    self::assertNotEmpty($results);

    $result = $results[0];
    self::assertInstanceOf(AnalysisResult::class, $result);
    self::assertSame('simple_function', $result->function);
    self::assertSame(0, $result->score);
    self::assertFalse($result->hasViolation());
  }

  public function testAnalyzeHighComplexityFunction(): void {
    $config = new Config(5);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($this->fixturesDir . '/high_complexity.php');

    $violations = array_filter($results, static fn (AnalysisResult $r) => $r->hasViolation());
    self::assertNotEmpty($violations);
  }

  public function testAnalyzeNestedStructures(): void {
    $config = new Config(20);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($this->fixturesDir . '/nested.php');

    self::assertNotEmpty($results);

    // Score must reflect nesting bonus
    $result = $results[0];
    self::assertGreaterThan(1, $result->score);
  }

  public function testThresholdFromConfig(): void {
    $config = new Config(3);
    $analyzer = new CognitiveAnalyzer($config);

    $results = $analyzer->analyze($this->fixturesDir . '/high_complexity.php');

    foreach ($results as $result) {
      self::assertSame(3, $result->threshold);
    }
  }

  public function testTernaryOperatorIncreasesScore(): void {
    $tmp = sys_get_temp_dir() . '/test_ternary_' . uniqid() . '.php';
    file_put_contents($tmp, '<?php function ternary_fn(int $x): string { return $x > 0 ? "pos" : "neg"; }');

    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);
    $results = $analyzer->analyze($tmp);
    unlink($tmp);

    self::assertNotEmpty($results);
    self::assertGreaterThan(0, $results[0]->score);
  }

  public function testNestedFunctionIncreasesScore(): void {
    $tmp = sys_get_temp_dir() . '/test_nested_fn_' . uniqid() . '.php';
    $code = <<<'PHP'
<?php
function outer(): void {
    function inner(): void {}
}
PHP;
    file_put_contents($tmp, $code);

    $config = new Config(15);
    $analyzer = new CognitiveAnalyzer($config);
    $results = $analyzer->analyze($tmp);
    unlink($tmp);

    self::assertNotEmpty($results);
    // outer() should have score >= 1 due to nested function
    $outer = array_filter($results, static fn ($r) => $r->function === 'outer');
    self::assertNotEmpty($outer, 'outer() function should appear in results');
    self::assertGreaterThanOrEqual(1, array_values($outer)[0]->score, 'outer() should have score >= 1 due to nested function');
  }

  protected function setUp(): void {
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

}
