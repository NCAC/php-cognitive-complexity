<?php declare(strict_types=1);

// Fixture: ternary operators
//
// Each ternary adds +1 (flat, no nesting bonus).
// Nested ternaries each add +1 individually.
//
// Score breakdown:
//   single ternary        → +1
//   two ternaries         → +2
//   ternary inside if     → if: +1(nesting=0), ternary: +1 flat → total 2

function ternary_single(int $x): string {
  return $x > 0 ? 'positive' : 'non-positive';  // +1
}
// Expected: 1

function ternary_double(int $x, int $y): string {
  $a = $x > 0 ? 'pos' : 'neg';   // +1
  $b = $y > 0 ? 'pos' : 'neg';   // +1

  return $a . $b;
}
// Expected: 2

function ternary_inside_if(int $x): string {
  if ($x !== 0) {                             // +1 (nesting=0) → 1
    return $x > 0 ? 'positive' : 'negative'; // +1 flat → 2
  }

  return 'zero';
}
// Expected: 2

function ternary_with_logical(bool $a, bool $b, int $x): string {
  return ($a && $b) ? 'both' : ($x > 0 ? 'pos' : 'neg');  // +1(&&) +1(outer ternary) +1(inner ternary) = 3
}
// Expected: 3
