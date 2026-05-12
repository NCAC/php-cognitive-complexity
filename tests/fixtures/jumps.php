<?php declare(strict_types=1);

// Fixture: jumps to labels / non-trivial break & continue / goto
//
// Spec §2.3: break N, continue N (N > 1) and goto add +1 flat.
// Plain break / continue (no argument, or argument 1) do NOT increment.

// ── break 2 inside nested loops ──────────────────────────────────────────────
/**
 * @param list<list<int>> $matrix
 */
function break_label(array $matrix): int {
  $found = -1;
  foreach ($matrix as $row) {       // +1 (nesting=0) → 1
    foreach ($row as $value) {    // +1+1 (nesting=1) → 3
      if ($value === 0) {       // +1+2 (nesting=2) → 6
        $found = $value;
        break 2;              // +1 (jump to outer loop) → 7
      }
    }
  }

  return $found;
}
// Expected: 7

// ── continue 2 inside nested loops ───────────────────────────────────────────
/**
 * @param list<list<int>> $matrix
 * @return list<int>
 */
function continue_label(array $matrix): array {
  $result = [];
  foreach ($matrix as $row) {      // +1 (nesting=0) → 1
    foreach ($row as $value) {   // +1+1 (nesting=1) → 3
      if ($value < 0) {        // +1+2 (nesting=2) → 6
        continue 2;          // +1 (jump to outer loop) → 7
      }

      $result[] = $value;
    }
  }

  return $result;
}
// Expected: 7

// ── plain break (no increment) ────────────────────────────────────────────────
/**
 * @param list<int> $items
 */
function plain_break(array $items): int {
  $found = -1;
  foreach ($items as $item) { // +1 (nesting=0) → 1
    if ($item === 0) {      // +1+1 (nesting=1) → 3
      $found = $item;
      break;              // plain break: no increment
    }
  }

  return $found;
}
// Expected: 3
