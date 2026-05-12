<?php declare(strict_types=1);

// Fixture: every nesting-incrementing control flow structure, one level deep.
//
// Algorithm reminder:
//   - Each nesting-incrementing node adds (1 + current_nesting_level)
//   - else/elseif add +1 flat (no nesting bonus at their own level)
//   - Nesting level is incremented when entering the node, decremented when leaving
//
// Score breakdown:
//   if        → +1 (nesting=0)                                  score = 1
//   elseif    → +1 flat (continuation, no nesting bonus)         score = 2
//   else      → +1 flat (continuation, no nesting bonus)         score = 3
//   for       → +1 (nesting=0)                                  score = 4
//   foreach   → +1 (nesting=0)                                  score = 5
//   while     → +1 (nesting=0)                                  score = 6
//   do-while  → +1 (nesting=0)                                  score = 7
//   switch    → +1 (nesting=0)                                  score = 8
//   try       → +1 (nesting=0)                                  score = 9
//   catch     → +1 flat (continuation, no nesting bonus)        score = 10
//
// Expected total: 10

/**
 * @param list<int> $items
 */
function control_flow_flat(array $items, int $x): int {
  $result = 0;

  if ($x > 0) {           // +1 (nesting=0) → 1
    $result++;
  } elseif ($x < 0) {     // +1 flat → 2
    $result--;
  } else {                // +1 flat → 3
    $result = 0;
  }

  for ($i = 0; $i < 3; $i++) {        // +1 (nesting=0) → 4
    $result += $i;
  }

  foreach ($items as $item) {          // +1 (nesting=0) → 5
    $result += $item;
  }

  while ($result > 100) {              // +1 (nesting=0) → 6
    $result--;
  }

  do {                                 // +1 (nesting=0) → 7
    $result++;
  } while ($result < 0);

  switch ($x) {                        // +1 (nesting=0) → 8
    case 1:
      $result = 1;
      break;
    default:
      $result = 0;
      break;
  }

  try {                                // +1 (nesting=0) → 9
    $result = intdiv($result, $x);
  } catch (\DivisionByZeroError $e) { // +1 (nesting=0) → 10
    $result = 0;
  }

  return $result;
}
// Expected total: 10
