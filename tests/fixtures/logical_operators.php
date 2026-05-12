<?php declare(strict_types=1);

// Fixture: logical operator sequences
//
// Algorithm: each *change* in boolean operator type adds +1.
// Consecutive same-type operators count as one sequence.
//
// Score breakdown per expression:
//   $a && $b                          → +1 (one && sequence)
//   $a || $b                          → +1 (one || sequence)
//   $a && $b && $c                    → +1 (one && sequence, not 2)
//   $a || $b && $c                    → +2 (|| sequence, then && sequence = change)
//   $a && $b || $c && $d              → +3 (&&, then ||, then &&)
//   $a and $b                         → +1 (logical `and` = same as &&)
//   $a or $b                          → +1 (logical `or` = same as ||)
//
// Each expression is in its own function to isolate scores.

function logical_and_simple(bool $a, bool $b): bool {
  return $a && $b;  // +1 (one && sequence)
}
// Expected: 1

function logical_or_simple(bool $a, bool $b): bool {
  return $a || $b;  // +1 (one || sequence)
}
// Expected: 1

function logical_and_chain(bool $a, bool $b, bool $c): bool {
  return $a && $b && $c;  // +1 (still one && sequence)
}
// Expected: 1

function logical_mixed_and_or(bool $a, bool $b, bool $c): bool {
  return $a || $b && $c;  // +1 (||) +1 (&& after ||) = 2
}
// Expected: 2

function logical_complex(bool $a, bool $b, bool $c, bool $d): bool {
  // Parsed as ($a&&$b)||($c&&$d):
  // BooleanOr.left = BooleanAnd($a,$b)   → isOrOp(BooleanOr) && !isOrOp(BooleanAnd) → +1
  // BooleanAnd($a,$b): left=$a (not AND) → +1
  // BooleanAnd($c,$d): left=$c (not AND) → +1
  return $a && $b || $c && $d;
}
// Expected: 3

function logical_keyword_and(bool $a, bool $b): bool {
  return $a and $b;  // +1 (logical and)
}
// Expected: 1

function logical_keyword_or(bool $a, bool $b): bool {
  return $a or $b;  // +1 (logical or)
}
// Expected: 1
