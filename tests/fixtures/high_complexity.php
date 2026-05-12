<?php declare(strict_types=1);

// Fixture: high complexity function (score >> 5)
/**
 * @param array<mixed> $items The items to process
 */
function process_order(array $items, bool $is_premium, string $country): float {
  $total = 0.0;

  foreach ($items as $item) {               // +1 (nesting=0) → score=1
    if ($item['qty'] > 0) {               // +1+1 (nesting=1) → score=3
      if ($item['type'] === 'digital') { // +1+2 (nesting=2) → score=6
        $total += $item['price'];
      } elseif ($is_premium) {            // +1 flat (continuation) → score=7
        $total += $item['price'] * 0.9;
      } else {                           // +1 flat (continuation) → score=8
        $total += $item['price'] * 0.95;
      }

      if ($country === 'FR' || $country === 'BE') { // +1+2 (nesting=2) +1(logical) → score=12
        $total *= 1.2;
      }
    }
  }

  return $total;
}
