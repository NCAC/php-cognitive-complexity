<?php declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Analyzer;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

/**
 * AST visitor that computes cognitive complexity per function/method.
 *
 * Algorithm based on G. Ann Campbell's "Cognitive Complexity" (SonarSource):
 *
 *  A. Structural increments (+ nesting bonus):
 *     if, for, foreach, while, do, switch, try, closure, arrow function
 *
 *  B. Continuation increments (+1 flat, no nesting bonus):
 *     elseif, else, catch
 *
 *  C. Other flat increments (+1):
 *     ternary ?:, logical operator sequence change, recursive call,
 *     break N / continue N / goto (jump to label)
 *
 *  D. Ignored:
 *     finally (no increment per spec)
 *
 * @see https://www.sonarsource.com/docs/CognitiveComplexity.pdf
 */
final class ComplexityVisitor extends NodeVisitorAbstract {

  /** @var list<AnalysisResult> */
  private array $results = [];

  private int $currentScore = 0;

  private int $nestingLevel = 0;

  private string $currentFunction = '';

  private int $currentLine = 0;

  private bool $insideFunction = false;

  /** @var list<array{function: string, line: int, score: int, nesting: int}> */
  private array $stack = [];

  public function __construct(
    private readonly string $file_path,
    private readonly int $threshold,
  ) {
  }

  /** @override */
  public function enterNode(Node $node): null|int|Node {
    if ($node instanceof Stmt\ClassMethod || $node instanceof Stmt\Function_) {
      $this->pushScope($node->name->toString(), $node->getStartLine());

      return null;
    }

    if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
      $this->enterLambda($node);

      return null;
    }

    if (!$this->insideFunction) {
      return null;
    }

    $this->scoreNode($node);

    return null;
  }

  /** @override */
  public function leaveNode(Node $node): null|int|Node|array {
    $is_function_node = $node instanceof Stmt\ClassMethod
    || $node instanceof Stmt\Function_
    || $node instanceof Node\Expr\Closure
    || $node instanceof Node\Expr\ArrowFunction;

    if ($is_function_node) {
      if (!$this->insideFunction) {
        return null;
      }

      // Closures only opened a scope if we were already inside a function
      if (
        ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction)
        && $this->stack === []
      ) {
        return null;
      }

      $this->results[] = new AnalysisResult(
        file: $this->file_path,
        function: $this->currentFunction,
        score: $this->currentScore,
        threshold: $this->threshold,
        line: $this->currentLine,
      );

      $this->popScope();

      return null;
    }

    if ($this->insideFunction && $this->isNestingNode($node)) {
      $this->nestingLevel = max(0, $this->nestingLevel - 1);
    }

    return null;
  }

  /**
   * @return list<AnalysisResult>
   */
  public function getResults(): array {
    return $this->results;
  }

  /**
   * Handle closure / arrow function entry (spec §2.2).
   *
   * @param Node\Expr\Closure|Node\Expr\ArrowFunction $node
   */
  private function enterLambda(Node $node): void {
    if (!$this->insideFunction) {
      return;
    }

    $label = $node instanceof Node\Expr\ArrowFunction ? '{arrow_fn}' : '{closure}';
    $this->pushScope($label . ':' . $node->getStartLine(), $node->getStartLine(), with_nesting_bonus: true);
  }

  /**
   * Dispatch scoring for nodes inside a function scope.
   */
  private function scoreNode(Node $node): void {
    if ($this->isNestingNode($node)) {
      $this->currentScore += 1 + $this->nestingLevel;
      $this->nestingLevel++;

      return;
    }

    if ($this->isContinuationNode($node) || $node instanceof Ternary) {
      $this->currentScore++;

      return;
    }

    if ($this->isLogicalBinaryOp($node)) {
      $this->scoreLogicalOp($node);

      return;
    }

    if ($this->isRecursiveCall($node) || $this->isLabelJump($node)) {
      $this->currentScore++;
    }
  }

  private function isContinuationNode(Node $node): bool {
    return $node instanceof Stmt\ElseIf_
    || $node instanceof Stmt\Else_
    || $node instanceof Stmt\Catch_;
  }

  /**
   * Score a logical binary operator: +1 on each family change (spec §1.4).
   */
  private function scoreLogicalOp(Node $node): void {
    /** @var Node\Expr\BinaryOp $node */
    $left = $node->left;
    $same_family = ($this->isAndOp($node) && $this->isAndOp($left))
    || ($this->isOrOp($node) && $this->isOrOp($left));

    if (!$same_family) {
      $this->currentScore++;
    }
  }

  /**
   * Returns true for goto and non-trivial break/continue (spec §2.3).
   */
  private function isLabelJump(Node $node): bool {
    if ($node instanceof Stmt\Goto_) {
      return true;
    }

    return ($node instanceof Stmt\Break_ || $node instanceof Stmt\Continue_)
    && $node->num !== null;
  }

  /**
   * Save outer scope context and initialise a new inner scope.
   * When withNestingBonus is true (closures), the +1 + nesting_level cost
   * is billed to the outer scope before pushing.
   */
  private function pushScope(string $name, int $line, bool $with_nesting_bonus = false): void {
    if ($this->insideFunction) {
      $outer_score = $with_nesting_bonus
        ? $this->currentScore + 1 + $this->nestingLevel
        : $this->currentScore + 1;

      $this->stack[] = [
        'function' => $this->currentFunction,
        'line' => $this->currentLine,
        'score' => $outer_score,
        'nesting' => $this->nestingLevel,
      ];
    }

    $this->currentFunction = $name;
    $this->currentLine = $line;
    $this->insideFunction = true;
    $this->nestingLevel = 0;
    $this->currentScore = 0;
  }

  /**
   * Restore the outer scope from the stack (or mark as outside any function).
   */
  private function popScope(): void {
    if ($this->stack !== []) {
      $outer = array_pop($this->stack);
      $this->currentFunction = $outer['function'];
      $this->currentLine = $outer['line'];
      $this->currentScore = $outer['score'];
      $this->nestingLevel = $outer['nesting'];
      $this->insideFunction = true;
    } else {
      $this->insideFunction = false;
      $this->nestingLevel = 0;
      $this->currentScore = 0;
    }
  }

  private function isNestingNode(Node $node): bool {
    return $node instanceof Stmt\If_
    || $node instanceof Stmt\For_
    || $node instanceof Stmt\Foreach_
    || $node instanceof Stmt\While_
    || $node instanceof Stmt\Do_
    || $node instanceof Stmt\Switch_
    || $node instanceof Stmt\TryCatch;
  }

  private function isLogicalBinaryOp(Node $node): bool {
    return $this->isAndOp($node) || $this->isOrOp($node);
  }

  private function isAndOp(Node $node): bool {
    return $node instanceof BooleanAnd || $node instanceof LogicalAnd;
  }

  private function isOrOp(Node $node): bool {
    return $node instanceof BooleanOr || $node instanceof LogicalOr;
  }

  /**
   * Returns true if the node is a direct recursive call to the current function.
   */
  private function isRecursiveCall(Node $node): bool {
    if ($this->currentFunction === '') {
      return false;
    }

    if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
      return strtolower($node->name->getLast()) === strtolower($this->currentFunction);
    }

    if ($node instanceof Node\Expr\MethodCall && $node->name instanceof Node\Identifier) {
      return strtolower($node->name->toString()) === strtolower($this->currentFunction);
    }

    if ($node instanceof Node\Expr\StaticCall && $node->name instanceof Node\Identifier) {
      return strtolower($node->name->toString()) === strtolower($this->currentFunction);
    }

    return false;
  }

}
