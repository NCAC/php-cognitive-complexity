<?php declare(strict_types=1);

// Fixture: deeply nested structures to validate nesting bonus
/**
 * @param array<mixed> $data The data to process
 */
function nested_example(array $data): void {
  if (!empty($data)) {              // +1 (nesting=0) → 1
    foreach ($data as $item) {   // +1+1 (nesting=1) → 3
      if ($item > 0) {         // +1+2 (nesting=2) → 6
                // nesting level 3
      }
    }
  }
}
// Expected total: 6
