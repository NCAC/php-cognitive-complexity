<?php declare(strict_types=1);

// Fixture: closures and arrow functions
//
// Spec §2.2: closures / arrow functions add +1 + nesting_level bonus to the
// enclosing function when they are nested, and reset the nesting level for
// their own body.
//
// Each closure/arrow function produces its own result entry.

// ── Top-level closure (not inside a function) ────────────────────────────────
// Not captured in results (no enclosing function).

// ── Function containing a closure ────────────────────────────────────────────
// closure is at nesting=0: outer() gets +1+0 = +1
// closure body has its own if: +1 (nesting=0 inside closure)
function with_closure(): void {
  $fn = function (int $x): bool { // outer: +1 (closure at nesting=0) → outer=1
    if ($x > 0) {               // closure body: +1 (nesting=0) → closure=1
      return true;
    }

    return false;
  };
  $fn(1);
}
// Expected outer with_closure: 1   (cost of having the closure)
// Expected {closure}:          1   (if inside closure)

// ── Function containing a nested closure (closure inside if) ─────────────────
// if at nesting=0: outer gets +1 → outer=1
// closure inside if: nesting=1 → outer gets +1+1=+2 → outer=3
// closure body has foreach: +1 (nesting=0 inside closure)
function with_closure_in_if(bool $flag): void {
  if ($flag) {                              // outer: +1 (nesting=0) → outer=1
    $fn = function (array $items): void { // outer: +1+1 (nesting=1) → outer=3
      foreach ($items as $item) {       // closure: +1 (nesting=0) → closure=1
        echo $item;
      }
    };
    $fn([]);
  }
}
// Expected with_closure_in_if: 3
// Expected {closure}:          1

// ── Arrow function ────────────────────────────────────────────────────────────
// Arrow fn at nesting=0 inside function: outer gets +1+0=+1
// Arrow fn body is a single expression (no control flow), so arrow_fn score=0
/**
 *
 * @param array<string> $items
 * @return array<string>
 */
function with_arrow_fn(array $items): array {
  return array_filter($items, fn (int $x): bool => $x > 0); // outer: +1 → outer=1
}
// Expected with_arrow_fn:  1
// Expected {arrow_fn}:     0
