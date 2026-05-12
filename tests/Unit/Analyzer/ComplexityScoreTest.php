<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Analyzer;

use NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer;
use NCAC\CognitiveComplexity\Analyzer\ComplexityVisitor;
use NCAC\CognitiveComplexity\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CognitiveAnalyzer::class)]
#[CoversClass(ComplexityVisitor::class)]
final class ComplexityScoreTest extends TestCase {

  private CognitiveAnalyzer $analyzer;

  private string $fixturesDir;

  // -------------------------------------------------------------------------
  // control_flow.php
  // -------------------------------------------------------------------------

  /**
   * @return list<array{string, int}>
   */
  public static function logicalOperatorProvider(): array {
    return [
      ['logical_and_simple', 1],
      ['logical_or_simple', 1],
      ['logical_and_chain', 1],
      ['logical_mixed_and_or', 2],
      ['logical_complex', 3],
      ['logical_keyword_and', 1],
      ['logical_keyword_or', 1],
    ];
  }

  /**
   * @return list<array{string, int}>
   */
  public static function ternaryProvider(): array {
    return [
      ['ternary_single', 1],
      ['ternary_double', 2],
      ['ternary_inside_if', 2],
      ['ternary_with_logical', 3],
    ];
  }

  public function testControlFlowFlatScore(): void {
    $results = $this->analyzeFunction('control_flow.php', 'control_flow_flat');
    self::assertSame(10, $results->score, 'control_flow_flat expected score 10');
  }// -------------------------------------------------------------------------
  // logical_operators.php
  // -------------------------------------------------------------------------

  #[DataProvider('logicalOperatorProvider')]
  public function testLogicalOperatorScore(string $function, int $expected): void {
    $result = $this->analyzeFunction('logical_operators.php', $function);
    self::assertSame(
      $expected,
      $result->score,
      "{$function} expected score {$expected}, got {$result->score}"
    );
  }// -------------------------------------------------------------------------
  // ternary.php
  // -------------------------------------------------------------------------

  #[DataProvider('ternaryProvider')]
  public function testTernaryScore(string $function, int $expected): void {
    $result = $this->analyzeFunction('ternary.php', $function);
    self::assertSame(
      $expected,
      $result->score,
      "{$function} expected score {$expected}, got {$result->score}"
    );
  }

  // -------------------------------------------------------------------------
  // high_complexity.php — score connu : 11
  // -------------------------------------------------------------------------

  public function testHighComplexityKnownScore(): void {
    $result = $this->analyzeFunction('high_complexity.php', 'process_order');
    self::assertSame(12, $result->score, 'process_order expected score 12');
  }

  // -------------------------------------------------------------------------
  // nested.php — score connu : 6
  // -------------------------------------------------------------------------

  public function testNestedKnownScore(): void {
    $result = $this->analyzeFunction('nested.php', 'nested_example');
    self::assertSame(6, $result->score, 'nested_example expected score 6');
  }

  // -------------------------------------------------------------------------
  // simple.php — score 0
  // -------------------------------------------------------------------------

  public function testSimpleKnownScore(): void {
    $result = $this->analyzeFunction('simple.php', 'simple_function');
    self::assertSame(0, $result->score, 'simple_function expected score 0');
  }

  // -------------------------------------------------------------------------
  // closures.php
  // -------------------------------------------------------------------------

  public function testClosureOuterScore(): void {
    $result = $this->analyzeFunction('closures.php', 'with_closure');
    self::assertSame(1, $result->score, 'with_closure outer expected 1');
  }

  public function testClosureInnerScore(): void {
    $result = $this->analyzeFunction('closures.php', '{closure}:18');
    self::assertSame(1, $result->score, '{closure}:18 expected 1');
  }

  public function testClosureInIfOuterScore(): void {
    $result = $this->analyzeFunction('closures.php', 'with_closure_in_if');
    self::assertSame(3, $result->score, 'with_closure_in_if outer expected 3');
  }

  public function testClosureInIfInnerScore(): void {
    $result = $this->analyzeFunction('closures.php', '{closure}:36');
    self::assertSame(1, $result->score, '{closure}:36 expected 1');
  }

  public function testArrowFnOuterScore(): void {
    $result = $this->analyzeFunction('closures.php', 'with_arrow_fn');
    self::assertSame(1, $result->score, 'with_arrow_fn outer expected 1');
  }

  public function testArrowFnInnerScore(): void {
    $result = $this->analyzeFunction('closures.php', '{arrow_fn}:56');
    self::assertSame(0, $result->score, '{arrow_fn}:56 expected 0');
  }

  // -------------------------------------------------------------------------
  // recursion.php
  // -------------------------------------------------------------------------

  public function testFactorialScore(): void {
    $result = $this->analyzeFunction('recursion.php', 'factorial');
    self::assertSame(2, $result->score, 'factorial expected 2');
  }

  public function testSumRecursiveScore(): void {
    $result = $this->analyzeFunction('recursion.php', 'sum_recursive');
    self::assertSame(2, $result->score, 'sum_recursive expected 2');
  }

  public function testTreeFlattenScore(): void {
    $result = $this->analyzeFunction('recursion.php', 'flatten');
    self::assertSame(5, $result->score, 'Tree::flatten expected 5');
  }

  // -------------------------------------------------------------------------
  // jumps.php
  // -------------------------------------------------------------------------

  public function testBreakLabelScore(): void {
    $result = $this->analyzeFunction('jumps.php', 'break_label');
    self::assertSame(7, $result->score, 'break_label expected 7');
  }

  public function testContinueLabelScore(): void {
    $result = $this->analyzeFunction('jumps.php', 'continue_label');
    self::assertSame(7, $result->score, 'continue_label expected 7');
  }

  public function testPlainBreakScore(): void {
    $result = $this->analyzeFunction('jumps.php', 'plain_break');
    self::assertSame(3, $result->score, 'plain_break expected 3');
  }

  // -------------------------------------------------------------------------
  // Helpers
  // -------------------------------------------------------------------------

  protected function setUp(): void {
    $this->analyzer = new CognitiveAnalyzer(new Config(999));
    $this->fixturesDir = __DIR__ . '/../../fixtures';
  }

  /**
   * Analyze a single fixture file and return the result for the given function.
   */
  private function analyzeFunction(
    string $fixture,
    string $function_name
  ): \NCAC\CognitiveComplexity\Analyzer\AnalysisResult {
    $results = $this->analyzer->analyze($this->fixturesDir . '/' . $fixture);

    $match = array_values(array_filter(
      $results,
      static fn ($r) => $r->function === $function_name
    ));

    self::assertNotEmpty($match, "Function '{$function_name}' not found in {$fixture}");

    return $match[0];
  }

}
