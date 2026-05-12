<?php declare(strict_types=1);

// Fixture: recursive calls
//
// Spec §2.4: each direct recursive call adds +1 flat.

// ── Simple recursion ─────────────────────────────────────────────────────────
function factorial(int $n): int {
  if ($n <= 1) {          // +1 (nesting=0) → 1
    return 1;
  }

  return $n * factorial($n - 1); // +1 (recursive call) → 2
}
// Expected: 2

// ── Recursion inside a loop ───────────────────────────────────────────────────
// foreach: +1 (nesting=0) → 1
// recursive call inside foreach: nesting=1, but recursion is flat → +1 → 2
/**
 * @param list<int> $items
 */
function sum_recursive(array $items): int {
  if (empty($items)) {                               // +1 (nesting=0) → 1
    return 0;
  }

  return array_shift($items) + sum_recursive($items); // +1 (recursive) → 2
}
// Expected: 2

// ── Method recursion ─────────────────────────────────────────────────────────
class Tree {

/**
             * @param list<mixed> $node
             * @return list<mixed>
             */
  public function flatten(array $node): array {
    $result = [];
    foreach ($node as $child) {      // +1 (nesting=0) → 1
      if (\is_array($child)) {      // +1+1 (nesting=1) → 3
        $result = array_merge($result, $this->flatten($child)); // +1 (recursive) → 4
      } else {
        $result[] = $child;
      }
    }

    return $result;
  }

}
// Expected Tree::flatten: 5  (foreach+1, if+2, recursive+1, else+1)
